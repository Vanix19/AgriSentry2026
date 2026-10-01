<?php

namespace Tests\Unit;

use App\Services\VetAdvisoryService;
use PHPUnit\Framework\TestCase;

class VetAdvisoryServiceTest extends TestCase
{
    public function test_temperature_advice_distinguishes_warning_from_urgent(): void
    {
        foreach (['High Temperature', 'Low Temperature'] as $type) {
            $warning = VetAdvisoryService::recommendationFor($type, 'Warning');
            $urgent = VetAdvisoryService::recommendationFor($type, 'High');
            $this->assertStringContainsString('repeat the reading within 15 minutes', $warning);
            $this->assertStringContainsString('contact a veterinarian now', $urgent);
            $this->assertStringNotContainsString('repeat the reading within 15 minutes', $urgent);
            $this->assertNotSame($warning, $urgent);
        }
    }

    public function test_urgent_severity_overrides_warning_in_the_alert_name(): void
    {
        $advice = VetAdvisoryService::recommendationFor('High Temperature Warning', 'Urgent');
        $this->assertStringContainsString('Urgent: inspect', $advice);
        $this->assertStringNotContainsString('repeat the reading within 15 minutes', $advice);
    }
}
