<?php

require_once __DIR__.'/../app/Services/NanaSimulatedHistory.php';

$generator = new App\Services\NanaSimulatedHistory;
$rows = $generator->rows(4);
$check = function (bool $valid, string $message): void {
    if (!$valid) throw new RuntimeException($message);
};
$check(count($rows) === 2610, 'Total must be 2610');
$check($rows === $generator->rows(4), 'Generation must be deterministic');
$zone = new DateTimeZone('Asia/Shanghai');
$utc = new DateTimeZone('UTC');
$counts = $events = [];
$previous = null;
foreach ($rows as $row) {
    $time = new DateTimeImmutable($row['created_at'], $utc);
    $local = $time->setTimezone($zone);
    $date = $local->format('Y-m-d');
    $minute = (int) $local->format('H') * 60 + (int) $local->format('i');
    $counts[$date] = ($counts[$date] ?? 0) + 1;
    $check($row['goat_id'] === 4 && $row['event_type'] === 'Simulated Reading', 'Wrong goat or missing label');
    $check(str_contains($row['description'], 'not a sensor measurement'), 'Missing disclosure');
    $check($row['created_at'] === $row['updated_at'], 'Timestamp mismatch');
    if ($previous !== null) $check($time->getTimestamp() - $previous === 120, 'Interval must be two minutes');
    $previous = $time->getTimestamp();
    $temp = $row['temperature'];
    if ($minute >= 120 && $minute < 140) {
        $check($temp < 35.5 && $row['led_status'] === 'Blue', 'Night spike missing');
    } elseif ($minute >= 720 && $minute < 740) {
        $check($temp > 37.2 && $row['led_status'] === 'Red', 'Noon spike missing');
    } else {
        $check($temp >= 35.5 && $temp <= 37.2 && $row['led_status'] === 'Green', 'Normal temperature outside band');
    }
    if ($row['movement'] === 'Excessive Movement') {
        $check(in_array($minute, [420, 540, 660], true), 'Excessive movement outside morning schedule');
        $events[$date] = ($events[$date] ?? 0) + 1;
    }
}
$check(array_values($counts) === [720, 720, 720, 450], 'Daily caps or cutoff incorrect');
$check(array_values($events) === [3, 3, 3, 3], 'Must have three morning events daily');
$inactive = array_values(array_filter($rows, fn ($row) => $row['movement'] === 'Prolonged Inactivity'));
$check(count($inactive) === 1 && $inactive[0]['created_at'] === '2026-10-27 17:30:00', 'Inactivity must be October 28 at 01:30 local');
$check(count(array_filter(array_slice($rows, 0, 45), fn ($row) => $row['movement'] === 'Low Movement')) === 45, 'Need 90 minutes preceding inactivity');
$check($rows[0]['created_at'] === '2026-10-27 16:00:00', 'Wrong UTC start');
$check(end($rows)['created_at'] === '2026-10-31 06:58:00', 'Wrong UTC cutoff');
$check($generator->rows(8, 2025, 'UTC')[0]['created_at'] === '2025-10-28 00:00:00', 'Options not respected');
try {
    $generator->rows(4, 0);
    throw new RuntimeException('Invalid year accepted');
} catch (InvalidArgumentException) {
}
echo "PASS: 2610 rows; daily caps, intervals, UTC conversion, cutoff, labels, temperature bands and motion events.\n";
