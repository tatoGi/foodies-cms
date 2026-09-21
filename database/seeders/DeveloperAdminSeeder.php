<?php

namespace Database\Seeders;

use App\Models\AdminPermission;
use App\Models\AdminRole;
use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DeveloperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect([
            ['key' => 'dashboard.view', 'label' => 'View Dashboard', 'group' => 'dashboard'],
            ['key' => 'pages.manage', 'label' => 'Manage Pages', 'group' => 'pages'],
            ['key' => 'posts.manage', 'label' => 'Manage Posts', 'group' => 'posts'],
            ['key' => 'products.manage', 'label' => 'Manage Products', 'group' => 'products'],
            ['key' => 'templates.manage', 'label' => 'Manage Templates', 'group' => 'templates'],
            ['key' => 'blocks.manage', 'label' => 'Manage Blocks', 'group' => 'blocks'],
            ['key' => 'menus.manage', 'label' => 'Manage Menus', 'group' => 'menus'],
            ['key' => 'languages.manage', 'label' => 'Manage Languages', 'group' => 'languages'],
            ['key' => 'media.manage', 'label' => 'Manage Media', 'group' => 'media'],
            ['key' => 'sales.manage', 'label' => 'View Sales and Orders', 'group' => 'sales'],
            ['key' => 'users.manage', 'label' => 'Manage Users', 'group' => 'users'],
            ['key' => 'roles.manage', 'label' => 'Manage Roles', 'group' => 'roles'],
            ['key' => 'settings.manage', 'label' => 'Manage Settings', 'group' => 'settings'],
        ])->map(function (array $permission) {
            return AdminPermission::query()->updateOrCreate(
                ['key' => $permission['key']],
                [
                    'label' => $permission['label'],
                    'group' => $permission['group'],
                ]
            );
        });

        $role = AdminRole::firstOrCreate(
            ['slug' => 'developer'],
            ['name' => 'Developer', 'description' => 'Full access developer role']
        );

        if ($permissions->isNotEmpty()) {
            $role->permissions()->syncWithoutDetaching($permissions->pluck('id')->all());
        }

        $email = env('ADMIN_EMAIL', 'dev@example.com');
        $password = env('ADMIN_PASSWORD', 'password');
        $name = env('ADMIN_NAME', 'Developer');

        AdminUser::firstOrCreate(
            ['email' => $email],
            [
                'role_id' => $role->id,
                'name' => $name,
                'password' => Hash::make($password),
                'is_active' => true,
            ]
        );
    }
}
