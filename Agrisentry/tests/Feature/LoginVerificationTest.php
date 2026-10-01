<?php
namespace Tests\Feature;

use App\Models\User;
use App\Services\OtpDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_async_login_returns_validation_errors_and_otp_destination(): void
    {
        $this->mock(OtpDelivery::class)->shouldReceive('send')->once()->andReturn(true);
        $user = User::factory()->create(['username' => 'async-user', 'password' => 'password']);
        $this->postJson('/login', ['username' => $user->username, 'password' => 'incorrect'])
            ->assertUnprocessable()->assertJsonValidationErrors('username');
        $this->postJson('/login', ['username' => $user->username, 'password' => 'password'])
            ->assertOk()->assertJsonPath('redirect', '/login/otp')->assertSessionHas('login_challenge');
        $this->assertGuest();
    }

    public function test_livestock_selection_requires_login_and_goat_opens_dashboard(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/species')->assertRedirect('/login');
        $this->get('/species/goat')->assertRedirect('/login');
        $user = User::factory()->create(['password_change_required' => false]);
        $this->actingAs($user)->get('/species')->assertOk()->assertSee('Back to sign in');
        $this->get('/species/goat')->assertRedirect('/agrisentry');
        $this->get('/login')->assertRedirect('/species');
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/species')->assertRedirect('/login');
    }

    public function test_verified_login_shows_livestock_even_after_a_dashboard_deep_link(): void
    {
        $this->mock(OtpDelivery::class)->shouldReceive('send')->once()->andReturn(true);
        $user = User::factory()->create(['username' => 'picker-user', 'password' => 'password', 'password_change_required' => false]);
        $this->get('/agrisentry')->assertRedirect('/login');
        $this->post('/login', ['username' => $user->username, 'password' => 'password'])->assertRedirect('/login/otp');
        $this->assertGuest();
        DB::table('login_otps')->where('user_id', $user->id)->update(['code_hash' => Hash::make('123456')]);
        $this->post('/login/otp', ['otp' => '000000'])->assertSessionHasErrors('otp');
        $this->assertGuest();
        $this->post('/login/otp', ['otp' => '123456'])->assertRedirect('/species')->assertSessionMissing('url.intended');
        $this->assertAuthenticatedAs($user);
    }

    public function test_stale_browser_challenge_returns_to_sign_in_without_sending(): void
    {
        $this->mock(OtpDelivery::class)->shouldNotReceive('send');
        $this->withSession(['login_challenge' => str_repeat('x', 64)])
            ->postJson('/login/otp/resend')->assertStatus(409)->assertJsonPath('redirect', '/login')
            ->assertSessionMissing('login_challenge');
        $this->withSession(['login_challenge' => str_repeat('x', 64)])
            ->get('/login/otp')->assertRedirect('/login');
    }

    public function test_web_resend_returns_json_and_updates_pending_session(): void
    {
        $this->mock(OtpDelivery::class)->shouldReceive('send')->twice()->andReturn(true);
        $user = User::factory()->create(['username' => 'json-resend', 'password' => 'password']);
        $this->post('/login', ['username' => $user->username, 'password' => 'password'])->assertRedirect('/login/otp');
        $old = session('login_challenge');
        $this->postJson('/login/otp/resend')->assertOk()->assertJsonPath('message', 'A new code has been sent. Use the latest code.');
        $this->assertNotSame($old, session('login_challenge'));
        $this->assertGuest();
    }

    public function test_web_resend_sends_a_fresh_code_after_expiry_and_lockout(): void
    {
        $codes = [];
        $this->mock(OtpDelivery::class)->shouldReceive('send')->twice()->andReturnUsing(function ($user, $channel, $code) use (&$codes) {
            $codes[] = $code;
            return true;
        });
        $user = User::factory()->create(['username' => 'resend-user', 'password' => 'password']);
        $this->post('/login', ['username' => $user->username, 'password' => 'password'])->assertRedirect('/login/otp');
        $oldChallenge = session('login_challenge');
        DB::table('login_otps')->where('user_id', $user->id)->update(['attempts' => 5]);
        $this->travel(11)->minutes();
        $this->post('/login/otp/resend')->assertRedirect('/login/otp')->assertSessionHasNoErrors();
        $this->assertNotSame($oldChallenge, session('login_challenge'));
        $this->assertDatabaseHas('login_otps', ['user_id' => $user->id, 'attempts' => 0]);
        $this->get('/login/otp')->assertOk()->assertSee('A new code has been sent')->assertDontSee('Back to livestock selection');
        $this->post('/login/otp', ['otp' => $codes[1]])->assertRedirect('/password/change');
        $this->assertAuthenticatedAs($user);
    }

    public function test_resend_cannot_restore_a_challenge_after_password_change(): void
    {
        $this->mock(OtpDelivery::class)->shouldReceive('send')->once()->andReturn(true);
        $user = User::factory()->create(['username' => 'resend-user', 'password' => 'password']);
        $challenge = $this->postJson('/api/mobile/login', ['username' => $user->username, 'password' => 'password'])->assertOk()->json('challenge');
        $user->forceFill(['password' => 'changed-password'])->save();
        $this->postJson('/api/mobile/login/resend', ['challenge' => $challenge])->assertUnprocessable();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_mobile_only_issues_token_after_otp_and_rejects_replay(): void
    {
        $this->mock(OtpDelivery::class)->shouldReceive('send')->once()->andReturn(true);
        $user = User::factory()->create(['username' => 'staff', 'password' => 'password', 'role' => 'Staff']);
        $response = $this->postJson('/api/mobile/login', ['username' => 'staff', 'password' => 'password', 'role' => 'Admin'])
            ->assertOk()->assertJsonMissingPath('token');
        $this->assertGuest();
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->getJson('/api/mobile/me')->assertUnauthorized();
        $this->assertDatabaseHas('login_otps', ['user_id' => $user->id, 'channel' => 'email']);
        $challenge = $response->json('challenge');
        DB::table('login_otps')->update(['code_hash' => Hash::make('123456')]);
        $this->postJson('/api/mobile/login/verify', ['challenge' => $challenge, 'otp' => '000000'])->assertUnprocessable();
        $this->postJson('/api/mobile/login/verify', ['challenge' => $challenge, 'otp' => '123456'])->assertOk()->assertJsonPath('user.role', 'Staff')->assertJsonStructure(['token']);
        $this->postJson('/api/mobile/login/verify', ['challenge' => $challenge, 'otp' => '123456'])->assertUnprocessable();
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'action' => 'Login']);
    }

    public function test_login_fails_closed_when_delivery_is_unavailable(): void
    {
        $this->mock(OtpDelivery::class)->shouldReceive('send')->once()->andReturn(false);
        User::factory()->create(['username' => 'staff', 'password' => 'password']);
        $this->post('/login', ['username' => 'staff', 'password' => 'password', 'channel' => 'email'])->assertSessionHasErrors('channel');
        $this->assertGuest();
        $this->assertDatabaseCount('login_otps', 0);
        $this->get('/login/otp')->assertRedirect('/login');
    }

    public function test_resend_replaces_challenge_and_codes_expire_and_lock(): void
    {
        $this->mock(OtpDelivery::class)->shouldReceive('send')->twice()->andReturn(true);
        User::factory()->create(['username' => 'staff', 'password' => 'password']);
        $challenge = $this->postJson('/api/mobile/login', ['username' => 'staff', 'password' => 'password', 'channel' => 'email'])->assertOk()->json('challenge');
        $new = $this->postJson('/api/mobile/login/resend', ['challenge' => $challenge])->assertOk()->json('challenge');
        DB::table('login_otps')->update(['code_hash' => Hash::make('123456'), 'attempts' => 4]);
        $this->postJson('/api/mobile/login/verify', ['challenge' => $challenge, 'otp' => '123456'])->assertUnprocessable();
        $this->postJson('/api/mobile/login/verify', ['challenge' => $new, 'otp' => '000000'])->assertUnprocessable();
        $this->assertDatabaseHas('login_otps', ['attempts' => 5]);
        $this->postJson('/api/mobile/login/verify', ['challenge' => $new, 'otp' => '123456'])->assertUnprocessable();
        DB::table('login_otps')->update(['attempts' => 0, 'expires_at' => now()->subMinute()]);
        $this->postJson('/api/mobile/login/verify', ['challenge' => $new, 'otp' => '123456'])->assertUnprocessable();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_reset_grant_is_bound_to_account_and_expires(): void
    {
        $user = User::factory()->create(['username' => 'reset-user']);
        User::factory()->create(['username' => 'other-user']);
        DB::table('password_otps')->insert(['user_id' => $user->id, 'code_hash' => Hash::make('123456'), 'expires_at' => now()->addMinutes(10)]);
        $token = $this->postJson('/api/password/verify', ['username' => 'reset-user', 'otp' => '123456'])->assertOk()->json('reset_token');
        $payload = ['username' => 'other-user', 'reset_token' => $token, 'password' => 'new-password', 'password_confirmation' => 'new-password'];
        $this->postJson('/api/password/reset', $payload)->assertUnprocessable();
        $this->travel(11)->minutes();
        $payload['username'] = 'reset-user';
        $this->postJson('/api/password/reset', $payload)->assertUnprocessable();
        $this->assertFalse(Hash::check('new-password', $user->fresh()->password));
    }
}
