<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminUserRequest;
use App\Http\Requests\Admin\UpdateAdminUserRequest;
use App\Models\AdminRole;
use App\Models\AdminUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        $users = AdminUser::query()
            ->with('role')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.admins.index', [
            'users' => $users,
        ]);
    }

    public function create(): View
    {
        $roles = AdminRole::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('admin.admins.create', [
            'roles' => $roles,
        ]);
    }

    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        AdminUser::query()->create([
            'name' => trim((string) $validated['name']),
            'email' => strtolower(trim((string) $validated['email'])),
            'role_id' => (int) $validated['role_id'],
            'password' => Hash::make((string) $validated['password']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.admins.index')
            ->with('success', __('User created successfully.'));
    }

    public function edit(AdminUser $user): View
    {
        $roles = AdminRole::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('admin.admins.edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    public function update(UpdateAdminUserRequest $request, AdminUser $user): RedirectResponse
    {
        $validated = $request->validated();

        $payload = [
            'name' => trim((string) $validated['name']),
            'email' => strtolower(trim((string) $validated['email'])),
            'role_id' => (int) $validated['role_id'],
            'is_active' => $request->boolean('is_active', false),
        ];

        $password = (string) ($validated['password'] ?? '');
        if ($password !== '') {
            $payload['password'] = Hash::make($password);
        }

        $user->update($payload);

        return redirect()
            ->route('admin.admins.index')
            ->with('success', __('User updated successfully.'));
    }

    public function destroy(AdminUser $user): RedirectResponse
    {
        $currentAdminId = (int) (auth('admin')->id() ?? 0);
        if ((int) $user->id === $currentAdminId) {
            return back()->with('error', __('You cannot delete your own account.'));
        }

        if (AdminUser::query()->count() <= 1) {
            return back()->with('error', __('At least one admin user is required.'));
        }

        $user->delete();

        return redirect()
            ->route('admin.admins.index')
            ->with('success', __('User deleted successfully.'));
    }
}
