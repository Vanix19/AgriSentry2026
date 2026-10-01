<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_id')->nullable()->constrained('alerts')->onDelete('cascade');
            $table->foreignId('caretaker_phone_number_id')->nullable()->constrained('caretaker_phone_numbers')->onDelete('set null');
            $table->string('phone_number', 20);
            $table->text('message');
            $table->string('status')->default('Pending');
            $table->text('provider_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_notifications');
    }
};
