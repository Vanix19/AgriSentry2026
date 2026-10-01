<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPhoneNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_the_same_contact_twice_does_not_duplicate_it(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $user = User::factory()->create();
        $this->actingAs($admin)->postJson('/api/users/'.$user->id.'/phone-numbers', ['phone_number' => '09516994607'])->assertCreated();
        $this->postJson('/api/users/'.$user->id.'/phone-numbers', ['phone_number' => '09516994607'])->assertOk();
        $this->assertSame(1, $user->phoneNumbers()->count());
        $this->assertEquals(1, $user->phoneNumbers()->first()->is_primary);
    }
}
