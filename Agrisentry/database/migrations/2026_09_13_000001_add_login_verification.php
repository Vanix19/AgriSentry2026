<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('password_otps', fn (Blueprint $table) => $table->string('reset_token_hash', 64)->nullable());
        Schema::create('login_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('challenge_hash', 64)->unique();
            $table->string('code_hash');
            $table->string('password_hash');
            $table->string('channel');
            $table->string('device_name')->default('mobile-device');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
        });
    }
    public function down(): void {
        Schema::dropIfExists('login_otps');
        Schema::table('password_otps', fn (Blueprint $table) => $table->dropColumn('reset_token_hash'));
    }
};
