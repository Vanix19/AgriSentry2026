<?php

namespace Tests\Feature;

use App\Models\Goat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoatPregnancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_pregnancy_can_be_created_changed_and_read_by_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $response = $this->postJson('/api/goats', [
            'name' => 'Luna', 'ear_tag' => 'EAR-100', 'sex' => 'Female', 'pregnancy_status' => 'pregnant',
        ])->assertCreated()->assertJsonPath('goat.pregnancy_status', 'pregnant');
        $id = $response->json('goat.id');
        $this->getJson('/api/goats')->assertOk()->assertJsonPath('goats.0.pregnancy_status', 'pregnant');
        $this->putJson('/api/goats/'.$id, ['name' => 'Luna updated'])->assertOk()->assertJsonPath('goat.pregnancy_status', 'pregnant');
        $this->putJson('/api/goats/'.$id, ['pregnancy_status' => 'not_pregnant'])->assertOk();
        $this->assertDatabaseHas('goats', ['id' => $id, 'pregnancy_status' => 'not_pregnant']);
        $this->getJson('/api/goats/'.$id)->assertOk()->assertJsonPath('goat.pregnancy_status', 'not_pregnant');
        $this->putJson('/api/goats/'.$id, ['pregnancy_status' => 'unknown'])->assertOk();
    }

    public function test_unspecified_pregnancy_is_unknown_and_invalid_values_are_rejected(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $goat = Goat::create(['name' => 'Luna', 'code' => 'EAR-101']);
        $this->getJson('/api/goats/'.$goat->id)->assertOk()->assertJsonPath('goat.pregnancy_status', 'unknown');
        foreach (['invalid', '', null] as $value) {
            $this->putJson('/api/goats/'.$goat->id, ['pregnancy_status' => $value])
                ->assertUnprocessable()->assertJsonValidationErrors('pregnancy_status');
        }
    }

    public function test_male_goats_cannot_be_marked_pregnant(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $this->postJson('/api/goats', ['name' => 'Buck', 'ear_tag' => 'EAR-102', 'sex' => 'Male', 'pregnancy_status' => 'pregnant'])
            ->assertUnprocessable()->assertJsonValidationErrors('pregnancy_status');
        $goat = Goat::create(['name' => 'Doe', 'code' => 'EAR-103', 'sex' => 'Female', 'pregnancy_status' => 'pregnant']);
        $this->putJson('/api/goats/'.$goat->id, ['sex' => 'Male'])->assertUnprocessable()->assertJsonValidationErrors('pregnancy_status');
        $this->putJson('/api/goats/'.$goat->id, ['sex' => 'Male', 'pregnancy_status' => 'not_pregnant'])->assertOk();
    }
}
