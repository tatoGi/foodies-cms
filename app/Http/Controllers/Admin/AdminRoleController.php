<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminRoleRequest;
use App\Http\Requests\Admin\UpdateAdminRoleRequest;
use App\Models\AdminPermission;
use App\Models\AdminRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminRoleController extends Controller
{
    public function index(): View
    {
        $roles = AdminRole::query()
            ->withCount(['users', 'permissions'])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.roles.index', [
            'roles' => $roles,
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'permissionsByGroup' => $this->permissionsByGroup(),
        ]);
    }

    public function store(StoreAdminRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated): void {
            $slug = trim((string) ($validated['slug'] ?? ''));
            if ($slug === '') {
                $slug = Str::slug((string) $validated['name']);
            }
            if ($slug === '') {
                $slug = 'role-'.Str::lower((string) Str::random(8));
            }

            $role = AdminRole::query()->create([
                'name' => trim((string) $validated['name']),
                'slug' => $slug,
                'description' => trim((string) ($validated['description'] ?? '')) ?: null,
            ]);

            $permissionIds = $this->resolvedPermissionIds($validated);
            $role->permissions()->sync($permissionIds);
        });

        return redirect()
            ->route('admin.roles.index')
            ->with('success', __('Role created successfully.'));
    }

    public function edit(AdminRole $role): View
    {
        $role->load('permissions:id');

        return view('admin.roles.edit', [
            'role' => $role,
            'permissionsByGroup' => $this->permissionsByGroup(),
            'selectedPermissionIds' => $role->permissions->pluck('id')->map(static fn ($id): int => (int) $id)->all(),
        ]);
    }

    public function update(UpdateAdminRoleRequest $request, AdminRole $role): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $role): void {
            $slug = trim((string) ($validated['slug'] ?? ''));
            if ($slug === '') {
                $slug = Str::slug((string) $validated['name']);
            }
            if ($slug === '') {
                $slug = 'role-'.Str::lower((string) Str::random(8));
            }

            $role->update([
                'name' => trim((string) $validated['name']),
                'slug' => $slug,
                'description' => trim((string) ($validated['description'] ?? '')) ?: null,
            ]);

            $permissionIds = $this->resolvedPermissionIds($validated);
            $role->permissions()->sync($permissionIds);
        });

        return redirect()
            ->route('admin.roles.index')
            ->with('success', __('Role updated successfully.'));
    }

    public function destroy(AdminRole $role): RedirectResponse
    {
        if ($role->users()->exists()) {
            return back()->with('error', __('Cannot delete role assigned to users.'));
        }

        $role->permissions()->detach();
        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', __('Role deleted successfully.'));
    }

    /**
     * @return array<string, Collection<int, AdminPermission>>
     */
    private function permissionsByGroup(): array
    {
        /** @var Collection<int, AdminPermission> $permissions */
        $permissions = AdminPermission::query()
            ->orderBy('group')
            ->orderBy('label')
            ->get();

        return $permissions
            ->groupBy(static fn (AdminPermission $permission): string => (string) ($permission->group ?: 'general'))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int, int>
     */
    private function resolvedPermissionIds(array $validated): array
    {
        $selectedIds = collect((array) ($validated['permission_ids'] ?? []))
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values();

        $keysInput = trim((string) ($validated['permission_keys'] ?? ''));
        if ($keysInput !== '') {
            $keys = collect(preg_split('/[\r\n,]+/', $keysInput) ?: [])
                ->map(static fn ($key): string => Str::of($key)->trim()->lower()->replace(' ', '.')->toString())
                ->filter(static fn (string $key): bool => $key !== '')
                ->unique()
                ->values();

            foreach ($keys as $key) {
                $permission = AdminPermission::query()->firstOrCreate(
                    ['key' => $key],
                    [
                        'label' => Str::headline(str_replace('.', ' ', $key)),
                        'group' => str_contains($key, '.') ? (string) Str::before($key, '.') : 'general',
                    ]
                );

                $selectedIds->push((int) $permission->id);
            }
        }

        return $selectedIds->unique()->values()->all();
    }
}
