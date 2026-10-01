<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GoatController;
use App\Http\Controllers\Api\HealthLogController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\MedicalRecordController;
use App\Http\Controllers\Api\GeminiController;
use App\Http\Controllers\Api\CollarController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\LoraController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\AuthController;

// Mobile app token auth — public login, everything else needs a Bearer token.
Route::post('/mobile/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/mobile/me', [AuthController::class, 'me']);
    Route::post('/mobile/logout', [AuthController::class, 'logout']);
});

// Shared by the web dashboard (session cookie, stateful) and the mobile app (Bearer token).
Route::middleware(['auth:sanctum', \App\Http\Middleware\FeatureAccess::class])->group(function () {
    Route::post('/firebase/session', \App\Http\Controllers\Api\FirebaseSessionController::class)
        ->middleware(['role:Admin,Staff,Caretaker', 'throttle:20,1']);

    // Read access — any authenticated role (Admin, Staff, Caretaker)
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/reports/export', [\App\Http\Controllers\Api\ExportController::class, 'reports']);
    Route::get('/medical-records/export', [\App\Http\Controllers\Api\ExportController::class, 'medical']);
    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/goats', [GoatController::class, 'index']);
    Route::get('/goats/{id}', [GoatController::class, 'show']);
    Route::get('/health-logs', [HealthLogController::class, 'index']);
    Route::get('/alerts', [AlertController::class, 'index']);
    Route::get('/medical-records', [MedicalRecordController::class, 'index']);
    // Caretakers are the people most likely to enter treatment details in the
    // field, so medical-record creation is available to every authenticated
    // role. Other herd-management writes remain limited to Admin and Staff.
    Route::post('/medical-records', [MedicalRecordController::class, 'store']);
    Route::post('/goats/{id}/medical-records', [MedicalRecordController::class, 'storeForGoat']);
    Route::get('/collars', [CollarController::class, 'index']);
    Route::post('/gemini-advice', [GeminiController::class, 'advice']);

    // Writes are authorized by the configured feature permissions.
    Route::middleware([])->group(function () {
        Route::post('/goats', [GoatController::class, 'store']);
        Route::put('/goats/{id}', [GoatController::class, 'update']);
        Route::delete('/goats/{id}', [GoatController::class, 'destroy']);

        Route::post('/health-logs', [HealthLogController::class, 'store']);

        Route::post('/alerts', [AlertController::class, 'store']);

        Route::post('/collars', [CollarController::class, 'store']);
        Route::put('/collars/{id}', [CollarController::class, 'update']);
        Route::delete('/collars/{id}', [CollarController::class, 'destroy']);
    });

    // Account management is reserved for Admin.
    Route::middleware('role:Admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
    });

    Route::middleware('role:Admin')->group(function () {
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
        Route::post('/users/{id}/phone-numbers', [UserController::class, 'addPhoneNumber']);
        Route::delete('/phone-numbers/{id}', [UserController::class, 'destroyPhoneNumber']);
    });
});

// LoRaWAN network server webhook — no browser session available here;
// protected by the X-LoRaWAN-Secret header instead (see LORAWAN_SHARED_SECRET).
Route::post('/lorawan/uplink', [LoraController::class, 'uplink']);

Route::post('/password/otp', [\App\Http\Controllers\PasswordController::class, 'requestOtp'])->middleware('throttle:3,1');
Route::post('/password/reset', [\App\Http\Controllers\PasswordController::class, 'reset'])->middleware('throttle:6,1');
Route::post('/password/change', [\App\Http\Controllers\PasswordController::class, 'change'])->middleware(['auth:sanctum', 'throttle:6,1']);
Route::middleware(['auth:sanctum', 'role:Admin', \App\Http\Middleware\FeatureAccess::class])->group(function () {
    Route::get('/access-control', [\App\Http\Controllers\Api\AccessController::class, 'index']);
    Route::put('/access-control', [\App\Http\Controllers\Api\AccessController::class, 'update']);
    Route::get('/activity-logs', [\App\Http\Controllers\Api\AccessController::class, 'logs']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'show']);
    Route::put('/settings', [\App\Http\Controllers\SettingsController::class, 'update'])->middleware('throttle:10,1');
});

Route::post('/password/verify', [\App\Http\Controllers\PasswordController::class, 'verifyOtp'])->middleware('throttle:6,1');
Route::post('/mobile/login/verify', [AuthController::class, 'verify'])->middleware('throttle:6,1');
Route::post('/mobile/login/resend', [AuthController::class, 'resend'])->middleware('throttle:3,1');
