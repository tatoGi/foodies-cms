<?php

declare(strict_types=1);

namespace Tests;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Language;
use App\Models\PageTemplate;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    protected function createAdminUser(): AdminUser
    {
        $role = AdminRole::query()->firstOrCreate(
            ['slug' => 'test-admin'],
            ['name' => 'Test Admin', 'description' => 'Test role'],
        );

        return AdminUser::query()->create([
            'role_id' => $role->id,
            'name' => 'Test Admin',
            'email' => 'testadmin@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
    }

    protected function createLanguage(string $code = 'en', bool $isDefault = true): Language
    {
        return Language::query()->create([
            'code' => $code,
            'name' => strtoupper($code),
            'english_name' => strtoupper($code),
            'country_code' => strtoupper($code),
            'direction' => 'ltr',
            'is_active' => true,
            'is_default' => $isDefault,
            'sort_order' => 1,
        ]);
    }

    protected function createPageTemplate(string $slug = 'default'): PageTemplate
    {
        return PageTemplate::query()->firstOrCreate(['slug' => $slug]);
    }
}
