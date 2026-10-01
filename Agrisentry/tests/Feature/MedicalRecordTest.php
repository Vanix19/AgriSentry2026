<?php

namespace Tests\Feature;

use App\Models\Goat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MedicalRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_caretaker_can_save_a_medical_record(): void
    {
        $user = User::factory()->create(['role' => 'Caretaker']);
        $goat = Goat::create([
            'name' => 'Test Goat',
            'code' => 'TEST-001',
        ]);

        $response = $this->actingAs($user)->postJson("/api/goats/{$goat->id}/medical-records", [
            'record_type' => 'Treatment',
            'title' => 'Routine treatment',
            'description' => 'Regression test record',
            'date_given' => '2026-09-05',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('medical_record.goat_id', $goat->id)
            ->assertJsonPath('medical_record.title', 'Routine treatment');

        $this->assertDatabaseHas('medical_records', [
            'goat_id' => $goat->id,
            'record_type' => 'Treatment',
            'title' => 'Routine treatment',
        ]);
    }

    public function test_a_medical_record_can_be_saved_with_a_pdf_attachment(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'Staff']);
        $goat = Goat::create(['name' => 'PDF Goat', 'code' => 'TEST-PDF']);
        $pdf = UploadedFile::fake()->createWithContent(
            'vaccination-record.pdf',
            "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF"
        );

        $response = $this->actingAs($user)->post("/api/goats/{$goat->id}/medical-records", [
            'record_type' => 'Vaccination',
            'title' => 'Vaccine',
            'date_given' => '2026-09-05',
            'next_due_date' => '2026-10-05',
            'administered_by' => 'Veterinarian',
            'reference_photo' => $pdf,
        ], ['Accept' => 'application/json']);

        $response->assertCreated();

        $path = $response->json('medical_record.reference_photo_path');
        $this->assertNotEmpty($path);
        $this->assertSame('/storage/'.$path, $response->json('medical_record.reference_photo_url'));
        Storage::disk('public')->assertExists($path);
    }
}
