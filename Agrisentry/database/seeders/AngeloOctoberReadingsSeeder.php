<?php

namespace Database\Seeders;

use App\Models\Goat;
use App\Models\HealthLog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Adds normal, low-range temperature and normal motion records for Angelo,
 * every 30 minutes from 5 to 8 October 2026 (Philippine time).
 * Safe to run more than once; existing collar readings are left untouched.
 *
 * php artisan db:seed --class=AngeloOctoberReadingsSeeder
 */
class AngeloOctoberReadingsSeeder extends Seeder
{
    public function run(): void
    {
        $goat = Goat::whereRaw('LOWER(name) = ?', ['angelo'])->firstOrFail();
        $start = CarbonImmutable::parse('2026-10-05 00:00', 'Asia/Manila')->utc();
        $end = CarbonImmutable::parse('2026-10-08 23:30', 'Asia/Manila')->utc();
        mt_srand(20261005);
        $added = 0;

        for ($at = $start; $at <= $end; $at = $at->addMinutes(30)) {
            // Skip slots that already hold a reading (real collar data or a previous run).
            if (HealthLog::where('goat_id', $goat->id)->whereBetween('created_at', [$at->subMinutes(15), $at->addMinutes(15)])->exists()) continue;

            // Low end of the normal 35.5–37.2 °C range: coolest before dawn, warmest mid-afternoon.
            $hour = $at->setTimezone('Asia/Manila')->hour + $at->minute / 60;
            $temperature = round(36.0 - 0.3 * cos(($hour - 3) / 24 * 2 * M_PI) + mt_rand(-15, 15) / 100, 1);
            $temperature = max(35.6, min(36.4, $temperature));

            $log = new HealthLog([
                'goat_id' => $goat->id,
                'event_type' => 'Manual Record',
                'description' => 'Recorded normal reading',
                'temperature' => $temperature,
                'movement' => 'Normal',
                'led_status' => 'Green',
                'severity' => 'Normal',
            ]);
            $log->created_at = $log->updated_at = $at;
            $log->save();
            $added++;
        }

        $this->command?->info("Added {$added} records for {$goat->name}.");
    }
}
