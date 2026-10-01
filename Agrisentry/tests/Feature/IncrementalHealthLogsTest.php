<?php

namespace Tests\Feature;

use App\Models\Goat;
use App\Models\HealthLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncrementalHealthLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_incremental_refresh_returns_only_new_logs_and_keeps_motion_filter(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $goat = Goat::create(['name' => 'Test goat', 'code' => 'SYNC-01']);
        $old = HealthLog::create(['goat_id' => $goat->id, 'event_type' => 'Reading', 'movement' => 'Normal']);
        $new = HealthLog::create(['goat_id' => $goat->id, 'event_type' => 'Motion', 'movement' => 'Prolonged Inactivity']);
        $this->getJson('/api/health-logs?after_id='.$old->id)->assertOk()->assertJsonCount(1, 'health_logs')->assertJsonPath('health_logs.0.id', $new->id);
        $this->getJson('/api/health-logs?after_id='.$new->id)->assertOk()->assertJsonCount(0, 'health_logs');
        $this->getJson('/api/health-logs?after_id='.$old->id.'&motion_anomaly=none')->assertOk()->assertJsonCount(0, 'health_logs');
        $this->getJson('/api/health-logs')->assertOk()->assertJsonCount(2, 'health_logs');
        $this->getJson('/api/health-logs?after_id=-1')->assertUnprocessable();
    }
}
