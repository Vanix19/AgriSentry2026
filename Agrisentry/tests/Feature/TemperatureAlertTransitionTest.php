<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Goat;
use App\Services\HealthAlertEvaluator;
use App\Services\SmsGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemperatureAlertTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_readings_replace_previous_bands_and_can_recur_without_waiting(): void
    {
        $this->mock(SmsGatewayService::class)->shouldReceive('notifyForAlert')->andReturnNull();
        $goat = Goat::create(['name' => 'Test goat', 'code' => 'TRANSITION-1']);
        $evaluator = app(HealthAlertEvaluator::class);
        foreach ([[31.9, 'Urgent Low'], [32.5, 'Warning Low'], [39.0, 'Warning High'], [40.0, 'Urgent High'], [31.9, 'Urgent Low']] as [$temperature, $label]) {
            $goat->update(['temperature' => $temperature]);
            $evaluator->evaluate($goat->fresh());
            $active = Alert::where('goat_id', $goat->id)->where('status', 'Active')->get();
            $this->assertCount(1, $active);
            $this->assertStringStartsWith($label, $active->first()->recommendation);
        }
        $goat->update(['temperature' => 37.0]);
        $evaluator->evaluate($goat->fresh());
        $this->assertSame(0, Alert::where('status', 'Active')->count());
        $this->assertSame(5, Alert::where('status', 'Resolved')->count());
    }
}
