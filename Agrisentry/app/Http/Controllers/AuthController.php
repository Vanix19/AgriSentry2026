<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = \App\Models\User::where('username', $validated['username'])->first();
        if (!$user || !\Illuminate\Support\Facades\Hash::check($validated['password'], $user->password)) {
            if ($request->expectsJson()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['username' => 'Invalid username or password.']);
            }
            return back()->withErrors(['username' => 'Invalid username or password.'])->onlyInput('username');
        }
        $channel = app(\App\Services\LoginVerification::class)->channelFor($user);
        $challenge = app(\App\Services\LoginVerification::class)->start($user, $channel);
        $request->session()->regenerate();
        $request->session()->put('login_challenge', $challenge);
        $request->session()->put('login_channel', $channel);
        if ($request->expectsJson()) {
            return response()->json(['redirect' => '/login/otp']);
        }
        return redirect('/login/otp')->with('status', 'Code sent to your registered '.($channel === 'email' ? 'email address.' : 'phone number.'));
    }

    public function otp(Request $request)
    {
        if (!app(\App\Services\LoginVerification::class)->isPending((string) $request->session()->get('login_challenge'))) {
            $request->session()->forget(['login_challenge', 'login_channel']);
            return redirect('/login')->with('status', 'Please sign in again to request a fresh OTP.');
        }
        return view('auth.login-otp');
    }

    public function resend(Request $request)
    {
        if (!app(\App\Services\LoginVerification::class)->isPending((string) $request->session()->get('login_challenge'))) {
            $request->session()->forget(['login_challenge', 'login_channel']);
            $message = 'This sign-in request has ended. Please sign in again to receive a fresh OTP.';
            if ($request->expectsJson()) return response()->json(['message' => $message, 'redirect' => '/login'], 409);
            return redirect('/login')->with('status', $message);
        }
        $challenge = app(\App\Services\LoginVerification::class)->resend((string) $request->session()->get('login_challenge'));
        $request->session()->put('login_challenge', $challenge);
        if ($request->expectsJson()) return response()->json(['message' => 'A new code has been sent. Use the latest code.']);
        return redirect('/login/otp')->with('status', 'A new code has been sent. Use the latest code.');
    }

    public function verify(Request $request)
    {
        $data = $request->validate(['otp' => 'required|digits:6']);
        [$user] = app(\App\Services\LoginVerification::class)->verify((string) $request->session()->get('login_challenge'), $data['otp']);
        Auth::login($user);
        $request->session()->forget(['login_challenge', 'login_channel']);
        $request->session()->regenerate();
        \App\Services\AccountAccess::record($user, 'Login', $request);
        if ($user->password_change_required) return redirect('/password/change');
        $request->session()->forget('url.intended');
        return redirect('/species');
    }

    public function logout(Request $request)
    {
        \App\Services\AccountAccess::record(Auth::user(), 'Logout', $request);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
