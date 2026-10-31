<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

class NanaSimulatedHistory
{
    public const MARKER = 'Simulated NANA October history v1';

    public function bounds(int $year, string $timezone): array
    {
        if ($year < 2000 || $year > 2100) {
            throw new InvalidArgumentException('Year must be between 2000 and 2100.');
        }
        $zone = new DateTimeZone($timezone);

        return [new DateTimeImmutable("{$year}-10-28 00:00:00", $zone),
            new DateTimeImmutable("{$year}-10-31 15:00:00", $zone)];
    }

    public function rows(int $goatId, int $year = 2026, string $timezone = 'Asia/Shanghai'): array
    {
        [$start, $end] = $this->bounds($year, $timezone);
        $utc = new DateTimeZone('UTC');
        $rows = [];
        // End is exclusive: October 31 has 450 readings, ending at 14:58.
        for ($time = $start; $time < $end; $time = $time->modify('+2 minutes')) {
            $minute = (int) $time->format('H') * 60 + (int) $time->format('i');
            $temperature = round(36.3 + 0.55 * cos(($minute - 720) * M_PI / 720)
                + 0.08 * sin($minute * M_PI / 17), 2);
            if ($minute >= 120 && $minute < 140) {
                $temperature = round(35.1 + 0.03 * (($minute - 120) / 2), 2);
            } elseif ($minute >= 720 && $minute < 740) {
                $temperature = round(37.3 + 0.04 * (($minute - 720) / 2), 2);
            }
            $movement = $minute >= 360 && $minute < 1080 ? 'Active' : 'Normal';
            if ($time->format('d') === '28' && $minute < 90) {
                $movement = 'Normal';
            } elseif ($time->format('d') === '28' && $minute === 90) {
                $movement = 'Prolonged Inactivity';
            } elseif (in_array($minute, [420, 540, 660], true)) {
                $movement = 'Excessive Movement';
            }
            // Exact requested daily movement totals for the generated baseline.
            $day = $time->format('d');
            if ($day === '28' || $day === '29') {
                $activeTarget = $day === '28' ? 398 : 354;
                $excessiveMinutes = $day === '28' ? [420, 540] : [420, 480, 540, 660];
                $activeEnd = 360 + 2 * ($activeTarget + count($excessiveMinutes));
                $movement = $minute >= 360 && $minute < $activeEnd ? 'Active' : 'Normal';
                if ($day === '28' && $minute === 90) $movement = 'Prolonged Inactivity';
                if (in_array($minute, $excessiveMinutes, true)) $movement = 'Excessive Movement';
            }
            $motionAlert = in_array($movement, ['Prolonged Inactivity', 'Excessive Movement'], true);
            $normal = $temperature >= 35.5 && $temperature <= 37.2;
            $stamp = $time->setTimezone($utc)->format('Y-m-d H:i:s');
            $rows[] = [
                'goat_id' => $goatId, 'event_type' => 'Simulated Reading',
                'description' => self::MARKER.'; synthetic demonstration data, not a sensor measurement.',
                'temperature' => $temperature, 'movement' => $movement,
                'led_status' => $temperature < 35.5 ? 'Blue' : ($temperature > 37.2 ? 'Red' : 'Green'),
                'severity' => $motionAlert ? 'Urgent' : ($normal ? 'Normal' : 'Warning'),
                'created_at' => $stamp, 'updated_at' => $stamp,
            ];
        }

        return $rows;
    }
}

