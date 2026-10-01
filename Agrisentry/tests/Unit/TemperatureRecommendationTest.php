<?php

namespace Tests\Unit;

use App\Models\Alert;
use App\Models\Goat;
use App\Services\VetAdvisoryService;
use PHPUnit\Framework\TestCase;

class TemperatureRecommendationTest extends TestCase
{
    public function test_all_five_bands_and_boundaries(): void
    {
        foreach ([['Urgent Low', 31.9], ['Warning Low', 32.0], ['Warning Low', 32.9], ['Normal', 33.0], ['Normal', 38.5], ['Warning High', 38.6], ['Warning High', 39.5], ['Urgent High', 39.6]] as [$label, $temperature]) {
            $this->assertStringStartsWith($label, VetAdvisoryService::temperatureRecommendation($temperature));
        }
        $this->assertNull(VetAdvisoryService::temperatureRecommendation(null));
    }

    public function test_active_alert_advice_changes_with_latest_temperature(): void
    {
        $goat = new Goat(['temperature' => 31.9]);
        $alert = new Alert(['alert_type' => 'Low Temperature', 'status' => 'Active', 'recommendation' => 'Original advice']);
        $alert->setRelation('goat', $goat);
        foreach ([[31.9, 'Urgent Low'], [32.5, 'Warning Low'], [37.0, 'Normal'], [39.0, 'Warning High'], [40.0, 'Urgent High']] as [$temperature, $label]) {
            $goat->temperature = $temperature;
            $this->assertStringStartsWith($label, $alert->recommendation);
        }
        $alert->status = 'Resolved';
        $this->assertSame('Original advice', $alert->recommendation);
        $alert->status = 'Active';
        $alert->alert_type = 'Low Battery';
        $this->assertSame('Original advice', $alert->recommendation);
    }

    public function test_advice_matches_the_card_reading_instead_of_the_latest_goat_reading(): void
    {
        $alert = new Alert(['alert_type' => 'Low Temperature', 'status' => 'Active',
            'message' => 'Low skin temperature reading: 33.8°C.']);
        $alert->setRelation('goat', new Goat(['temperature' => 40.0]));
        $this->assertStringStartsWith('Normal', $alert->recommendation);
        $this->assertStringContainsString('33.8°C', $alert->recommendation);
        $this->assertStringNotContainsString('40.0°C', $alert->recommendation);
    }

    public function test_normal_readings_have_specific_guidance_and_multiple_steps(): void
    {
        foreach ([[33.8, 'lower part'], [36.0, 'middle'], [38.0, 'upper part']] as [$temperature, $phrase]) {
            $advice = VetAdvisoryService::temperatureRecommendation($temperature);
            $this->assertStringContainsString($phrase, $advice);
            $this->assertStringContainsString(number_format($temperature, 1) . '°C', $advice);
            $this->assertGreaterThanOrEqual(3, substr_count($advice, '. '));
        }
        $this->assertNotSame(VetAdvisoryService::temperatureRecommendation(33.8), VetAdvisoryService::temperatureRecommendation(34.8));
    }

    public function test_individual_readings_have_different_steps_even_without_the_temperature_label(): void
    {
        $recommendations = [];
        for ($tenths = 290; $tenths <= 390; $tenths++) {
            $advice = VetAdvisoryService::temperatureRecommendation($tenths / 10);
            $recommendations[] = preg_replace('/Skin reading [\d.]+°C: /u', '', $advice);
        }
        $this->assertCount(count($recommendations), array_unique($recommendations));

        foreach ([[32.2, 'collar position'], [32.6, 'appetite and chewing'], [34.8, 'shelter for drafts']] as [$temperature, $action]) {
            $this->assertStringContainsString($action, VetAdvisoryService::temperatureRecommendation($temperature));
        }
    }
}
