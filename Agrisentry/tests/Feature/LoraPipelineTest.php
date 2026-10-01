<?php

namespace Tests\Feature;

use App\Jobs\PublishFirebaseTelemetry;
use App\Models\Collar;
use App\Models\Goat;
use App\Services\FirebaseTelemetry;
use App\Services\SmsGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LoraPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.lorawan.secret' => 'test-secret', 'firebase.enabled' => true]);
        Queue::fake();
        $this->withHeader('X-LoRaWAN-Secret', 'test-secret');
        $this->mock(SmsGatewayService::class)->shouldReceive('notifyForAlert')->andReturnNull();
    }

    public function test_unknown_device_does_not_match_a_collar_with_null_eui(): void
    {
        $collar = Collar::create(['collar_code' => 'registered', 'battery_level' => 80]);
        $this->postJson('/api/lorawan/uplink', ['device_id' => 'unknown', 'battery_level' => 0])->assertNotFound();
        $this->assertEquals(80, $collar->fresh()->battery_level);
        Queue::assertNothingPushed();
    }

    public function test_unconfigured_webhook_fails_closed(): void
    {
        config(['services.lorawan.secret' => null]);
        $this->postJson('/api/lorawan/uplink', [])->assertStatus(503);
    }

    public function test_malformed_readings_are_rejected_without_mutation(): void
    {
        $collar = Collar::create(['collar_code' => 'registered', 'battery_level' => 80]);
        foreach ([['temperature' => 'broken'], ['temperature' => []], ['movement' => 'invalid'],
            ['uplink_message' => ['frm_payload' => '%%%']], ['uplink_message' => ['decoded_payload' => 'broken']], []] as $reading) {
            $this->postJson('/api/lorawan/uplink', array_merge(['device_id' => 'registered'], $reading))->assertUnprocessable();
        }
        $this->assertNull($collar->fresh()->last_seen);
        Queue::assertNothingPushed();
    }

    public function test_ttn_motion_reading_updates_animal_raises_alert_and_queues_firebase(): void
    {
        $goat = Goat::create(['name' => 'Motion test', 'code' => 'MOTION-1', 'movement' => 'Normal']);
        $collar = Collar::create(['collar_code' => 'registered', 'goat_id' => $goat->id]);
        $this->postJson('/api/lorawan/uplink', [
            'end_device_ids' => ['device_id' => 'registered'],
            'uplink_message' => ['decoded_payload' => ['movement' => 'Excessive Movement']],
        ])->assertCreated();
        $this->assertSame('Excessive Movement', $goat->fresh()->movement);
        $this->assertDatabaseHas('alerts', ['goat_id' => $goat->id, 'alert_type' => 'Excessive Movement']);
        Queue::assertPushed(PublishFirebaseTelemetry::class, fn ($job) => $job->collarId === $collar->id && $job->connection === 'database');
    }

    public function test_raw_ttn_bytes_are_decoded(): void
    {
        $goat = Goat::create(['name' => 'Raw test', 'code' => 'RAW-1']);
        Collar::create(['collar_code' => 'registered', 'goat_id' => $goat->id]);
        $this->postJson('/api/lorawan/uplink', [
            'end_device_ids' => ['device_id' => 'registered'],
            'uplink_message' => ['frm_payload' => base64_encode(pack('C*', 1, 94, 0, 1, 0, 0))],
        ])->assertCreated();
        $this->assertEquals(35.0, $goat->fresh()->temperature);
        $this->assertEquals(0, $goat->fresh()->battery);
        $this->assertSame('Active', $goat->fresh()->movement);
    }

    public function test_heltec_packets_override_stale_formatter_and_update_motion_and_battery(): void
    {
        $goat = Goat::create(['name' => 'Heltec', 'code' => 'HEL-1']);
        $collar = Collar::create(['collar_code' => 'registered', 'goat_id' => $goat->id, 'battery_level' => 66]);
        foreach ([[1, 48, 'Prolonged Inactivity'], [3, 25, 'Excessive Movement'], [0, 0, 'Normal']] as [$motion, $battery, $label]) {
            $this->postJson('/api/lorawan/uplink', [
                'end_device_ids' => ['device_id' => 'registered'],
                'uplink_message' => [
                    'frm_payload' => base64_encode(pack('C*', 1, 94, $motion, 0, $battery)),
                    'decoded_payload' => ['temperature' => 35, 'battery_level' => 66, 'movement' => 'Normal'],
                ],
            ])->assertCreated()->assertJsonPath('health_log.movement', $label);
            $this->assertEquals($battery, $collar->fresh()->battery_level);
            $this->assertEquals($battery, $goat->fresh()->battery);
            $this->assertSame($motion ? 'Urgent' : 'Normal', $goat->fresh()->status);
        }
        $this->assertDatabaseMissing('alerts', ['goat_id' => $goat->id, 'status' => 'Active', 'alert_type' => 'Excessive Movement']);
    }

    public function test_heltec_sensor_error_still_accepts_battery_without_false_temperature(): void
    {
        $goat = Goat::create(['name' => 'Heltec', 'code' => 'HEL-2']);
        Collar::create(['collar_code' => 'registered', 'goat_id' => $goat->id]);
        $this->postJson('/api/lorawan/uplink', [
            'device_id' => 'registered',
            'uplink_message' => ['frm_payload' => base64_encode(pack('C*', 255, 255, 0, 0x64, 72))],
        ])->assertCreated()->assertJsonPath('health_log.temperature', null)->assertJsonPath('health_log.movement', null);
        $this->assertEquals(72, $goat->fresh()->battery);
    }

    public function test_retry_publishes_current_state_and_propagates_outage_for_queue_retry(): void
    {
        $collar = Collar::create(['collar_code' => 'registered', 'battery_level' => 80]);
        $job = new PublishFirebaseTelemetry($collar->id);
        $collar->update(['battery_level' => 20]);
        $firebase = $this->mock(FirebaseTelemetry::class);
        $firebase->shouldReceive('publish')->once()->withArgs(fn ($current) => $current->battery_level == 20)
            ->andThrow(new \RuntimeException('Firebase unavailable'));
        $this->expectException(\RuntimeException::class);
        $job->handle($firebase);
    }
}
