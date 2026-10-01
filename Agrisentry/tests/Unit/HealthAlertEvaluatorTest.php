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
            'urgent low' => [31.9, 'Urgent'],
            'warning low lower edge' => [32.0, 'Warning'],
            'warning low upper edge' => [32.9, 'Warning'],
            'normal lower edge' => [33.0, 'Normal'],
            'normal upper edge' => [38.5, 'Normal'],
            'warning high lower edge' => [38.6, 'Warning'],
            'warning high upper edge' => [39.5, 'Warning'],
            'urgent high' => [39.6, 'Urgent'],
        ];
    }

    #[DataProvider('temperatureBands')]
    public function test_it_classifies_temperature_bands(float $temperature, string $expected): void
    {
        $this->assertSame($expected, HealthAlertEvaluator::resolveStatus($temperature)['status']);
    }
}
