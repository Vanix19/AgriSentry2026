<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AccountAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    public function recovery(Request $request)
    {
        if ($request->boolean('restart')) $request->session()->forget('password_recovery');
        return view('auth.recovery', ['recovery' => $request->session()->get('password_recovery')]);
    }

    public function requestOtp(Request $request)
    {
        $data = $request->validate(['username' => 'required|string|max:100', 'channel' => 'required|in:email,phone']);
        $user = User::where('username', $data['username'])->first();
        if ($user) {
            $code = (string) random_int(100000, 999999);
            $sent = app(\App\Services\OtpDelivery::class)->send($user, $data['channel'], $code, 'password reset');
            if (!$sent) throw ValidationException::withMessages([
                'channel' => 'Unable to send a code. Ask your Admin to check your registered contact and OTP delivery settings.',
            ]);
            if ($sent) DB::table('password_otps')->updateOrInsert(['user_id' => $user->id], [
                'reset_token_hash' => null, 'code_hash' => Hash::make($code), 'attempts' => 0, 'expires_at' => now()->addMinutes(10)]);
        }
        $message = 'If the account has a registered contact, a reset code will be sent.';
        if (!$request->expectsJson()) {
            $request->session()->put('password_recovery', $data);
            return redirect('/password/recovery')->with('status', $message);
        }
        return $this->respond($request, $message);
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate(['username' => 'required|string', 'otp' => 'required|digits:6']);
        $token = \Illuminate\Support\Str::random(64);
        $valid = DB::transaction(function () use ($data, $token) {
            $user = User::where('username', $data['username'])->first();
            $otp = $user ? DB::table('password_otps')->where('user_id', $user->id)->lockForUpdate()->first() : null;
            if (!$otp || $otp->reset_token_hash || $otp->attempts >= 5 || now()->greaterThanOrEqualTo($otp->expires_at)) return false;
            if (!Hash::check($data['otp'], $otp->code_hash)) {
                DB::table('password_otps')->where('id', $otp->id)->increment('attempts');
                return false;
            }
            DB::table('password_otps')->where('id', $otp->id)->update(['reset_token_hash' => hash('sha256', $token), 'expires_at' => now()->addMinutes(10)]);
            return true;
        });
        if (!$valid) throw ValidationException::withMessages(['otp' => 'Invalid or expired code. Request a new code after five unsuccessful attempts.']);
        if ($request->expectsJson()) return response()->json(['message' => 'Code verified. Choose a new password.', 'reset_token' => $token]);
        $request->session()->put('password_recovery.username', $data['username']);
        $request->session()->put('password_recovery.reset_token', $token);
        return redirect('/password/recovery')->with('status', 'Code verified. Choose a new password.');
    }

    public function reset(Request $request)
    {
        if (!$request->expectsJson()) $request->merge([
            'username' => $request->session()->get('password_recovery.username'),
            'reset_token' => $request->session()->get('password_recovery.reset_token'),
        ]);
        $data = $request->validate(['username' => 'required|string', 'reset_token' => 'required|string|size:64', 'password' => 'required|string|min:8|confirmed']);
        $valid = DB::transaction(function () use ($data, $request) {
            $user = User::where('username', $data['username'])->first();
            $otp = $user ? DB::table('password_otps')->where('user_id', $user->id)->lockForUpdate()->first() : null;
            if (!$otp || !$otp->reset_token_hash || now()->greaterThanOrEqualTo($otp->expires_at) || !hash_equals($otp->reset_token_hash, hash('sha256', $data['reset_token']))) return false;
            $user->forceFill(['password' => $data['password'], 'password_change_required' => false, 'remember_token' => null])->save();
            $user->tokens()->delete();
            if (config('session.driver') === 'database') DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            DB::table('login_otps')->where('user_id', $user->id)->delete();
            DB::table('password_otps')->where('id', $otp->id)->delete();
            AccountAccess::record($user, 'Password reset', $request);
            return true;
        });
        if (!$valid) throw ValidationException::withMessages(['reset_token' => 'Password reset authorization expired. Start again to request a new code.']);
        if (!$request->expectsJson()) {
            $request->session()->forget('password_recovery');
            return redirect('/login')->with('status', 'Password reset successfully. Sign in with your new password.');
        }
        return $this->respond($request, 'Password reset. You can now sign in.');
    }

    public function change(Request $request)
    {
        $data = $request->validate(['current_password' => 'required|string', 'password' => 'required|string|min:8|confirmed']);
        $user = $request->user();
        if (!Hash::check($data['current_password'], $user->password)) throw ValidationException::withMessages(['current_password' => 'Current password is incorrect.']);
        $user->forceFill(['password' => $data['password'], 'password_change_required' => false, 'remember_token' => null])->save();
        DB::table('password_otps')->where('user_id', $user->id)->delete();
        $user->tokens()->delete();
        if (config('session.driver') === 'database') DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        if ($request->hasSession()) $request->session()->regenerate();
        AccountAccess::record($user, 'Password changed', $request);
        return $this->respond($request, 'Password changed. Sign in again on your other devices.');
    }
    private function respond(Request $request, string $message) {
        return $request->expectsJson() ? response()->json(['message' => $message]) : back()->with('status', $message);
    }
}
