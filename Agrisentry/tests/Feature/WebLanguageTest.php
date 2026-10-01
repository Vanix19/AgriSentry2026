<?php

namespace Tests\Feature;

use App\Models\Goat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebLanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_language_support_is_loaded_on_account_and_herd_pages(): void
    {
        foreach (['/login', '/password/recovery'] as $path) {
            $this->get($path)->assertOk()->assertSee('/js/system-translations.js', false)->assertSee('/js/dashboard-language.js', false);
        }
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        $goat = Goat::create(['name' => 'Luna', 'code' => 'GT-900']);
        foreach (['/species', '/agrisentry', '/settings', '/password/change', '/admin/users', '/admin/access', '/goat/'.$goat->id.'/profile'] as $path) {
            $page = $this->get($path)->assertOk();
            $page->assertSee('/js/system-translations.js', false)->assertSee('/js/dashboard-language.js', false);
            $this->assertSame(1, substr_count($page->getContent(), '/js/dashboard-language.js'));
        }
    }
}
