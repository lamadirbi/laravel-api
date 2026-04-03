<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConsultationController;
use App\Http\Controllers\Api\MedicalFileController;
use App\Http\Controllers\Api\MedicalProfileController;
use App\Http\Controllers\Api\PhysicianProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/medical-profile', [MedicalProfileController::class, 'show']);
        Route::put('/medical-profile', [MedicalProfileController::class, 'update']);

        Route::get('/physician-profile', [PhysicianProfileController::class, 'show'])
            ->middleware('role:physician');
        Route::put('/physician-profile', [PhysicianProfileController::class, 'update'])
            ->middleware('role:physician');

        Route::post('/medical-files', [MedicalFileController::class, 'store']);
        Route::get('/medical-files/{medicalFile}/download', [MedicalFileController::class, 'download']);

        Route::get('/consultations', [ConsultationController::class, 'indexForMe']);
        Route::get('/consultations/queue', [ConsultationController::class, 'queue'])
            ->middleware('role:physician');
        Route::get('/consultations/{consultation}', [ConsultationController::class, 'show']);
        Route::post('/consultations/{consultation}/claim', [ConsultationController::class, 'claim'])
            ->middleware('role:physician');
        Route::post('/consultations', [ConsultationController::class, 'store'])
            ->middleware('role:patient');
        Route::post('/consultations/{consultation}/respond', [ConsultationController::class, 'respond'])
            ->middleware('role:physician');
    });
});

