<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('medical_records', fn (Blueprint $table) =>
            $table->string('reference_photo_path')->nullable()->after('administered_by')
        );
    }

    public function down(): void
    {
        Schema::table('medical_records', fn (Blueprint $table) => $table->dropColumn('reference_photo_path'));
    }
};
