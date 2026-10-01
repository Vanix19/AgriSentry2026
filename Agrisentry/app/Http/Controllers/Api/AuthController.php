<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:255',
        ]);

        $user = \App\Models\User::where('username', $credentials['username'])->first();
        if (!$user || !\Illuminate\Support\Facades\Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['username' => 'Invalid username or password.']);
        }
        $channel = app(\App\Services\LoginVerification::class)->channelFor($user);
        $challenge = app(\App\Services\LoginVerification::class)->start($user, $channel, $credentials['device_name'] ?? 'mobile-device');
        return response()->json(['challenge' => $challenge, 'message' => 'Code sent to your registered contact.']);
    }

    public function resend(Request $request)
    {
        $data = $request->validate(['challenge' => 'required|string|size:64']);
        $challenge = app(\App\Services\LoginVerification::class)->resend($data['challenge']);
        return response()->json(['challenge' => $challenge, 'message' => 'A new code has been sent. Use the latest code.']);
    }

    public function verify(Request $request)
    {
        $data = $request->validate(['challenge' => 'required|string|size:64', 'otp' => 'required|digits:6']);
        [$user, $deviceName] = app(\App\Services\LoginVerification::class)->verify($data['challenge'], $data['otp']);
        \App\Services\AccountAccess::record($user, 'Login', $request);
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role,
                'password_change_required' => (bool) $user->password_change_required,
                'permissions' => $user->isAdmin() ? array_fill_keys(array_keys(\App\Services\AccountAccess::defaults('Staff')), true) : \App\Services\AccountAccess::permissions($user->role),
            ],
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role,
                'password_change_required' => (bool) $user->password_change_required,
                'permissions' => $user->isAdmin() ? array_fill_keys(array_keys(\App\Services\AccountAccess::defaults('Staff')), true) : \App\Services\AccountAccess::permissions($user->role),
            ],
        ]);
    }

    public function logout(Request $request)
    {
        \App\Services\AccountAccess::record($request->user(), 'Logout', $request);
        $token = $request->user()->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json(['message' => 'Logged out.']);
    }
}
