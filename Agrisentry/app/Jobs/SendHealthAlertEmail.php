<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendHealthAlertEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 45;
    public function backoff(): array { return [60, 300]; }

    public function __construct(public int $alertId, public int $userId) {}

    public function handle(): void
    {
        $alert = Alert::with('goat')->find($this->alertId);
        $user = User::find($this->userId);
        if (!$alert || !$user || !in_array($user->role, ['Admin', 'Staff', 'Caretaker'], true)
            || !filter_var($user->email, FILTER_VALIDATE_EMAIL) || str_ends_with(strtolower($user->email), '.local')
            || strtolower($alert->status) !== 'active') return;

        $key = 'health-email:'.$alert->id.':'.hash('sha256', strtolower($user->email));
        Cache::lock($key.':lock', 60)->block(5, function () use ($key, $alert, $user) {
            if (Cache::has($key)) return;
            $animal = $alert->goat;
            $label = $animal?->name ?: 'Animal #'.$alert->goat_id;
            $body = "AgriSentry — Urgent health alert\n\n"
                ."Animal: {$label}\nEar tag: ".($animal?->ear_tag ?: $animal?->code ?: 'Not recorded')
                ."\nSeverity: {$alert->severity}\nFinding: {$alert->alert_type}"
                ."\nDetected: ".$alert->created_at->format('Y-m-d H:i:s T')
                ."\n\n{$alert->message}\n\nRecommended action:\n{$alert->recommendation}"
                ."\n\nPlease check the animal promptly and contact your veterinarian when needed."
                ."\nSensor findings are monitoring alerts, not a confirmed diagnosis."
                ."\n\nOpen AgriSentry to review the latest health and motion records.";
            Mail::raw($body, fn ($mail) => $mail->to($user->email)->subject('AgriSentry urgent alert: '.$alert->alert_type.' — '.$label));
            Cache::put($key, true, now()->addDays(7));
            Log::info('Health alert email accepted by mail transport.', ['alert_id' => $alert->id, 'user_id' => $user->id]);
        });
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Health alert email failed after retries.', ['alert_id' => $this->alertId, 'user_id' => $this->userId]);
    }
}
