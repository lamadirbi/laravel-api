<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\ConsultationMessage;
use App\Models\MedicalFile;
use App\Models\PhysicianProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ConsultationController extends Controller
{
    protected function hydratePhysicianCertificateFiles(Consultation $consultation): void
    {
        $pp = $consultation->physician?->physicianProfile;
        if ($pp instanceof PhysicianProfile) {
            $pp->hydrateCertificateFilesRelation();
        }
    }

    protected function verifiedPhysicianQuery()
    {
        return User::query()
            ->where('role', User::ROLE_PHYSICIAN)
            ->where('is_disabled', false)
            ->whereHas('physicianProfile', fn ($q) => $q->where('verification_status', PhysicianProfile::STATUS_APPROVED));
    }

    public function queue(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->role !== User::ROLE_PHYSICIAN) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $perPage = (int) $request->integer('per_page', 50);
        $perPage = max(1, min($perPage, 100));

        $items = Consultation::query()
            ->whereNull('physician_id')
            ->where('assignment_mode', Consultation::MODE_QUEUE)
            ->where('status', 'pending')
            ->with([
                'patient:id,name,email,role',
                'patient.medicalProfile',
            ])
            ->orderByDesc('submitted_at')
            ->paginate($perPage);

        return response()->json($items);
    }

    public function claim(Request $request, Consultation $consultation)
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->role !== User::ROLE_PHYSICIAN) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        if ($consultation->assignment_mode === Consultation::MODE_DIRECT) {
            return response()->json(['message' => 'هذه استشارة موجّهة لطبيب محدد ولا يمكن استلامها من الطابور.'], 422);
        }

        if ($consultation->status !== 'pending') {
            return response()->json(['message' => 'لا يمكن استلام استشارة غير معلّقة'], 422);
        }

        if ($consultation->physician_id !== null && $consultation->physician_id !== $user->id) {
            return response()->json(['message' => 'تم استلام هذه الاستشارة من طبيب آخر'], 409);
        }

        $consultation->physician_id = $user->id;
        $consultation->save();

        return response()->json([
            'consultation' => $consultation->load([
                'patient:id,name,email,role',
                'patient.medicalProfile',
                'physician:id,name,email,role',
                'physician.physicianProfile',
            ]),
        ]);
    }

    public function indexForMe(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $query = Consultation::query()->with([
            'patient:id,name,email,role',
            'patient.medicalProfile',
            'physician:id,name,email,role',
            'physician.physicianProfile',
        ]);

        if ($user->role === User::ROLE_PHYSICIAN) {
            $query->where('physician_id', $user->id);
        } else {
            $query->where('patient_id', $user->id);
        }

        $perPage = (int) $request->integer('per_page', 50);
        $perPage = max(1, min($perPage, 100));

        $items = $query->orderByDesc('submitted_at')->paginate($perPage);

        return response()->json($items);
    }

    public function show(Request $request, Consultation $consultation)
    {
        /** @var User $user */
        $user = $request->user();

        $allowed = false;
        if ($user->role === User::ROLE_PHYSICIAN) {
            if (! $user->isVerifiedPhysician()) {
                return response()->json(['message' => 'حسابك بانتظار موافقة الإدارة.'], 403);
            }
            $allowed = $consultation->physician_id === null || $consultation->physician_id === $user->id;
        } else {
            $allowed = $consultation->patient_id === $user->id;
        }

        if (! $allowed) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $consultation->load([
            'patient:id,name,email,role',
            'patient.medicalProfile',
            'physician:id,name,email,role',
            'physician.physicianProfile.certificateFile:id,original_name,mime_type,file_kind,size_bytes,created_at',
            'medicalFiles:id,owner_user_id,uploaded_by_user_id,consultation_id,original_name,mime_type,size_bytes,file_kind,created_at',
            'messages.sender:id,name,role',
        ]);

        if ($consultation->physician_id !== null && $consultation->physician === null) {
            $consultation->setRelation(
                'physician',
                User::query()
                    ->whereKey($consultation->physician_id)
                    ->select(['id', 'name', 'email', 'role'])
                    ->with([
                        'physicianProfile.certificateFile:id,original_name,mime_type,file_kind,size_bytes,created_at',
                    ])
                    ->first()
            );
        }

        $this->hydratePhysicianCertificateFiles($consultation);
        $this->ensureLegacyPhysicianMessage($consultation);

        return response()->json([
            'consultation' => $consultation,
        ]);
    }

    public function update(Request $request, Consultation $consultation)
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->role !== User::ROLE_PATIENT || $consultation->patient_id !== $user->id) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        if ($consultation->physician_response || $consultation->messages()->where('sender_role', 'physician')->exists()) {
            return response()->json(['message' => 'لا يمكن تعديل الاستشارة بعد رد الطبيب.'], 422);
        }

        $data = $request->validate([
            'question_text' => ['required', 'string', 'min:10'],
        ]);

        $consultation->question_text = $data['question_text'];
        $consultation->save();

        return response()->json([
            'consultation' => $consultation->fresh()->load([
                'patient:id,name,role',
                'patient.medicalProfile',
                'physician:id,name,role',
                'medicalFiles:id,owner_user_id,uploaded_by_user_id,consultation_id,original_name,mime_type,size_bytes,file_kind,created_at',
                'messages.sender:id,name,role',
            ]),
        ]);
    }

    public function storeMessage(Request $request, Consultation $consultation)
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'body' => ['required', 'string', 'min:2'],
        ]);

        if ($user->role === User::ROLE_PATIENT) {
            if ($consultation->patient_id !== $user->id) {
                return response()->json(['message' => 'غير مصرح'], 403);
            }
            if (! $consultation->physician_response && ! $consultation->messages()->where('sender_role', 'physician')->exists()) {
                return response()->json(['message' => 'يمكنك الرد بعد استلام إجابة الطبيب.'], 422);
            }
            $role = 'patient';
        } elseif ($user->role === User::ROLE_PHYSICIAN) {
            if (! $user->isVerifiedPhysician()) {
                return response()->json(['message' => 'حسابك بانتظار موافقة الإدارة.'], 403);
            }
            if ($consultation->physician_id && $consultation->physician_id !== $user->id) {
                return response()->json(['message' => 'هذه الاستشارة لطبيب آخر'], 403);
            }
            $role = 'physician';
            $consultation->physician_id = $user->id;
            $consultation->physician_response = $data['body'];
            $consultation->responded_at = Carbon::now();
            $consultation->save();
        } else {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $message = ConsultationMessage::create([
            'consultation_id' => $consultation->id,
            'sender_id' => $user->id,
            'sender_role' => $role,
            'body' => $data['body'],
        ]);

        $consultation->load([
            'patient:id,name,role',
            'patient.medicalProfile',
            'physician:id,name,role',
            'physician.physicianProfile',
            'medicalFiles:id,owner_user_id,uploaded_by_user_id,consultation_id,original_name,mime_type,size_bytes,file_kind,created_at',
            'messages.sender:id,name,role',
        ]);

        return response()->json([
            'message' => $message->load('sender:id,name,role'),
            'consultation' => $consultation,
        ], 201);
    }

    public function store(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->role !== User::ROLE_PATIENT) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $data = $request->validate([
            'question_text' => ['required', 'string', 'min:10'],
            'file_ids' => ['nullable', 'array'],
            'file_ids.*' => ['integer', 'exists:medical_files,id'],
            'assignment_mode' => ['nullable', 'string', Rule::in([
                Consultation::MODE_QUEUE,
                Consultation::MODE_DIRECT,
            ])],
            'physician_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $mode = $data['assignment_mode'] ?? Consultation::MODE_QUEUE;
        $physicianId = $data['physician_id'] ?? null;

        if ($mode === Consultation::MODE_DIRECT) {
            if (! $physicianId) {
                return response()->json(['message' => 'يجب اختيار طبيب موثّق عند الإرسال المباشر.'], 422);
            }

            $physician = $this->verifiedPhysicianQuery()->whereKey($physicianId)->first();
            if (! $physician) {
                return response()->json(['message' => 'الطبيب المختار غير متاح أو غير موثّق.'], 422);
            }
        } elseif ($physicianId) {
            return response()->json(['message' => 'لا يمكن تحديد طبيب مع وضع الطابور العام.'], 422);
        }

        $patientId = $user->id;

        $consultation = Consultation::create([
            'patient_id' => $patientId,
            'physician_id' => $mode === Consultation::MODE_DIRECT ? $physicianId : null,
            'assignment_mode' => $mode,
            'question_text' => $data['question_text'],
            'status' => 'pending',
            'submitted_at' => Carbon::now(),
        ]);

        if (!empty($data['file_ids'])) {
            MedicalFile::whereIn('id', $data['file_ids'])
                ->where('owner_user_id', $patientId)
                ->update(['consultation_id' => $consultation->id]);
        }

        return response()->json([
            'consultation' => $consultation->load([
                'patient:id,name,role',
                'physician:id,name,role',
                'physician.physicianProfile',
            ]),
        ], 201);
    }

    public function respond(Request $request, Consultation $consultation)
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->role !== User::ROLE_PHYSICIAN) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        if ($consultation->physician_id && $consultation->physician_id !== $user->id) {
            return response()->json(['message' => 'هذه الاستشارة لطبيب آخر'], 403);
        }

        $data = $request->validate([
            'response' => ['required', 'string', 'min:5'],
            'mark_completed' => ['nullable', 'boolean'],
        ]);

        $consultation->physician_id = $user->id;
        $consultation->physician_response = $data['response'];
        $consultation->responded_at = Carbon::now();
        if (($data['mark_completed'] ?? true) === true) {
            $consultation->status = 'completed';
        }
        $consultation->save();

        ConsultationMessage::create([
            'consultation_id' => $consultation->id,
            'sender_id' => $user->id,
            'sender_role' => 'physician',
            'body' => $data['response'],
        ]);

        $consultation->load([
            'patient:id,name,role',
            'patient.medicalProfile',
            'physician:id,name,role',
            'physician.physicianProfile.certificateFile:id,original_name,mime_type,file_kind,size_bytes,created_at',
            'medicalFiles:id,owner_user_id,uploaded_by_user_id,consultation_id,original_name,mime_type,size_bytes,file_kind,created_at',
            'messages.sender:id,name,role',
        ]);
        $this->hydratePhysicianCertificateFiles($consultation);

        return response()->json([
            'consultation' => $consultation,
        ]);
    }

    protected function ensureLegacyPhysicianMessage(Consultation $consultation): void
    {
        if (! $consultation->physician_response || $consultation->relationLoaded('messages') === false) {
            return;
        }

        if ($consultation->messages->isNotEmpty()) {
            return;
        }

        if (! $consultation->physician_id) {
            return;
        }

        $legacy = ConsultationMessage::create([
            'consultation_id' => $consultation->id,
            'sender_id' => $consultation->physician_id,
            'sender_role' => 'physician',
            'body' => $consultation->physician_response,
            'created_at' => $consultation->responded_at ?? $consultation->updated_at,
            'updated_at' => $consultation->responded_at ?? $consultation->updated_at,
        ]);
        $legacy->load('sender:id,name,role');
        $consultation->setRelation('messages', collect([$legacy]));
    }
}
