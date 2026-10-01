<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginVerification
{
    public function channelFor(User $user): string
    {
        return filter_var($user->email, FILTER_VALIDATE_EMAIL) && !str_ends_with($user->email, '.local') ? 'email' : 'phone';
    }

    public function start(User $user, string $channel, string $device = 'mobile-device'): string
    {
        $code = (string) random_int(100000, 999999);
        $challenge = Str::random(64);
        if (!app(OtpDelivery::class)->send($user, $channel, $code, 'sign-in')) {
            throw ValidationException::withMessages(['channel' => 'Unable to send a code. Ask your Admin to check your registered contact and OTP delivery settings.']);
        }
        DB::table('login_otps')->updateOrInsert(['user_id' => $user->id], [
            'challenge_hash' => hash('sha256', $challenge), 'code_hash' => Hash::make($code),
            'password_hash' => $user->password, 'channel' => $channel, 'device_name' => $device,
            'attempts' => 0, 'expires_at' => now()->addMinutes(10),
        ]);
        return $challenge;
    }

    public function resend(string $challenge): string
    {
        $row = DB::table('login_otps')->where('challenge_hash', hash('sha256', $challenge))->first();
        $user = $row ? User::find($row->user_id) : null;
        // Expiry prevents using the old code, not requesting a replacement.
        // The pending challenge still proves that the password was checked.
        if (!$user || !hash_equals($row->password_hash, $user->password)) {
            throw ValidationException::withMessages(['otp' => 'This sign-in request is no longer active. Go back to sign in to request a new code.']);
        }
        return $this->start($user, $row->channel, $row->device_name);
    }

    public function isPending(string $challenge): bool
    {
        $row = DB::table('login_otps')->where('challenge_hash', hash('sha256', $challenge))->first();
        $user = $row ? User::find($row->user_id) : null;
        return $user && hash_equals($row->password_hash, $user->password);
    }

    public function verify(string $challenge, string $code): array
    {
        $result = DB::transaction(function () use ($challenge, $code) {
            $row = DB::table('login_otps')->where('challenge_hash', hash('sha256', $challenge))->lockForUpdate()->first();
            if (!$row || $row->attempts >= 5 || now()->greaterThanOrEqualTo($row->expires_at)) return null;
            if (!Hash::check($code, $row->code_hash)) {
                DB::table('login_otps')->where('id', $row->id)->increment('attempts');
                return null;
            }
            $user = User::find($row->user_id);
            DB::table('login_otps')->where('id', $row->id)->delete();
            if (!$user || !hash_equals($row->password_hash, $user->password)) return null;
            return [$user, $row->device_name];
        });
        if (!$result) $this->invalid();
        return $result;
    }

    private function invalid(): never {
        throw ValidationException::withMessages(['otp' => 'Invalid or expired code. After five unsuccessful attempts, request a new code or sign in again.']);
    }
}
