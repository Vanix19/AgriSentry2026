<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\SmsNotification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsGatewayService
{
    /**
     * Broadcast an alert's recommendation to every registered Caretaker/Staff
     * phone number. The current schema has no per-goat caretaker assignment,
     * so broadcasting to all is the correct scope for a single small herd.
     */
    public function notifyForAlert(Alert $alert): void
    {
        $goat = $alert->goat;
        $goatLabel = $goat?->name ? "{$goat->name} (Goat #{$alert->goat_id})" : "Goat #{$alert->goat_id}";

        $message = mb_substr(trim(sprintf(
            'AgriSentry Alert: %s - %s. %s',
            $goatLabel,
            $alert->alert_type,
            $alert->recommendation ?? $alert->message
        )), 0, 900);

        $phones = User::whereIn('role', ['Caretaker', 'Staff'])
            ->with('phoneNumbers')
            ->get()
            ->flatMap(fn ($user) => $user->phoneNumbers)
            ->unique('phone_number');

        // Not using Collection::each() here: it stops iterating as soon as the
        // callback returns false, and send() returns false on skip/failure —
        // that would silently cut off notifications to the remaining phones.
        foreach ($phones as $phone) {
            $this->send($phone->phone_number, $message, $alert->id, $phone->id);
        }
    }

    public function send(string $number, string $message, ?int $alertId = null, ?int $phoneId = null): bool
    {
        $apiKey = config('services.semaphore.key');

        if (! $apiKey) {
            Log::info("SMS gateway not configured — would have texted {$number}: {$message}");

            SmsNotification::create([
                'alert_id' => $alertId,
                'caretaker_phone_number_id' => $phoneId,
                'phone_number' => $number,
                'message' => $message,
                'status' => 'Skipped',
                'provider_response' => 'SEMAPHORE_API_KEY not set in .env',
            ]);

            return false;
        }

        try {
            $response = Http::asForm()->timeout(15)->post('https://api.semaphore.co/api/v4/messages', [
                'apikey' => $apiKey,
                'number' => $number,
                'message' => $message,
                'sendername' => config('services.semaphore.sender_name'),
            ]);

            SmsNotification::create([
                'alert_id' => $alertId,
                'caretaker_phone_number_id' => $phoneId,
                'phone_number' => $number,
                'message' => $message,
                'status' => $response->successful() ? 'Sent' : 'Failed',
                'provider_response' => $response->body(),
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("SMS send failed to {$number}: {$e->getMessage()}");

            SmsNotification::create([
                'alert_id' => $alertId,
                'caretaker_phone_number_id' => $phoneId,
                'phone_number' => $number,
                'message' => $message,
                'status' => 'Failed',
                'provider_response' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
