<?php

namespace Tests\Feature;

use App\Models\Goat;
use App\Models\HealthLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NanaSimulatedHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_write_and_repeat_preserve_live_state(): void
    {
        $goat = Goat::create(['name' => 'Nana', 'code' => 'NANA-TEST', 'temperature' => 36.5]);
        $options = ['--goat-id' => $goat->id];
        $this->artisan('history:simulate-nana', $options)->assertSuccessful();
        $this->assertDatabaseCount('health_logs', 0);
        $this->artisan('history:simulate-nana', $options + ['--write' => true])->assertSuccessful();
        $this->assertDatabaseCount('health_logs', 2610);
        $this->artisan('history:simulate-nana', $options + ['--write' => true])->assertSuccessful();
        $this->assertDatabaseCount('health_logs', 2610);
        $this->assertDatabaseCount('alerts', 0);
        $this->assertEquals(36.5, $goat->fresh()->temperature);
        $this->assertSame('2026-10-31 06:58:00', HealthLog::max('created_at'));
    }

    public function test_existing_history_after_cutoff_prevents_import(): void
    {
        $goat = Goat::create(['name' => 'NANA', 'code' => 'NANA-OVERLAP']);
        $log = HealthLog::create(['goat_id' => $goat->id, 'event_type' => 'Reading']);
        $log->forceFill(['created_at' => '2026-10-31 08:00:00'])->save();
        $this->artisan('history:simulate-nana', ['--goat-id' => $goat->id, '--write' => true])->assertFailed();
        $this->assertDatabaseCount('health_logs', 1);
    }

    public function test_other_goats_are_rejected(): void
    {
        $goat = Goat::create(['name' => 'Other goat', 'code' => 'OTHER']);
        $this->artisan('history:simulate-nana', ['--goat-id' => $goat->id, '--write' => true])->assertFailed();
        $this->assertDatabaseCount('health_logs', 0);
    }

    public function test_modified_import_is_not_duplicated(): void
    {
        $goat = Goat::create(['name' => 'NANA', 'code' => 'NANA-ALTERED']);
        $options = ['--goat-id' => $goat->id, '--write' => true];
        $this->artisan('history:simulate-nana', $options)->assertSuccessful();
        HealthLog::query()->orderBy('id')->first()->update(['temperature' => 39]);
        $this->artisan('history:simulate-nana', $options)->assertFailed();
        $this->assertDatabaseCount('health_logs', 2610);
    }
}
