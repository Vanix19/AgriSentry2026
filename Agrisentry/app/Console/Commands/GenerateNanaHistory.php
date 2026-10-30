<?php

namespace App\Console\Commands;

use App\Models\Goat;
use App\Services\NanaSimulatedHistory;
use DateTimeZone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GenerateNanaHistory extends Command
{
    protected $signature = 'history:simulate-nana {--goat-id=4} {--year=2026} {--timezone=Asia/Shanghai} {--write : Insert the previewed simulated readings}';

    protected $description = 'Preview or insert labeled simulated NANA history for October 28 through October 31 at 15:00';

    public function handle(NanaSimulatedHistory $generator): int
    {
        try {
            $year = filter_var($this->option('year'), FILTER_VALIDATE_INT);
            $goatId = filter_var($this->option('goat-id'), FILTER_VALIDATE_INT);
            if ($year === false || $goatId === false || $goatId < 1) {
                throw new RuntimeException('Supply an integer year and positive goat ID.');
            }
            $timezone = (string) $this->option('timezone');
            [$start, $end] = $generator->bounds($year, $timezone);
            $rows = $generator->rows($goatId, $year, $timezone);
            $utc = new DateTimeZone('UTC');
            $from = $start->setTimezone($utc)->format('Y-m-d H:i:s');
            // Check the entire last day, including records beyond the requested cutoff.
            $until = $end->setTime(0, 0)->modify('+1 day')->setTimezone($utc)->format('Y-m-d H:i:s');
            $written = DB::transaction(function () use ($goatId, $rows, $from, $until) {
                $goat = Goat::whereKey($goatId)->lockForUpdate()->first();
                if (!$goat || strcasecmp(trim($goat->name), 'NANA') !== 0) {
                    throw new RuntimeException('The selected goat must exist and be named NANA.');
                }
                $existing = DB::table('health_logs')->where('goat_id', $goatId)
                    ->where('created_at', '>=', $from)->where('created_at', '<', $until)->orderBy('created_at')->get();
                if ($existing->isNotEmpty()) {
                    // Only an exact previous import is an idempotent no-op.
                    $matches = $existing->count() === count($rows);
                    foreach ($existing as $index => $record) {
                        $expected = $rows[$index] ?? null;
                        if (!$expected) { $matches = false; break; }
                        foreach ($expected as $key => $value) {
                            if ((string) $record->$key !== (string) $value &&
                                !($key === 'temperature' && (float) $record->$key === (float) $value)) {
                                $matches = false;
                                break 2;
                            }
                        }
                    }
                    if ($matches) return 0;
                    throw new RuntimeException('NANA already has history in these dates. No records changed; resolve the overlap before importing.');
                }
                if (!$this->option('write')) return null;
                foreach (array_chunk($rows, 50) as $chunk) {
                    DB::table('health_logs')->insert($chunk);
                }
                return count($rows);
            });
            $this->table(['Local date ('.$timezone.')', 'Temperature readings', 'Motion readings'], [
                ["{$year}-10-28", 720, 720], ["{$year}-10-29", 720, 720],
                ["{$year}-10-30", 720, 720], ["{$year}-10-31 (before 15:00)", 450, 450],
            ]);
            $this->info($written === null ? 'Preview only: 2,610 combined readings. Use --write to insert.'
                : ($written === 0 ? 'Exact simulated history already exists; no changes.' : "Inserted {$written} labeled simulated readings."));
            return self::SUCCESS;
        } catch (\Exception $error) {
            $this->error($error->getMessage());
            return self::FAILURE;
        }
    }
}
