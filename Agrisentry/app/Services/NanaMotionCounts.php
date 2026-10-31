<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

class NanaMotionCounts
{
    public function changes(array $records): array
    {
        $zone = new DateTimeZone('Asia/Shanghai');
        $utc = new DateTimeZone('UTC');
        $updates = [];
        foreach (['2026-10-28' => [398, 2], '2026-10-29' => [354, 4]] as $date => [$active, $excessive]) {
            $generated = [];
            foreach ($records as $record) {
                $time = (new DateTimeImmutable($record['created_at'], $utc))->setTimezone($zone);
                if ($time->format('Y-m-d') !== $date) continue;
                $synthetic = (int) $record['goat_id'] === 4 && $record['event_type'] === 'Simulated Reading'
                    && str_starts_with($record['description'] ?? '', NanaSimulatedHistory::MARKER.';');
                // Preserve inactivity findings, regardless of their source.
                $inactivity = str_contains(strtolower(($record['movement'] ?? '').' '.($record['event_type'] ?? '').' '.($record['description'] ?? '')), 'prolonged inactivity');
                if ($synthetic && !$inactivity) {
                    $record['hour'] = (int) $time->format('H');
                    $generated[] = $record;
                } else {
                    if (!$inactivity) {
                        if (str_contains(strtolower(($record['movement'] ?? '').' '.($record['event_type'] ?? '').' '.($record['description'] ?? '')), 'excessive movement')) $excessive--;
                        elseif (($record['movement'] ?? '') === 'Active') $active--;
                    }
                }
            }
            if (!$generated) continue;
            if ($active < 0 || $excessive < 0 || count($generated) < $active + $excessive) {
                throw new RuntimeException("Cannot meet {$date} motion targets without changing real readings or inactivity. No motion changes were applied.");
            }
            // Prefer existing morning excessive events; all new excessive events remain in the morning.
            usort($generated, fn ($a, $b) =>
                (($a['movement'] === 'Excessive Movement' ? 0 : 1) <=> ($b['movement'] === 'Excessive Movement' ? 0 : 1))
                ?: strcmp($a['created_at'], $b['created_at']));
            $assigned = [];
            foreach ($generated as $row) {
                if ($excessive > 0 && $row['hour'] >= 6 && $row['hour'] < 12) {
                    $assigned[$row['id']] = 'Excessive Movement';
                    $excessive--;
                }
            }
            if ($excessive > 0) throw new RuntimeException("Not enough generated morning readings on {$date}. No motion changes were applied.");
            usort($generated, fn ($a, $b) =>
                (($a['movement'] === 'Active' ? 0 : 1) <=> ($b['movement'] === 'Active' ? 0 : 1))
                ?: (($a['hour'] >= 6 && $a['hour'] < 20 ? 0 : 1) <=> ($b['hour'] >= 6 && $b['hour'] < 20 ? 0 : 1))
                ?: strcmp($a['created_at'], $b['created_at']));
            foreach ($generated as $row) {
                $movement = $assigned[$row['id']] ?? ($active > 0 ? 'Active' : 'Normal');
                if (!isset($assigned[$row['id']]) && $active > 0) $active--;
                $normal = $row['temperature'] === null || ((float) $row['temperature'] >= 35.5 && (float) $row['temperature'] <= 37.2);
                $severity = $movement === 'Excessive Movement' ? 'Urgent' : ($normal ? 'Normal' : 'Warning');
                if ($movement !== $row['movement'] || $severity !== $row['severity']) {
                    $updates[] = ['id' => $row['id'], 'movement' => $movement, 'severity' => $severity];
                }
            }
        }
        return $updates;
    }
}
