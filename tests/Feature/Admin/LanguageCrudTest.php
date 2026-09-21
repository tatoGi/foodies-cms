<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageCrudTest extends TestCase
{
    use RefreshDatabase;

    // ─── index ────────────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_languages_index(): void
    {
        $this->get(route('admin.languages.index'))->assertRedirect();
    }

    public function test_admin_can_view_languages_index(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.languages.index'))
            ->assertOk();
    }

    // ─── create / store ───────────────────────────────────────────────────────

    public function test_admin_can_view_create_language(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.languages.create'))
            ->assertOk();
    }

    public function test_admin_can_store_language(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.languages.store'), [
                'name' => 'English',
                'english_name' => 'English',
                'code' => 'en',
                'country_code' => 'US',
                'direction' => 'ltr',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.languages.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('languages', ['code' => 'en']);
    }

    public function test_store_language_requires_name(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.languages.store'), [
                'english_name' => 'Georgian',
                'code' => 'ka',
                'country_code' => 'GE',
                'direction' => 'ltr',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_store_language_rejects_duplicate_code(): void
    {
        $admin = $this->createAdminUser();
        $this->createLanguage('ka');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.languages.store'), [
                'name' => 'Georgian 2',
                'english_name' => 'Georgian 2',
                'code' => 'ka',
                'country_code' => 'GE',
                'direction' => 'ltr',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_store_language_requires_two_letter_code(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.languages.store'), [
                'name' => 'Test',
                'english_name' => 'Test',
                'code' => 'eng', // 3 letters — invalid
                'country_code' => 'US',
                'direction' => 'ltr',
            ])
            ->assertSessionHasErrors('code');
    }

    // ─── edit / update ────────────────────────────────────────────────────────

    public function test_admin_can_view_edit_language(): void
    {
        $admin = $this->createAdminUser();
        $language = $this->createLanguage('en');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.languages.edit', $language))
            ->assertOk();
    }

    public function test_admin_can_update_language(): void
    {
        $admin = $this->createAdminUser();
        $language = $this->createLanguage('en');

        $this->actingAs($admin, 'admin')
            ->put(route('admin.languages.update', $language), [
                'name' => 'English Updated',
                'english_name' => 'English',
                'code' => 'en',
                'country_code' => 'GB',
                'direction' => 'ltr',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.languages.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('languages', ['id' => $language->id, 'country_code' => 'GB']);
    }

    public function test_cannot_deactivate_default_language(): void
    {
        $admin = $this->createAdminUser();
        $language = $this->createLanguage('en', isDefault: true);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.languages.update', $language), [
                'name' => 'English',
                'english_name' => 'English',
                'code' => 'en',
                'country_code' => 'US',
                'direction' => 'ltr',
                'is_active' => false, // Trying to deactivate default
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    // ─── destroy ──────────────────────────────────────────────────────────────

    public function test_admin_can_delete_non_default_language(): void
    {
        if (config('database.default') === 'sqlite') {
            // LanguageController::destroy uses information_schema.COLUMNS (MySQL-only).
            // This test must run against a real MySQL database.
            $this->markTestSkipped('Requires MySQL — information_schema not available in SQLite.');
        }

        $admin = $this->createAdminUser();
        $language = $this->createLanguage('fr', isDefault: false);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.languages.destroy', $language))
            ->assertRedirect(route('admin.languages.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('languages', ['id' => $language->id]);
    }

    public function test_cannot_delete_default_language(): void
    {
        $admin = $this->createAdminUser();
        $language = $this->createLanguage('en', isDefault: true);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.languages.destroy', $language))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('languages', ['id' => $language->id]);
    }

    // ─── toggle active ────────────────────────────────────────────────────────

    public function test_admin_can_toggle_language_active(): void
    {
        $admin = $this->createAdminUser();
        $language = $this->createLanguage('fr', isDefault: false);

        $this->assertTrue($language->is_active);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.languages.toggle-active', $language), ['active' => false])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($language->fresh()->is_active);
    }

    public function test_cannot_deactivate_default_language_via_toggle(): void
    {
        $admin = $this->createAdminUser();
        $language = $this->createLanguage('en', isDefault: true);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.languages.toggle-active', $language), ['active' => false])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue($language->fresh()->is_active);
    }

    // ─── set default ─────────────────────────────────────────────────────────

    public function test_admin_can_set_default_language(): void
    {
        $admin = $this->createAdminUser();
        $en = $this->createLanguage('en', isDefault: true);
        $ka = $this->createLanguage('ka', isDefault: false);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.languages.set-default', $ka))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($en->fresh()->is_default);
        $this->assertTrue($ka->fresh()->is_default);
        $this->assertTrue($ka->fresh()->is_active); // set-default also activates
    }

    // ─── sort order ───────────────────────────────────────────────────────────

    public function test_admin_can_update_language_sort_order(): void
    {
        $admin = $this->createAdminUser();
        $en = $this->createLanguage('en', isDefault: true);
        $ka = Language::query()->create([
            'code' => 'ka', 'name' => 'KA', 'english_name' => 'Georgian',
            'country_code' => 'GE', 'direction' => 'ltr', 'is_active' => true,
            'is_default' => false, 'sort_order' => 2,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.languages.sort-order'), [
                'ordered_ids' => [$ka->id, $en->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(1, $ka->fresh()->sort_order);
        $this->assertEquals(2, $en->fresh()->sort_order);
    }
}
