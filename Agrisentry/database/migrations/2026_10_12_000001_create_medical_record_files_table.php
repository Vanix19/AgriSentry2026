<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

// Medical attachments live in the database because the hosted server's disk is
// wiped on every deploy.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_record_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_record_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('mime_type', 100);
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size');
            $table->binary('content');
            $table->timestamps();
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE medical_record_files MODIFY content LONGBLOB NOT NULL');
        }

        // Copy attachments that exist on this machine's disk into the database.
        $disk = Storage::disk('public');
        foreach (DB::table('medical_records')->whereNotNull('reference_photo_path')->get(['id', 'reference_photo_path']) as $record) {
            if (!$disk->exists($record->reference_photo_path)) continue;
            DB::table('medical_record_files')->insert([
                'medical_record_id' => $record->id,
                'mime_type' => $disk->mimeType($record->reference_photo_path) ?: 'application/octet-stream',
                'original_name' => basename($record->reference_photo_path),
                'size' => $disk->size($record->reference_photo_path),
                'content' => $disk->get($record->reference_photo_path),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_record_files');
    }
};
