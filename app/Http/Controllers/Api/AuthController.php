<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhysicianProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $role = $request->input('role', User::ROLE_PATIENT);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'role' => ['nullable', 'string', Rule::in([
                User::ROLE_PATIENT,
                User::ROLE_PHYSICIAN,
            ])],
            'phone' => ['nullable', 'string', 'max:32'],
            'physician_specialty' => [
                Rule::requiredIf($role === User::ROLE_PHYSICIAN),
                'string',
                'max:255',
            ],
            'physician_certificate' => [
                Rule::requiredIf($role === User::ROLE_PHYSICIAN),
                'string',
                'max:5000',
            ],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower(trim($data['email'])),
            'password' => $data['password'],
            'role' => $data['role'] ?? User::ROLE_PATIENT,
            'phone' => $data['phone'] ?? null,
        ]);

        if (($data['role'] ?? User::ROLE_PATIENT) === User::ROLE_PHYSICIAN) {
            PhysicianProfile::create([
                'user_id' => $user->id,
                'specialty' => $data['physician_specialty'],
                'certificate' => $data['physician_certificate'],
                'verification_status' => PhysicianProfile::STATUS_PENDING,
            ]);
        }

        $user->load('physicianProfile');
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = strtolower(trim($data['email']));

        /** @var User|null $user */
        $user = User::where('email', $email)->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 422);
        }

        if ($user->is_disabled) {
            return response()->json(['message' => 'تم تعطيل هذا الحساب. تواصل مع الإدارة.'], 403);
        }

        $user->load('physicianProfile');
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function me(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $user->load('physicianProfile');

        return response()->json([
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        /** @var \Laravel\Sanctum\PersonalAccessToken|null $token */
        $token = $user->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return response()->json(['message' => 'تم تسجيل الخروج']);
    }

    public function forgotPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim($data['email']));

        /** @var User|null $user */
        $user = User::where('email', $email)->first();
        if ($user && ! $user->is_disabled) {
            Password::sendResetLink(['email' => $email]);
        }

        return response()->json([
            'message' => 'إذا كان البريد مسجّلاً، أُرسلت تعليمات إعادة التعيين.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $data['email'] = strtolower(trim($data['email']));

        $status = Password::reset(
            $data,
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'تمت إعادة تعيين كلمة المرور. يمكنك تسجيل الدخول الآن.',
            ]);
        }

        return response()->json([
            'message' => __($status),
        ], 422);
    }

    public function changePassword(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'كلمة المرور الحالية غير صحيحة.'], 422);
        }

        $user->forceFill(['password' => $data['password']])->save();
        $user->tokens()->delete();

        return response()->json([
            'message' => 'تم تغيير كلمة المرور. سجّل دخولك من جديد.',
        ]);
    }
}
