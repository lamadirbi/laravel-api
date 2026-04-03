<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\MedicalFile;
use App\Models\PhysicianProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ConsultationController extends Controller
{
    protected function hydratePhysicianCertificateFiles(Consultation $consultation): void
    {
        $pp = $consultation->physician?->physicianProfile;
        if ($pp instanceof PhysicianProfile) {
            $pp->hydrateCertificateFilesRelation();
        }
    }

    public function queue(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->role !== User::ROLE_PHYSICIAN) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $items = Consultation::query()
            ->whereNull('physician_id')
            ->where('status', 'pending')
            ->with([
                'patient:id,name,email,role',
                'patient.medicalProfile',
            ])
            ->orderBy('submitted_at')
            ->paginate(20);

        return response()->json($items);
    }

    public function claim(Request $request, Consultation $consultation)
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->role !== User::ROLE_PHYSICIAN) {
            return response()->json(['message' => 'غير مصرح'], 403);
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

        $items = $query->orderByDesc('submitted_at')->paginate(20);

        return response()->json($items);
    }

    public function show(Request $request, Consultation $consultation)
    {
        /** @var User $user */
        $user = $request->user();

        $allowed = false;
        if ($user->role === User::ROLE_PHYSICIAN) {
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
        ]);

        // إن وُجد طبيب معيّن لكن relation فارغة (بيانات قديمة أو حذف مستخدم ثم استعادته)، نحمّل الطبيب صراحةً.
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

        return response()->json([
            'consultation' => $consultation,
        ]);
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
        ]);

        $patientId = $user->id;

        $consultation = Consultation::create([
            'patient_id' => $patientId,
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
            'consultation' => $consultation->load(['patient:id,name,role']),
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

        // In later increment: broadcast real-time notification to patient here.

        $consultation->load([
            'patient:id,name,role',
            'patient.medicalProfile',
            'physician:id,name,role',
            'physician.physicianProfile.certificateFile:id,original_name,mime_type,file_kind,size_bytes,created_at',
            'medicalFiles:id,owner_user_id,uploaded_by_user_id,consultation_id,original_name,mime_type,size_bytes,file_kind,created_at',
        ]);
        $this->hydratePhysicianCertificateFiles($consultation);

        return response()->json([
            'consultation' => $consultation,
        ]);
    }
}
