<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\MedicalFile;
use App\Models\PhysicianProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MedicalFileController extends Controller
{
    public function store(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB
            'file_kind' => ['nullable', 'string', 'in:report,image,pdf,audio,video,other'],
            'consultation_id' => ['nullable', 'integer', 'exists:consultations,id'],
        ]);

        $ownerUserId = $user->id;

        $uploaded = $request->file('file');
        $originalName = $uploaded->getClientOriginalName();
        $mimeType = $uploaded->getClientMimeType();

        $ext = $uploaded->getClientOriginalExtension();
        $safeExt = $ext ? ('.'.Str::lower($ext)) : '';
        $fileName = Str::uuid()->toString().$safeExt;

        $path = $uploaded->storeAs(
            'medical-files/'.$ownerUserId,
            $fileName,
            ['disk' => 'local']
        );

        $record = MedicalFile::create([
            'owner_user_id' => $ownerUserId,
            'uploaded_by_user_id' => $user->id,
            'consultation_id' => $data['consultation_id'] ?? null,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size_bytes' => $uploaded->getSize(),
            'file_kind' => $data['file_kind'] ?? 'other',
        ]);

        return response()->json([
            'file' => $record,
        ], 201);
    }

    public function download(Request $request, MedicalFile $medicalFile)
    {
        /** @var User $user */
        $user = $request->user();

        $allowed = false;
        if ($user->role === User::ROLE_PHYSICIAN) {
            // ملفات الاستشارة، أو ملفات يمتلكها الطبيب (مثل مرفق الشهادة)
            $allowed = $medicalFile->consultation_id !== null
                || $medicalFile->owner_user_id === $user->id;
        } else {
            $allowed = $medicalFile->owner_user_id === $user->id;

            // المريض يمكنه عرض مرفق شهادة الطبيب ضمن استشارته المكتملة (consultation_id في الاستعلام)
            if (! $allowed && $user->role === User::ROLE_PATIENT && $request->filled('consultation_id')) {
                $consultationId = (int) $request->query('consultation_id');
                $consultation = Consultation::query()
                    ->where('id', $consultationId)
                    ->where('patient_id', $user->id)
                    ->whereNotNull('physician_id')
                    ->first();

                if ($consultation) {
                    $pProfile = PhysicianProfile::query()
                        ->where('user_id', $consultation->physician_id)
                        ->first();
                    if ($pProfile && in_array((int) $medicalFile->id, $pProfile->orderedCertificateFileIds(), true)) {
                        $allowed = true;
                    }
                }
            }
        }

        if (! $allowed) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $disk = $medicalFile->disk ?: 'local';
        $path = $medicalFile->path;

        if (! Storage::disk($disk)->exists($path)) {
            return response()->json(['message' => 'الملف غير موجود'], 404);
        }

        $absolute = Storage::disk($disk)->path($path);

        return response()->download(
            $absolute,
            $medicalFile->original_name,
            [
                'Content-Type' => $medicalFile->mime_type ?: 'application/octet-stream',
            ]
        );
    }
}
