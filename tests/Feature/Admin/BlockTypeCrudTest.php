<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\BlockTypeDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockTypeCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Get a valid block key from the registered CMS block definitions.
     */
    private function validBlockKey(string $scope = 'page'): string
    {
        $definitions = collect((array) config('cms_blocks.definitions', []));

        $key = $definitions
            ->filter(fn ($d): bool => is_array($d) && ($d['scope'] ?? '') === $scope)
            ->pluck('key')
            ->first();

        if ($key === null) {
            // Fallback: any defined key
            $key = $definitions->pluck('key')->first();
        }

        $this->assertNotNull($key, 'No block type keys found in cms_blocks config.');

        return (string) $key;
    }

    private function makeBlockType(string $key, string $scope = 'page'): BlockTypeDefinition
    {
        return BlockTypeDefinition::query()->create([
            'key' => $key,
            'scope' => $scope,
            'label' => 'Test Block',
            'is_enabled' => true,
            'schema' => '[]',
            'default_data' => '[]',
        ]);
    }

    // ─── index ────────────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_blocks_index(): void
    {
        $this->get(route('admin.blocks.index'))->assertRedirect();
    }

    public function test_admin_can_view_blocks_index(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.blocks.index'))
            ->assertOk();
    }

    // ─── create / store ───────────────────────────────────────────────────────

    public function test_admin_can_view_create_block_type(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.blocks.create'))
            ->assertOk();
    }

    public function test_admin_can_store_block_type(): void
    {
        $admin = $this->createAdminUser();
        $key = $this->validBlockKey('page');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.blocks.store'), [
                'key' => $key,
                'scope' => 'page',
                'is_enabled' => true,
                'sort_order' => 1,
                'labels' => ['en' => 'Test Block'],
            ])
            ->assertRedirect(route('admin.blocks.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('block_type_definitions', ['key' => $key]);
    }

    public function test_store_block_type_requires_key(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.blocks.store'), [
                'scope' => 'page',
                'labels' => ['en' => 'Missing Key Block'],
            ])
            ->assertSessionHasErrors('key');
    }

    public function test_store_block_type_rejects_invalid_key(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.blocks.store'), [
                'key' => 'this_key_does_not_exist_in_registry',
                'scope' => 'page',
                'labels' => ['en' => 'Invalid Key'],
            ])
            ->assertSessionHasErrors('key');
    }

    public function test_store_block_type_rejects_duplicate_key(): void
    {
        $admin = $this->createAdminUser();
        $key = $this->validBlockKey('page');

        BlockTypeDefinition::query()->create([
            'key' => $key,
            'scope' => 'page',
            'label' => 'Existing',
            'is_enabled' => true,
            'schema' => '[]',
            'default_data' => '[]',
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.blocks.store'), [
                'key' => $key,
                'scope' => 'page',
                'labels' => ['en' => 'Duplicate'],
            ])
            ->assertSessionHasErrors('key');
    }

    // ─── edit / update ────────────────────────────────────────────────────────

    public function test_admin_can_view_edit_block_type(): void
    {
        $admin = $this->createAdminUser();
        $key = $this->validBlockKey('page');

        $blockType = $this->makeBlockType($key);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.blocks.edit', $blockType->id))
            ->assertOk();
    }

    public function test_admin_can_update_block_type(): void
    {
        $admin = $this->createAdminUser();
        $key = $this->validBlockKey('page');

        $blockType = $this->makeBlockType($key);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.blocks.update', $blockType->id), [
                'key' => $key,
                'scope' => 'page',
                'is_enabled' => false,
                'sort_order' => 5,
                'labels' => ['en' => 'Updated Label'],
            ])
            ->assertRedirect(route('admin.blocks.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('block_type_definitions', ['id' => $blockType->id, 'is_enabled' => false]);
    }

    // ─── destroy ──────────────────────────────────────────────────────────────

    public function test_admin_can_delete_block_type(): void
    {
        $admin = $this->createAdminUser();
        $key = $this->validBlockKey('page');

        $blockType = $this->makeBlockType($key);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.blocks.destroy', $blockType->id))
            ->assertRedirect(route('admin.blocks.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('block_type_definitions', ['id' => $blockType->id]);
    }
}
