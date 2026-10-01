<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goats', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('breed')->nullable();
            $table->string('age')->nullable();
            $table->string('sex')->nullable();
            $table->string('weight')->nullable();
            $table->string('owner')->nullable();
            $table->string('barn')->nullable();
            $table->string('collar_id')->nullable();
            $table->decimal('temperature', 5, 2)->nullable();
            $table->string('movement')->nullable();
            $table->string('battery')->nullable();
            $table->string('status')->default('Normal');
            $table->text('alert_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goats');
    }
};