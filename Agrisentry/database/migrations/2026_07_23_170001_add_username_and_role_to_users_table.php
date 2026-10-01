<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('role')->default('Caretaker')->after('username');
        });

        // Backfill any pre-existing rows (e.g. the seeded "Test User") so the
        // unique constraint doesn't collide and no demo data needs wiping.
        DB::table('users')->whereNull('username')->orderBy('id')->get()->each(function ($user) {
            DB::table('users')->where('id', $user->id)->update(['username' => 'user'.$user->id]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'role']);
        });
    }
};
