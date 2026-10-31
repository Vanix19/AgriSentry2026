<?php

use App\Services\NanaMotionCounts;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $goat = DB::table('goats')->where('id', 4)->lockForUpdate()->first();
            if (!$goat || strcasecmp(trim($goat->name), 'NANA') !== 0) return;
            $records = DB::table('health_logs')->where('goat_id', 4)
                ->where('created_at', '>=', '2026-10-27 16:00:00')
                ->where('created_at', '<', '2026-10-29 16:00:00')->orderBy('id')->get()
                ->map(fn ($row) => (array) $row)->all();
            foreach ((new NanaMotionCounts)->changes($records) as $update) {
                DB::table('health_logs')->where('id', $update['id'])->update([
                    'movement' => $update['movement'], 'severity' => $update['severity'],
                ]);
            }
        });
    }

    public function down(): void
    {
        // Preserve historical readings instead of guessing their previous movement.
    }
};
