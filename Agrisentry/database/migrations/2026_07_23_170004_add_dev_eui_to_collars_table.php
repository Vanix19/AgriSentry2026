<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collars', function (Blueprint $table) {
            $table->string('dev_eui')->nullable()->unique()->after('collar_code');
        });
    }

    public function down(): void
    {
        Schema::table('collars', function (Blueprint $table) {
            $table->dropColumn('dev_eui');
        });
    }
};
