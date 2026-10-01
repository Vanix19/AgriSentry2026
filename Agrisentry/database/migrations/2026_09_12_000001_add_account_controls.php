<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('password_change_required')->default(true);
        });
        Schema::table('goats', function (Blueprint $table) {
            $table->string('ear_tag')->nullable();
            $table->string('color')->nullable();
        });
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role')->unique();
            $table->json('permissions');
        });
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('username');
            $table->string('action');
            $table->string('path');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
        Schema::create('password_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('password_otps');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('role_permissions');
        Schema::table('goats', fn (Blueprint $table) => $table->dropColumn(['ear_tag', 'color']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('password_change_required'));
    }
};
