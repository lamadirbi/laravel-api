<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalProfile;
use App\Models\User;
use Illuminate\Http\Request;

class MedicalProfileController extends Controller
{
    public function show(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $profile = MedicalProfile::firstOrCreate(
            ['user_id' => $user->id],
            []
        );

        return response()->json([
            'profile' => $profile,
        ]);
    }

    public function update(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'height_cm' => ['nullable', 'integer', 'min:30', 'max:260'],
            'weight_kg' => ['nullable', 'integer', 'min:1', 'max:500'],
            'chronic_diseases' => ['nullable', 'string'],
            'medical_history' => ['nullable', 'string'],
            'allergies' => ['nullable', 'string'],
            'current_medications' => ['nullable', 'string'],
        ]);

        $profile = MedicalProfile::firstOrCreate(
            ['user_id' => $user->id],
            []
        );

        $profile->fill($data);
        $profile->save();

        return response()->json([
            'profile' => $profile,
        ]);
    }
}
