<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;

class NanaHistoryPlan
{
    public function build(array $existing): array
    {
        $zone = new DateTimeZone('Asia/Shanghai');
        $utc = new DateTimeZone('UTC');
        $days = [];
        foreach (['28', '29', '30', '31'] as $day) {
            $days['2026-10-'.$day] = ['existing' => 0, 'adding' => 0, 'limit' => $day === '31' ? 450 : 720];
        }
        $occupied = [];
        foreach ($existing as $record) {
            $time = new DateTimeImmutable($record['created_at'], $utc);
            $date = $time->setTimezone($zone)->format('Y-m-d');
            if (!isset($days[$date])) continue;
            $days[$date]['existing']++;
            $occupied[intdiv($time->getTimestamp(), 120)] = true;
        }
        $candidates = (new NanaSimulatedHistory)->rows(4);
        // When little room remains, prefer requested motion events and temperature spikes.
        $priority = fn ($row) => in_array($row['movement'], ['Prolonged Inactivity', 'Excessive Movement'], true)
            ? 0 : (($row['temperature'] < 35.5 || $row['temperature'] > 37.2) ? 1 : 2);
        usort($candidates, fn ($a, $b) => ($priority($a) <=> $priority($b)) ?: strcmp($a['created_at'], $b['created_at']));
        $rows = [];
        $skippedMotion = 0;
        foreach ($candidates as $row) {
            $time = new DateTimeImmutable($row['created_at'], $utc);
            $date = $time->setTimezone($zone)->format('Y-m-d');
            $day = &$days[$date];
            $slot = intdiv($time->getTimestamp(), 120);
            if (isset($occupied[$slot]) || $day['existing'] + $day['adding'] >= $day['limit']) {
                if ($priority($row) === 0) $skippedMotion++;
                unset($day);
                continue;
            }
            $rows[] = $row;
            $occupied[$slot] = true;
            $day['adding']++;
            unset($day);
        }
        usort($rows, fn ($a, $b) => strcmp($a['created_at'], $b['created_at']));
        return ['days' => $days, 'rows' => $rows, 'skipped_motion' => $skippedMotion,
            'fingerprint' => hash('sha256', json_encode([$existing, $rows]))];
    }
}
