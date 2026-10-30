<?php

require_once __DIR__.'/../app/Services/NanaSimulatedHistory.php';
require_once __DIR__.'/../app/Services/NanaHistoryPlan.php';

$planner = new App\Services\NanaHistoryPlan;
$check = function (bool $valid, string $message) { if (!$valid) throw new RuntimeException($message); };
$plan = $planner->build([]);
$check(count($plan['rows']) === 2610, 'Empty history should receive 2610 readings');
$existing = array_map(fn ($row, $id) => ['id' => $id, 'created_at' => $row['created_at']], $plan['rows'], array_keys($plan['rows']));
$check(count($planner->build($existing)['rows']) === 0, 'Repeat must add nothing');
$partial = array_slice($existing, 0, 710);
$filled = $planner->build($partial);
$check($filled['days']['2026-10-28']['adding'] === 10, 'Partial day must stop at 720');
$slots = array_column($partial, 'created_at');
foreach ($filled['rows'] as $row) $check(!in_array($row['created_at'], $slots), 'Occupied slot duplicated');
$duplicateSlot = [['id' => 1, 'created_at' => '2026-10-27 16:00:56']];
$check($planner->build($duplicateSlot)['rows'][0]['created_at'] !== '2026-10-27 16:00:00', 'Seconds within a slot must block it');
$overfull = array_fill(0, 721, ['created_at' => '2026-10-27 16:00:00']);
$check($planner->build($overfull)['days']['2026-10-28']['adding'] === 0, 'Overfull day must receive no more readings');
$after = $planner->build([['created_at' => '2026-10-31 08:00:00']]);
$check($after['days']['2026-10-31']['adding'] === 449, 'Post-cutoff existing reading must count');
$check($plan['fingerprint'] !== $after['fingerprint'], 'Changed history must invalidate preview');
echo "PASS: empty, repeat, partial, occupied slots, daily caps, post-cutoff history, and preview fingerprint.\n";
