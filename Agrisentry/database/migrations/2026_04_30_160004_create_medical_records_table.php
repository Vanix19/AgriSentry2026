<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goat_id')->constrained('goats')->onDelete('cascade');
            $table->string('record_type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('date_given')->nullable();
            $table->date('next_due_date')->nullable();
            $table->string('administered_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};