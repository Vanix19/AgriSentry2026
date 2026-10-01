<?php

namespace Tests\Feature;

use App\Models\Collar;
use App\Models\Goat;
use App\Models\User;
use App\Services\FirebaseTelemetry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FirebaseTelemetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_requires_laravel_authentication(): void
    {
        $this->postJson('/api/firebase/session')->assertUnauthorized();
    }

    public function test_disabled_firebase_does_not_break_existing_login(): void
    {
        config(['firebase.enabled' => false]);
        Sanctum::actingAs(User::factory()->create(['role' => 'Admin']));
        $this->postJson('/api/firebase/session')->assertOk()->assertExactJson(['enabled' => false]);
    }

    public function test_sensor_payload_contains_no_user_or_owner_information(): void
    {
        config(['firebase.enabled' => true, 'firebase.farm_id' => 'test-farm']);
        $service = new class extends FirebaseTelemetry {
            public array $sent = [];
            protected function write(string $path, array $data): void { $this->sent = [$path, $data]; }
        };
        $goat = new Goat(['temperature' => 39.0, 'owner' => 'Private owner', 'status' => 'Warning']);
        $goat->id = 1;
        $collar = new Collar(['battery_level' => 0, 'last_seen' => now()]);
        $collar->id = 2;
        $collar->setRelation('goat', $goat);
        $service->publish($collar);
        $this->assertSame('farms/test-farm/telemetry/2', $service->sent[0]);
        $this->assertSame(0, $service->sent[1]['battery_level']);
        $this->assertSame(39.0, $service->sent[1]['temperature']);
        $this->assertArrayNotHasKey('owner', $service->sent[1]);
        $this->assertStringStartsWith('Warning High', $service->sent[1]['recommendation']);
    }
}
