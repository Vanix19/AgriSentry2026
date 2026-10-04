<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\OtpDelivery;
use App\Support\DeploymentErrors;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RenderBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_semaphore_key_rejects_recovery_without_claiming_delivery(): void
    {
        config(['services.semaphore.key' => null]);
        Http::fake();
        Log::spy();
        $user = User::factory()->create(['username' => 'recovery']);
        $user->phoneNumbers()->create(['phone_number' => '09171234567', 'is_primary' => true]);
        $this->postJson('/password/otp', ['username' => 'recovery', 'channel' => 'phone'])
            ->assertUnprocessable()->assertJsonValidationErrors('channel');
        $this->assertDatabaseCount('password_otps', 0);
        Http::assertNothingSent();
        Log::shouldHaveReceived('error')->with(
            'OTP delivery unavailable: configure a real mail transport or SEMAPHORE_API_KEY.', ['channel' => 'phone']
        )->once();
    }

    public function test_provider_error_in_http_200_is_not_successful_delivery(): void
    {
        config(['services.semaphore.key' => 'test-key']);
        Http::fake(['api.semaphore.co/*' => Http::response(['error' => 'Invalid API key'], 200)]);
        $user = User::factory()->create();
        $user->phoneNumbers()->create(['phone_number' => '09171234567', 'is_primary' => true]);
        $this->assertFalse(app(OtpDelivery::class)->send($user, 'phone', '123456', 'password reset'));
    }

    public function test_database_failures_log_driver_details_without_sql_bindings(): void
    {
        Log::spy();
        $error = new QueryException('mysql', 'select * from users where password = ?', ['sensitive-password'],
            new \PDOException('SQLSTATE[HY000] [2002] Connection refused'));
        DeploymentErrors::log('Database failed.', $error);
        Log::shouldHaveReceived('error')->withArgs(function ($message, $context) {
            $this->assertStringContainsString('Connection refused', $context['message']);
            $this->assertStringNotContainsString('sensitive-password', json_encode($context));
            $this->assertArrayHasKey('file', $context);
            $this->assertArrayHasKey('line', $context);
            return $message === 'Database failed.';
        })->once();
    }

    public function test_query_failure_returns_503_for_json_and_500_for_html_and_is_logged(): void
    {
        Log::spy();
        User::resolveConnection()->beforeExecuting(function ($query) {
            if (str_starts_with($query, 'select') && str_contains($query, 'users')) {
                throw new QueryException('mysql', 'select ?', ['sensitive-value'],
                    new \PDOException('SQLSTATE[HY000] [2002] Connection refused'));
            }
        });
        $this->postJson('/login', ['username' => 'login', 'password' => 'secret'])->assertStatus(503);
        $this->post('/password/otp', ['username' => 'recovery', 'channel' => 'email'])->assertStatus(500);
        Log::shouldHaveReceived('error')->withArgs(function ($message, $context) {
            return $message === 'Backend request failed.'
                && str_contains($context['message'], 'Connection refused')
                && !str_contains(json_encode($context), 'sensitive-value');
        })->twice();
    }

    public function test_missing_firebase_credentials_is_logged_and_returns_controlled_error(): void
    {
        config(['firebase.enabled' => true, 'firebase.credentials' => '/nonexistent/firebase-credentials.json']);
        Sanctum::actingAs(User::factory()->create());
        Log::spy();
        $this->postJson('/api/firebase/session')->assertStatus(503);
        Log::shouldHaveReceived('error')->with(
            'Firebase credentials missing or unreadable. Set FIREBASE_CREDENTIALS to the mounted Render secret file.'
        )->once();
    }
}
