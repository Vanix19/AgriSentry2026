<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goat_id')->constrained('goats')->onDelete('cascade');
            $table->string('event_type');
            $table->text('description')->nullable();
            $table->decimal('temperature', 5, 2)->nullable();
            $table->string('movement')->nullable();
            $table->string('led_status')->nullable();
            $table->string('severity')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_logs');
    }
};