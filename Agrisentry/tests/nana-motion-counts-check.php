<?php

require_once __DIR__.'/../app/Services/NanaSimulatedHistory.php';
require_once __DIR__.'/../app/Services/NanaMotionCounts.php';

$check = function ($valid, $message) { if (!$valid) throw new RuntimeException($message); };
$rows = (new App\Services\NanaSimulatedHistory)->rows(4);
$utc = new DateTimeZone('UTC');
$zone = new DateTimeZone('Asia/Shanghai');
$counts = function ($rows) use ($utc, $zone) {
    $result = [];
    foreach ($rows as $row) {
        $date = (new DateTimeImmutable($row['created_at'], $utc))->setTimezone($zone)->format('Y-m-d');
        $result[$date][$row['movement']] = ($result[$date][$row['movement']] ?? 0) + 1;
    }
    return $result;
};
$baseline = $counts($rows);
$check($baseline['2026-10-28']['Active'] === 398 && $baseline['2026-10-28']['Excessive Movement'] === 2, 'Wrong October 28 baseline');
$check($baseline['2026-10-29']['Active'] === 354 && $baseline['2026-10-29']['Excessive Movement'] === 4, 'Wrong October 29 baseline');
$check($baseline['2026-10-28']['Prolonged Inactivity'] === 1, 'Inactivity lost');
foreach ($rows as $id => &$row) $row['id'] = $id;
unset($row);
// Model an old import with too many active readings plus preserved genuine measurements.
foreach ($rows as &$row) {
    if ($row['movement'] === 'Normal') $row['movement'] = 'Active';
}
unset($row);
$rows[200]['event_type'] = 'LoRaWAN Uplink';
$rows[200]['description'] = 'Automatic reading from collar goat-collar-001';
$rows[200]['movement'] = 'Active';
$rows[210]['event_type'] = 'LoRaWAN Uplink';
$rows[210]['description'] = 'Automatic reading from collar goat-collar-001';
$rows[210]['movement'] = 'Excessive Movement';
$before = $rows;
$service = new App\Services\NanaMotionCounts;
$changes = $service->changes($rows);
foreach ($changes as $change) {
    $rows[$change['id']]['movement'] = $change['movement'];
    $rows[$change['id']]['severity'] = $change['severity'];
}
$after = $counts($rows);
$check($after['2026-10-28']['Active'] === 398 && $after['2026-10-28']['Excessive Movement'] === 2, 'Wrong corrected October 28 total');
$check($after['2026-10-29']['Active'] === 354 && $after['2026-10-29']['Excessive Movement'] === 4, 'Wrong corrected October 29 total');
$check($rows[200] === $before[200] && $rows[210] === $before[210], 'Real readings changed');
foreach ($rows as $id => $row) {
    foreach (['temperature', 'created_at', 'description', 'event_type', 'led_status'] as $key) $check($row[$key] === $before[$id][$key], 'Non-motion field changed');
    if ($id >= 1440) $check($row === $before[$id], 'Later date changed');
}
$check($service->changes($rows) === [], 'Repeat correction should be a no-op');
foreach ([211, 212] as $id) {
    $rows[$id]['event_type'] = 'LoRaWAN Uplink';
    $rows[$id]['movement'] = 'Excessive Movement';
}
try {
    $service->changes($rows);
    throw new LogicException('Impossible target was accepted');
} catch (RuntimeException $error) {
    $check(!($error instanceof LogicException), 'Expected conflict rejection');
}
echo "PASS: requested baseline and corrected totals, preserved real readings, inactivity, other dates, and idempotency.\n";
