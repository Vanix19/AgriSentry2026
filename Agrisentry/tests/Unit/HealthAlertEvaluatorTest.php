<?php

namespace Tests\Unit;

use App\Services\HealthAlertEvaluator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HealthAlertEvaluatorTest extends TestCase
{
    public static function temperatureBands(): array
    {
        return [
            'urgent low' => [34.4, 'Urgent'],
            'warning low lower edge' => [34.5, 'Warning'],
            'warning low upper edge' => [35.4, 'Warning'],
            'normal lower edge' => [35.5, 'Normal'],
            'normal upper edge' => [37.2, 'Normal'],
            'warning high lower edge' => [37.3, 'Warning'],
            'warning high upper edge' => [38.2, 'Warning'],
            'urgent high' => [38.3, 'Urgent'],
        ];
    }

    #[DataProvider('temperatureBands')]
    public function test_it_classifies_temperature_bands(float $temperature, string $expected): void
    {
        $this->assertSame($expected, HealthAlertEvaluator::resolveStatus($temperature)['status']);
    }
}
