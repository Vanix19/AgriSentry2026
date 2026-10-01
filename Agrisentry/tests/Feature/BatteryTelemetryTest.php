<?php

namespace Tests\Feature;

use App\Models\Collar;
use App\Models\Goat;
use App\Services\SmsGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatteryTelemetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_battery_only_uplinks_update_both_collar_and_goat_including_zero(): void
    {
        config(['services.lorawan.secret' => 'test-secret']);
        $this->mock(SmsGatewayService::class)->shouldReceive('notifyForAlert')->andReturnNull();
        $goat = Goat::create(['name' => 'Battery test', 'code' => 'BAT-1']);
        $collar = Collar::create(['goat_id' => $goat->id, 'collar_code' => 'BAT-COLLAR', 'battery_level' => 80]);
        foreach ([42, 0] as $level) {
            $this->withHeader('X-LoRaWAN-Secret', 'test-secret')->postJson('/api/lorawan/uplink', ['device_id' => 'BAT-COLLAR', 'battery_level' => $level])->assertCreated();
            $this->assertEquals($level, $collar->fresh()->battery_level);
            $this->assertEquals($level, $goat->fresh()->battery);
            $this->assertNotNull($collar->fresh()->last_seen);
        }
        $this->withHeader('X-LoRaWAN-Secret', 'test-secret')->postJson('/api/lorawan/uplink', ['device_id' => 'BAT-COLLAR', 'battery_level' => 255])->assertStatus(422);
        $this->assertEquals(0, $collar->fresh()->battery_level);
    }
}
