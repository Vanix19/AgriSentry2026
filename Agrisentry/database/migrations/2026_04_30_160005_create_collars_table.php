<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goat_id')->nullable()->constrained('goats')->onDelete('set null');
            $table->string('collar_code')->unique();
            $table->integer('battery_level')->default(100);
            $table->string('device_status')->default('Active');
            $table->timestamp('last_seen')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collars');
    }
};