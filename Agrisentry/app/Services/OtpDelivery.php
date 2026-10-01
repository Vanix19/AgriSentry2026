<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class OtpDelivery
{
    public function send(User $user, string $channel, string $code, string $purpose): bool
    {
        $destination = $channel === 'email' ? $user->email : $user->phoneNumbers()->orderByDesc('is_primary')->value('phone_number');
        if (!$destination) return false;
        $message = "Your AgriSentry {$purpose} code is {$code}. It expires in 10 minutes.\n\nUse this latest code and ignore earlier codes.\nRequested at: ".now()->format('Y-m-d H:i:s').' '.config('app.timezone');
        try {
            if ($channel === 'email' && !str_ends_with($destination, '.local') && !in_array(config('mail.default'), ['log', 'array'])) {
                Mail::raw($message, fn ($mail) => $mail->to($destination)->subject("AgriSentry {$purpose} - ".now()->format('Y-m-d H:i:s')));
                Log::info('OTP email accepted by mail transport.', ['user_id' => $user->id, 'purpose' => $purpose]);
                return true;
            }
            if ($channel === 'phone' && config('services.semaphore.key')) {
                return Http::asForm()->timeout(15)->post('https://api.semaphore.co/api/v4/messages', [
                    'apikey' => config('services.semaphore.key'), 'number' => $destination,
                    'message' => $message, 'sendername' => config('services.semaphore.sender_name'),
                ])->successful();
            }
        } catch (\Throwable $e) {
            $detail = strtolower($e->getMessage());
            $reason = str_contains($detail, 'authenticate') || str_contains($detail, '535') ? 'authentication_rejected'
                : (str_contains($detail, 'certificate') ? 'tls_certificate_error'
                : (str_contains($detail, 'timed out') ? 'connection_timeout' : 'transport_error'));
            // Never log the provider exception: it can contain credentials or the code.
            Log::warning('OTP delivery failed.', ['user_id' => $user->id, 'channel' => $channel, 'reason' => $reason]);
        }
        return false;
    }
}
