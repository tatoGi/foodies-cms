<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MenuRevalidateTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_a_menu_refreshes_the_site_navigation(): void
    {
        config([
            'services.frontend.revalidate_url' => 'http://frontend.test/api/revalidate',
            'services.frontend.revalidate_secret' => 'test-secret',
        ]);
        Http::fake(['http://frontend.test/*' => Http::response(['ok' => true])]);
        $this->createLanguage('ka');
        $menu = Menu::query()->create(['title' => 'Header', 'slug' => 'header', 'is_active' => true]);

        $this->actingAs($this->createAdminUser(), 'admin')
            ->put(route('admin.menus.update', $menu), [
                'title' => 'Header',
                'slug' => 'header',
                'is_active' => 1,
                'items' => [
                    ['type' => 'custom', 'url' => '/menu', 'target' => '_self', 'order' => 1, 'labels' => ['ka' => 'მენიუ']],
                ],
            ])
            ->assertRedirect(route('admin.menus.index'));

        Http::assertSent(fn (Request $request): bool => $request->url() === 'http://frontend.test/api/revalidate'
            && in_array('navigation', $request->data()['tags'] ?? [], true));
    }
}
