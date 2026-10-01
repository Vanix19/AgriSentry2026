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

        User::whereIn('role', ['Admin', 'Staff', 'Caretaker'])->get()
            ->filter(fn ($user) => filter_var($user->email, FILTER_VALIDATE_EMAIL) && !str_ends_with(strtolower($user->email), '.local'))
            ->unique(fn ($user) => strtolower($user->email))
            ->each(fn ($user) => SendHealthAlertEmail::dispatch($alert->id, $user->id)->onConnection('database')->onQueue('email-alerts')->afterCommit());
    }
}
