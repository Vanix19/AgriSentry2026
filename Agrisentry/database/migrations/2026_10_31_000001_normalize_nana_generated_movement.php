<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Normalize only generated NANA history, preserving real collar measurements.
        DB::table('health_logs')->where('goat_id', 4)
            ->where('event_type', 'Simulated Reading')
            ->where('description', 'like', 'Simulated NANA October history v1;%')
            ->where('movement', 'Low Movement')
            ->update(['movement' => 'Normal']);
    }

    public function down(): void
    {
        // Keep normalized historical labels on rollback; do not guess prior movements.
    }
};
