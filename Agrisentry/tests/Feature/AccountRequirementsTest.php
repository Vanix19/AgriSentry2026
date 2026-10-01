<?php
namespace Tests\Feature;

use App\Models\User;
use App\Models\Goat;
use App\Models\HealthLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountRequirementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_recovery_shows_one_step_at_a_time(): void
    {
        $user = User::factory()->create(['username' => 'recovery-user']);
        $this->get('/password/recovery')->assertOk()->assertSee('Send OTP')->assertDontSee('id="otp"', false);
        $this->from('/password/recovery')->post('/password/otp', ['username' => $user->username, 'channel' => 'email'])
            ->assertRedirect('/password/recovery')->assertSessionHas('password_recovery.username', $user->username);
        $this->get('/password/recovery')->assertOk()->assertSee('id="otp"', false)->assertDontSee('id="channel"', false)->assertDontSee('id="password"', false);
        $this->from('/password/recovery')->post('/password/verify', ['username' => $user->username, 'otp' => '000000', 'password' => 'new-password', 'password_confirmation' => 'new-password'])
            ->assertSessionHasErrors('otp');
        $this->get('/password/recovery')->assertSee('id="otp"', false);
        DB::table('password_otps')->insert(['user_id' => $user->id, 'code_hash' => Hash::make('123456'), 'expires_at' => now()->addMinutes(10)]);
        $this->post('/password/verify', ['username' => $user->username, 'otp' => '123456'])->assertRedirect('/password/recovery');
        $this->get('/password/recovery')->assertSee('Step 3 of 3')->assertDontSee('id="otp"', false)->assertSee('id="password"', false);
        $this->post('/password/reset', ['username' => $user->username, 'otp' => '123456', 'password' => 'new-password', 'password_confirmation' => 'new-password'])
            ->assertRedirect('/login')->assertSessionMissing('password_recovery');
        $this->get('/login')->assertSee('Password reset successfully')->assertSee('id="login-form"', false);
        $this->assertGuest();
        $this->get('/species')->assertRedirect('/login');
    }

    public function test_recovery_can_return_to_account_selection(): void
    {
        $this->withSession(['password_recovery' => ['username' => 'someone', 'channel' => 'email']])
            ->get('/password/recovery?restart=1')->assertOk()->assertSessionMissing('password_recovery')
            ->assertSee('id="channel"', false)->assertDontSee('id="otp"', false);
    }

    public function test_account_and_profile_pages_render(): void
    {
        $this->get('/login')->assertOk()->assertDontSee('name="role"', false);
        $this->get('/password/recovery')->assertOk();
        $this->get('/species/goat')->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $this->get('/password/change')->assertOk();
        $this->get('/admin/access')->assertOk();
        $this->get('/admin/users')->assertOk();
        $this->get('/agrisentry')->assertOk();
        $goat = Goat::create(['name' => 'Test', 'code' => 'GT-001']);
        $this->get('/goat/'.$goat->id.'/profile')->assertOk()->assertSee('Goat Color');
    }

    public function test_login_uses_assigned_role_and_records_login(): void
    {
        $user = User::factory()->create(['username' => 'staff', 'role' => 'Staff', 'password' => 'initial-password']);
        $this->mock(\App\Services\OtpDelivery::class)->shouldReceive('send')->once()->andReturn(true);
        $this->post('/login', ['username' => 'staff', 'password' => 'initial-password', 'role' => 'Admin', 'channel' => 'email'])->assertRedirect('/login/otp');
        $this->assertGuest();
        $this->assertDatabaseMissing('activity_logs', ['action' => 'Login']);
        DB::table('login_otps')->where('user_id', $user->id)->update(['code_hash' => Hash::make('123456')]);
        $this->post('/login/otp', ['otp' => '123456'])->assertRedirect('/password/change');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'action' => 'Login']);
    }

    public function test_admin_can_grant_and_revoke_caretaker_writes(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $caretaker = User::factory()->create(['role' => 'Caretaker']);
        $this->actingAs($caretaker)->postJson('/api/goats', ['name' => 'Test', 'code' => 'GT-001'])->assertForbidden();
        $this->actingAs($admin)->putJson('/api/access-control', ['role' => 'Caretaker', 'permissions' => ['goats.write' => true]])->assertOk();
        $this->actingAs($caretaker)->postJson('/api/goats', ['name' => 'Test', 'code' => 'GT-001', 'breed' => 'Native', 'ear_tag' => 'E-4', 'color' => 'Brown'])->assertCreated();
        $this->actingAs($caretaker)->getJson('/api/activity-logs')->assertForbidden();
        $this->actingAs($caretaker)->postJson('/api/users', [])->assertForbidden();
        $this->actingAs($admin)->putJson('/api/access-control', ['role' => 'Caretaker', 'permissions' => ['goats.write' => false]])->assertOk();
        $this->actingAs($caretaker)->deleteJson('/api/goats/1')->assertForbidden();
    }

    public function test_registration_validates_id_and_preserves_logbook_fields(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $this->postJson('/api/goats', ['name' => 'Test', 'code' => 'bad'])->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->postJson('/api/goats', ['name' => 'Test', 'code' => 'GT-002', 'breed' => 'Native', 'ear_tag' => '123', 'color' => 'White'])->assertCreated();
        $this->assertDatabaseHas('goats', ['code' => '123', 'ear_tag' => '123', 'color' => 'White']);
    }

    public function test_otp_is_single_use_and_revokes_tokens(): void
    {
        $user = User::factory()->create(['username' => 'reset-user']);
        $user->createToken('old-device');
        DB::table('password_otps')->insert(['user_id' => $user->id, 'code_hash' => Hash::make('123456'), 'attempts' => 0, 'expires_at' => now()->addMinutes(10)]);
        $payload = ['username' => 'reset-user', 'otp' => '123456', 'password' => 'new-password', 'password_confirmation' => 'new-password'];
        $this->postJson('/api/password/reset', $payload)->assertUnprocessable();
        $payload['reset_token'] = $this->postJson('/api/password/verify', $payload)->assertOk()->json('reset_token');
        $this->postJson('/api/password/verify', $payload)->assertUnprocessable();
        $this->postJson('/api/password/reset', $payload)->assertOk();
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertFalse($user->fresh()->password_change_required);
        $this->assertSame(0, $user->tokens()->count());
        $this->postJson('/api/password/reset', $payload)->assertUnprocessable();
    }

    public function test_phone_otp_delivery_and_reset_use_registered_contact(): void
    {
        config(['services.semaphore.key' => 'test-key']);
        \Illuminate\Support\Facades\Http::fake(['api.semaphore.co/*' => \Illuminate\Support\Facades\Http::response([['status' => 'Pending']], 200)]);
        $user = User::factory()->create(['username' => 'phone-user']);
        $user->phoneNumbers()->create(['phone_number' => '09171234567', 'is_primary' => true]);
        $this->postJson('/api/password/otp', ['username' => 'phone-user', 'channel' => 'phone', 'phone' => 'untrusted-number'])->assertOk();
        $sent = \Illuminate\Support\Facades\Http::recorded()->first()[0];
        $this->assertSame('09171234567', $sent['number']);
        preg_match('/\b([0-9]{6})\b/', $sent['message'], $match);
        $this->assertTrue(Hash::check($match[1], DB::table('password_otps')->value('code_hash')));
        $this->assertDatabaseCount('sms_notifications', 0);
        $token = $this->postJson('/api/password/verify', ['username' => 'phone-user', 'otp' => $match[1]])->assertOk()->json('reset_token');
        $this->postJson('/api/password/reset', ['reset_token' => $token, 'username' => 'phone-user', 'otp' => $match[1], 'password' => 'new-password', 'password_confirmation' => 'new-password'])->assertOk();
    }

    public function test_wrong_codes_lock_otp_and_expired_codes_are_rejected(): void
    {
        $user = User::factory()->create(['username' => 'locked-user']);
        DB::table('password_otps')->insert(['user_id' => $user->id, 'code_hash' => Hash::make('123456'), 'attempts' => 4, 'expires_at' => now()->addMinutes(10)]);
        $payload = ['username' => 'locked-user', 'otp' => '000000', 'password' => 'new-password', 'password_confirmation' => 'new-password'];
        $this->postJson('/api/password/verify', $payload)->assertUnprocessable();
        $payload['otp'] = '123456';
        $this->postJson('/api/password/verify', $payload)->assertUnprocessable();
        DB::table('password_otps')->where('user_id', $user->id)->update(['attempts' => 0, 'expires_at' => now()->subMinute()]);
        $this->postJson('/api/password/verify', $payload)->assertUnprocessable();
    }

    public function test_motion_anomaly_is_returned_with_health_record(): void
    {
        $goat = Goat::create(['name' => 'Test', 'code' => 'GT-001']);
        HealthLog::create(['goat_id' => $goat->id, 'event_type' => 'Motion Anomaly', 'movement' => 'Prolonged Inactivity']);
        $this->actingAs(User::factory()->create(['role' => 'Caretaker']))->getJson('/api/health-logs')->assertOk()->assertJsonPath('health_logs.0.motion_anomaly', 'Prolonged Inactivity');
    }

    public function test_denied_health_logs_cannot_be_read_through_goat_profile(): void
    {
        $goat = Goat::create(['name' => 'Test', 'code' => 'GT-001']);
        HealthLog::create(['goat_id' => $goat->id, 'event_type' => 'Motion Anomaly', 'movement' => 'Excessive Movement']);
        DB::table('role_permissions')->insert(['role' => 'Caretaker', 'permissions' => json_encode(['health-logs.read' => false])]);
        $this->actingAs(User::factory()->create(['role' => 'Caretaker']))->getJson('/api/health-logs')->assertForbidden();
        $this->getJson('/api/goats/'.$goat->id)->assertOk()->assertJsonMissingPath('goat.health_logs');
    }
}
