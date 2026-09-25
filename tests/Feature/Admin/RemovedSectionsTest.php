<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Reels and call requests came from the previous project and are not part of the BiteClub admin. */
class RemovedSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reels_and_call_requests_are_gone(): void
    {
        $this->assertFalse(Route::has('admin.reels.index'));
        $this->assertFalse(Route::has('api.website.call-requests.store'));
        $this->postJson('/api/web/call-requests', ['name' => 'x', 'phone' => '555'])->assertNotFound();
    }

    public function test_admin_is_branded_biteclub_without_removed_links(): void
    {
        $this->actingAs($this->createAdminUser(), 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('BiteClub CMS')
            ->assertDontSee('NewHome CMS')
            ->assertDontSee('Reels')
            ->assertDontSee('Call Requests');
    }
}
