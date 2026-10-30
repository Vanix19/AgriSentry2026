<?php

namespace Tests\Feature;

use App\Models\Goat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NanaHistoryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_preview_and_import(): void
    {
        $this->get('/admin/nana-history')->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'Staff']));
        $this->get('/admin/nana-history')->assertForbidden();
        $this->post('/admin/nana-history', ['acknowledge' => 1])->assertForbidden();
    }

    public function test_preview_then_import_preserves_real_history_and_repeats_add_nothing(): void
    {
        $goat = new Goat(['name' => 'Nana', 'code' => 'NANA-PAGE']);
        $goat->id = 4;
        $goat->save();
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        DB::table('health_logs')->insert(['goat_id' => 4, 'event_type' => 'LoRaWAN Uplink', 'temperature' => 36,
            'created_at' => '2026-10-27 16:00:56', 'updated_at' => '2026-10-27 16:00:56']);
        $this->get('/admin/nana-history')->assertOk()->assertSee('Import 2609 simulated readings');
        $this->assertDatabaseCount('health_logs', 1);
        $this->post('/admin/nana-history', ['acknowledge' => 1])->assertRedirect('/admin/nana-history');
        $this->assertDatabaseCount('health_logs', 2610);
        $this->assertDatabaseHas('health_logs', ['event_type' => 'LoRaWAN Uplink', 'created_at' => '2026-10-27 16:00:56']);
        $this->get('/admin/nana-history')->assertOk();
        $this->post('/admin/nana-history', ['acknowledge' => 1])->assertRedirect('/admin/nana-history');
        $this->assertDatabaseCount('health_logs', 2610);
        $this->assertDatabaseCount('alerts', 0);
    }

    public function test_missing_preview_or_changed_history_prevents_writes(): void
    {
        $goat = new Goat(['name' => 'Nana', 'code' => 'NANA-STALE']);
        $goat->id = 4;
        $goat->save();
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $this->post('/admin/nana-history', ['acknowledge' => 1])->assertSessionHasErrors('history');
        $this->get('/admin/nana-history')->assertOk();
        DB::table('health_logs')->insert(['goat_id' => 4, 'event_type' => 'Reading', 'created_at' => '2026-10-28 00:00:00']);
        $this->post('/admin/nana-history', ['acknowledge' => 1])->assertSessionHasErrors('history');
        $this->assertDatabaseCount('health_logs', 1);
    }
}
