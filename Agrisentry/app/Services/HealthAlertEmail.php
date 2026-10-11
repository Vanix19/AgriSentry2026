<?php

namespace App\Services;

use App\Jobs\SendHealthAlertEmail;
use App\Models\Alert;
use App\Models\User;

class HealthAlertEmail
{
    public function notify(Alert $alert): void
    {
        if (strtolower($alert->status) !== 'active' || !in_array(strtolower($alert->severity), ['high', 'urgent', 'critical'], true)) return;

        $connection = config('mail.alert_queue_connection');
        User::whereIn('role', ['Admin', 'Staff', 'Caretaker'])->get()
            ->filter(fn ($user) => filter_var($user->email, FILTER_VALIDATE_EMAIL) && !str_ends_with(strtolower($user->email), '.local'))
            ->unique(fn ($user) => strtolower($user->email))
            ->each(function ($user) use ($alert, $connection) {
                $job = SendHealthAlertEmail::dispatch($alert->id, $user->id)->afterCommit();
                $connection === 'sync' ? $job->afterResponse() : $job->onConnection($connection)->onQueue('email-alerts');
            });
    }
}
