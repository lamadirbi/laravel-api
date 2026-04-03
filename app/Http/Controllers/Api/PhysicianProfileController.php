<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalFile;
use App\Models\PhysicianProfile;
use App\Models\User;
use Illuminate\Http\Request;

class PhysicianProfileController extends Controller
{
    public function show(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->role !== User::ROLE_PHYSICIAN) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $profile = PhysicianProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['specialty' => 'غير محدد', 'certificate' => '']
        );

        $profile->load('certificateFile');
        $profile->hydrateCertificateFilesRelation();

        return response()->json([
            'profile' => $profile,
        ]);
    }

    public function update(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->role !== User::ROLE_PHYSICIAN) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $data = $request->validate([
            'specialty' => ['required', 'string', 'max:255'],
            'certificate' => ['nullable', 'string', 'max:5000'],
            'certificate_file_id' => ['nullable', 'integer', 'exists:medical_files,id'],
            'certificate_file_ids' => ['nullable', 'array', 'max:20'],
            'certificate_file_ids.*' => ['integer', 'distinct', 'exists:medical_files,id'],
        ]);

        $profile = PhysicianProfile::firstOrCreate(
            ['user_id' => $user->id],
            []
        );

        $profile->specialty = $data['specialty'];
        $profile->certificate = $data['certificate'] ?? '';

        if (array_key_exists('certificate_file_ids', $data) && $data['certificate_file_ids'] !== null) {
            $requested = array_values(array_unique(array_map('intval', $data['certificate_file_ids'])));
            $owned = MedicalFile::query()
                ->whereIn('id', $requested)
                ->where('owner_user_id', $user->id)
                ->pluck('id')
                ->all();
            $ordered = array_values(array_filter(
                $requested,
                fn (int $id) => in_array($id, $owned, true)
            ));
            $profile->certificate_file_ids = $ordered;
            $profile->certificate_file_id = $ordered[0] ?? null;
        } elseif (array_key_exists('certificate_file_id', $data)) {
            $id = $data['certificate_file_id'];
            if ($id !== null) {
                $owns = MedicalFile::query()
                    ->where('id', $id)
                    ->where('owner_user_id', $user->id)
                    ->exists();
                if (! $owns) {
                    return response()->json(['message' => 'الملف غير موجود أو غير مملوك لك'], 422);
                }
            }
            $profile->certificate_file_id = $id;
            $profile->certificate_file_ids = $id ? [(int) $id] : [];
        }

        $profile->save();

        $profile->load('certificateFile');
        $profile->hydrateCertificateFilesRelation();

        return response()->json([
            'profile' => $profile,
        ]);
    }
}
