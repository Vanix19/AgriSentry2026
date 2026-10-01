<?php

namespace Tests\Feature;

use App\Models\Goat;
use App\Models\HealthLog;
use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsAndReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_update_is_self_only_and_preserves_role(): void
    {
        $user = User::factory()->create(['username' => 'caretaker', 'role' => 'Caretaker', 'password' => 'old-password']);
        $other = User::factory()->create(['name' => 'Other user']);
        $user->phoneNumbers()->create(['phone_number' => '09171234567', 'is_primary' => true]);
        DB::table('password_otps')->insert(['user_id' => $user->id, 'code_hash' => Hash::make('123456'), 'expires_at' => now()->addMinutes(10)]);
        $this->actingAs($user)->putJson('/api/settings', [
            'id' => $other->id, 'name' => 'Updated name', 'username' => 'updated.user', 'email' => 'updated@gmail.com',
            'phone_number' => '+639171111111', 'current_password' => 'old-password', 'role' => 'Admin', 'password' => 'injected-password',
        ])->assertOk()->assertJsonPath('user.role', 'Caretaker')->assertJsonMissingPath('user.password');
        $this->assertSame('Other user', $other->fresh()->name);
        $this->assertSame('Updated name', $user->fresh()->name);
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
        $this->assertSame(1, $user->phoneNumbers()->where('is_primary', true)->count());
        $this->assertSame('+639171111111', $user->phoneNumbers()->where('is_primary', true)->value('phone_number'));
        $this->assertDatabaseMissing('password_otps', ['user_id' => $user->id]);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'action' => 'Profile updated']);
    }

    public function test_profile_validation_and_authentication(): void
    {
        $this->getJson('/api/settings')->assertUnauthorized();
        $this->putJson('/api/settings', [])->assertUnauthorized();
        $user = User::factory()->create(['password' => 'old-password']);
        $other = User::factory()->create();
        $payload = ['name' => 'Changed', 'username' => $user->username, 'email' => $user->email, 'current_password' => 'incorrect'];
        $this->actingAs($user)->putJson('/api/settings', $payload)->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $payload['current_password'] = 'old-password';
        $payload['email'] = $other->email;
        $this->putJson('/api/settings', $payload)->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertNotSame('Changed', $user->fresh()->name);
        $this->get('/settings')->assertOk()->assertSee('Personal information')->assertSee('Password &amp; security', false);
    }

    public function test_date_range_includes_entire_end_day_and_uses_historical_readings(): void
    {
        $goat = Goat::create(['name' => 'Test', 'code' => 'GT-001', 'temperature' => 99, 'status' => 'Urgent']);
        foreach ([['2026-08-31 23:59:59', 50], ['2026-09-01 00:00:00', 32], ['2026-09-02 23:59:59', 34], ['2026-09-03 00:00:00', 50]] as [$time, $temperature]) {
            $log = HealthLog::create(['goat_id' => $goat->id, 'event_type' => 'Telemetry', 'temperature' => $temperature]);
            $log->forceFill(['created_at' => $time])->save();
        }
        MedicalRecord::create(['goat_id' => $goat->id, 'record_type' => 'Treatment', 'title' => 'Included', 'date_given' => '2026-09-02']);
        MedicalRecord::create(['goat_id' => $goat->id, 'record_type' => 'Treatment', 'title' => 'Excluded', 'date_given' => '2026-09-03']);
        $undated = MedicalRecord::create(['goat_id' => $goat->id, 'record_type' => 'Treatment', 'title' => 'Undated']);
        $undated->forceFill(['created_at' => '2026-09-01 15:00:00'])->save();
        $this->actingAs(User::factory()->create(['role' => 'Admin']))->getJson('/api/reports?start_date=2026-09-01&end_date=2026-09-02')
            ->assertOk()->assertJsonPath('period', 'custom')->assertJsonPath('health_logs_count', 2)
            ->assertJsonPath('medical_records_count', 2)->assertJsonPath('temp_avg', 33)->assertJsonPath('temp_low', 32)
            ->assertJsonPath('temp_high', 34)->assertJsonPath('normal_goats', 1)->assertJsonPath('urgent_goats', 0);
        $this->getJson('/api/reports?start_date=2026-09-02&end_date=2026-09-02')->assertOk()->assertJsonPath('health_logs_count', 1);
        $this->getJson('/api/reports?start_date=2025-01-01&end_date=2025-01-01')->assertOk()->assertJsonPath('health_logs_count', 0)->assertJsonPath('temp_avg', null)->assertJsonPath('normal_goats', 0);
    }

    public function test_invalid_report_ranges_are_rejected(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        foreach (['start_date=2026-09-02&end_date=2026-09-01', 'start_date=2026-09-01', 'start_date=2026-02-30&end_date=2026-03-01'] as $query) {
            $this->getJson('/api/reports?'.$query)->assertUnprocessable();
        }
    }
}
