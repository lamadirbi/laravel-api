<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConsultationController;
use App\Http\Controllers\Api\MedicalFileController;
use App\Http\Controllers\Api\MedicalProfileController;
use App\Http\Controllers\Api\PhysicianProfileController;
use App\Http\Controllers\Api\PlatformStatsController;
use App\Http\Controllers\Api\VerifiedPhysicianController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// يُستخدم مرة للنشر (مثلاً Render بدون shell). عيّن MIGRATE_HTTP_SECRET في Render ثم احذف المسار أو المتغير بعد الانتهاء.
Route::get('/run-migrations', function () {
    $secret = (string) env('MIGRATE_HTTP_SECRET', '');
    if ($secret === '' || ! hash_equals($secret, (string) request()->query('key', ''))) {
        abort(404);
    }

    try {
        Artisan::call('migrate', ['--force' => true]);

        return 'تم إنشاء الجداول بنجاح: '.Artisan::output();
    } catch (\Throwable $e) {
        return 'حدث خطأ: '.$e->getMessage();
    }
});

Route::prefix('v1')->group(function () {
    Route::get('/platform-stats', [PlatformStatsController::class, 'index']);
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/medical-profile', [MedicalProfileController::class, 'show']);
        Route::put('/medical-profile', [MedicalProfileController::class, 'update']);

        Route::get('/physician-profile', [PhysicianProfileController::class, 'show'])
            ->middleware('role:physician');
        Route::put('/physician-profile', [PhysicianProfileController::class, 'update'])
            ->middleware('role:physician');

        Route::get('/verified-physicians', [VerifiedPhysicianController::class, 'index']);
        Route::get('/verified-physicians/{physician}', [VerifiedPhysicianController::class, 'show']);

        Route::post('/medical-files', [MedicalFileController::class, 'store']);
        Route::get('/medical-files/{medicalFile}/download', [MedicalFileController::class, 'download']);

        Route::get('/consultations', [ConsultationController::class, 'indexForMe']);
        Route::get('/consultations/queue', [ConsultationController::class, 'queue'])
            ->middleware(['role:physician', 'physician.verified']);
        Route::get('/consultations/{consultation}', [ConsultationController::class, 'show']);
        Route::patch('/consultations/{consultation}', [ConsultationController::class, 'update'])
            ->middleware('role:patient');
        Route::post('/consultations/{consultation}/messages', [ConsultationController::class, 'storeMessage']);
        Route::post('/consultations/{consultation}/claim', [ConsultationController::class, 'claim'])
            ->middleware(['role:physician', 'physician.verified']);
        Route::post('/consultations', [ConsultationController::class, 'store'])
            ->middleware('role:patient');
        Route::post('/consultations/{consultation}/respond', [ConsultationController::class, 'respond'])
            ->middleware(['role:physician', 'physician.verified']);

        Route::prefix('admin')->middleware('role:admin')->group(function () {
            Route::get('/users', [AdminController::class, 'users']);
            Route::patch('/users/{user}/disabled', [AdminController::class, 'setUserDisabled']);
            Route::get('/physicians/pending', [AdminController::class, 'pendingPhysicians']);
            Route::get('/physicians', [AdminController::class, 'physicians']);
            Route::post('/physicians/{physicianProfile}/approve', [AdminController::class, 'approvePhysician']);
            Route::post('/physicians/{physicianProfile}/reject', [AdminController::class, 'rejectPhysician']);
        });
    });
});
