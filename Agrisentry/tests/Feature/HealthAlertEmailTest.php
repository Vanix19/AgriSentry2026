<?php

namespace Tests\Feature;

use App\Jobs\SendHealthAlertEmail;
use App\Models\Alert;
use App\Models\Goat;
use App\Models\User;
use App\Services\HealthAlertEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HealthAlertEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_urgent_alerts_queue_registered_users_and_repeated_readings_do_not_resend(): void
    {
        Queue::fake();
        foreach (['Admin', 'Staff', 'Caretaker'] as $role) User::factory()->create(['role' => $role]);
        User::factory()->create(['role' => 'Staff', 'email' => 'staff@agrisentry.local']);
        $goat = Goat::create(['name' => 'Test animal', 'code' => 'EMAIL-01', 'temperature' => 40]);
        $service = app(HealthAlertEvaluator::class);
        $service->evaluate($goat);
        $service->evaluate($goat);
        Queue::assertPushed(SendHealthAlertEmail::class, 3);
        Queue::assertPushedOn('email-alerts', SendHealthAlertEmail::class);
        $service->raiseMotionAlert($goat, 'Prolonged Inactivity');
        Queue::assertPushed(SendHealthAlertEmail::class, 6);
    }

    public function test_warning_alerts_do_not_email_but_critical_alerts_do(): void
    {
        Queue::fake();
        User::factory()->create(['role' => 'Admin']);
        $goat = Goat::create(['name' => 'Test animal', 'code' => 'EMAIL-02']);
        Alert::create(['goat_id' => $goat->id, 'alert_type' => 'Low Battery', 'message' => 'Low battery', 'severity' => 'Medium', 'status' => 'Active']);
        Queue::assertNothingPushed();
        Alert::create(['goat_id' => $goat->id, 'alert_type' => 'Critical finding', 'message' => 'Check animal', 'severity' => 'Critical', 'status' => 'Active']);
        Queue::assertPushed(SendHealthAlertEmail::class, 1);
    }

    public function test_delivery_contains_finding_and_is_not_repeated_for_same_recipient(): void
    {
        Queue::fake();
        $user = User::factory()->create(['role' => 'Admin']);
        $goat = Goat::create(['name' => 'Test animal', 'code' => 'EMAIL-03']);
        $alert = app(HealthAlertEvaluator::class)->raiseMotionAlert($goat, 'Excessive Movement');
        Mail::shouldReceive('raw')->once()->withArgs(fn ($body, $callback) => str_contains($body, 'Excessive Movement') && str_contains($body, 'EMAIL-03') && str_contains($body, 'Recommended action:'));
        $job = new SendHealthAlertEmail($alert->id, $user->id);
        $job->handle();
        $job->handle();
    }
}
