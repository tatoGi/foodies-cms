<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PageHierarchyReorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reorder_pages_and_assign_parent_with_tree_payload(): void
    {
        $admin = $this->createAdmin();

        $pageA = $this->createPage('a', 0);
        $pageB = $this->createPage('b', 1);
        $pageC = $this->createPage('c', 2);

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.pages.reorder'), [
                'tree' => [
                    [
                        'id' => $pageC->id,
                        'children' => [
                            ['id' => $pageA->id],
                        ],
                    ],
                    [
                        'id' => $pageB->id,
                        'children' => [],
                    ],
                ],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('pages', [
            'id' => $pageC->id,
            'parent_id' => null,
            'sort_order' => 0,
        ]);

        $this->assertDatabaseHas('pages', [
            'id' => $pageA->id,
            'parent_id' => $pageC->id,
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('pages', [
            'id' => $pageB->id,
            'parent_id' => null,
            'sort_order' => 2,
        ]);
    }

    private function createAdmin(): AdminUser
    {
        $role = AdminRole::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'description' => 'Test role',
        ]);

        return AdminUser::query()->create([
            'role_id' => $role->id,
            'name' => 'Admin',
            'email' => 'admin-'.uniqid().'@example.test',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
    }

    private function createPage(string $suffix, int $sortOrder): Page
    {
        $page = Page::query()->create([
            'parent_id' => null,
            'template' => 'default',
            'block_types' => [],
            'sort_order' => $sortOrder,
            'published' => true,
        ]);

        $page->translations()->create([
            'locale' => 'ka',
            'title' => 'Page '.$suffix,
            'slug' => 'page-'.$suffix,
        ]);

        return $page;
    }
}
