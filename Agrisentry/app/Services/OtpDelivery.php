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
        if (!$destination) {
            Log::error('OTP delivery unavailable: account has no registered contact.', ['channel' => $channel]);
            return false;
        }
        $message = "Your AgriSentry {$purpose} code is {$code}. It expires in 10 minutes.\n\nUse this latest code and ignore earlier codes.\nRequested at: ".now()->format('Y-m-d H:i:s').' '.config('app.timezone');
        try {
            if ($channel === 'email' && !str_ends_with($destination, '.local') && !in_array(config('mail.default'), ['log', 'array'])) {
                Mail::raw($message, fn ($mail) => $mail->to($destination)->subject("AgriSentry {$purpose} - ".now()->format('Y-m-d H:i:s')));
                Log::info('OTP email accepted by mail transport.', ['user_id' => $user->id, 'purpose' => $purpose]);
                return true;
            }
            if ($channel === 'phone' && config('services.semaphore.key')) {
                $response = Http::asForm()->timeout(15)->post('https://api.semaphore.co/api/v4/messages', [
                    'apikey' => config('services.semaphore.key'), 'number' => $destination,
                    'message' => $message, 'sendername' => config('services.semaphore.sender_name'),
                ]);
                $accepted = $response->successful() && is_array($response->json())
                    && in_array($response->json('0.status'), ['Pending', 'Queued', 'Sent'], true);
                if (!$accepted) Log::error('Semaphore rejected OTP delivery.', ['http_status' => $response->status()]);
                return $accepted;
            }
            Log::error('OTP delivery unavailable: configure a real mail transport or SEMAPHORE_API_KEY.', ['channel' => $channel]);
        } catch (\Throwable $e) {
            $detail = strtolower($e->getMessage());
            $reason = str_contains($detail, 'authenticate') || str_contains($detail, '535') ? 'authentication_rejected'
                : (str_contains($detail, 'certificate') ? 'tls_certificate_error'
                : (str_contains($detail, 'timed out') ? 'connection_timeout' : 'transport_error'));
            // Remove the generated OTP and destination before sanitizing provider details.
            $safe = new \RuntimeException(str_replace([$code, $destination], '[redacted]', $e->getMessage()));
            \App\Support\DeploymentErrors::log('OTP delivery failed.', $safe, [
                'channel' => $channel, 'reason' => $reason,
                'origin_type' => get_class($e), 'origin_file' => $e->getFile(), 'origin_line' => $e->getLine(),
            ]);
        }
        return false;
    }
}
