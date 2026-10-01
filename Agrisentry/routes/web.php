<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoatProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect(auth()->check() ? '/species' : '/login');
});

Route::view('/species', 'species-select')->middleware('auth')->name('species');

Route::get('/species/goat', function () {
    return redirect('/agrisentry');
})->middleware('auth');

Route::get('/login', [AuthController::class, 'showLogin'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware(['guest', 'throttle:6,1']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

Route::get('/agrisentry', function () {
    return view('agrisentry');
})->middleware('auth')->name('dashboard');

Route::get('/goat/{id}/profile', [GoatProfileController::class, 'show'])
    ->middleware('auth')
    ->name('goat.profile.show');

Route::get('/admin/users', function () {
    return view('admin-users');
})->middleware(['auth', 'role:Admin']);

Route::get('/password/recovery', [\App\Http\Controllers\PasswordController::class, 'recovery'])->middleware('guest');
Route::post('/password/otp', [\App\Http\Controllers\PasswordController::class, 'requestOtp'])->middleware(['guest', 'throttle:3,1']);
Route::post('/password/reset', [\App\Http\Controllers\PasswordController::class, 'reset'])->middleware(['guest', 'throttle:6,1']);
Route::view('/password/change', 'auth.change-password')->middleware('auth');
Route::post('/password/change', [\App\Http\Controllers\PasswordController::class, 'change'])->middleware(['auth', 'throttle:6,1']);
Route::view('/admin/access', 'admin-access')->middleware(['auth', 'role:Admin']);

Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'show'])->middleware('auth');
Route::put('/settings', [\App\Http\Controllers\SettingsController::class, 'update'])->middleware(['auth', 'throttle:10,1']);

Route::post('/password/verify', [\App\Http\Controllers\PasswordController::class, 'verifyOtp'])->middleware('throttle:6,1');
Route::get('/login/otp', [AuthController::class, 'otp'])->middleware('guest');
Route::post('/login/otp', [AuthController::class, 'verify'])->middleware(['guest', 'throttle:6,1']);
Route::post('/login/otp/resend', [AuthController::class, 'resend'])->middleware(['guest', 'throttle:3,1']);
