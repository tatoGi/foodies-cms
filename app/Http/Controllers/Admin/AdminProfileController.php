<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminPasswordRequest;
use App\Http\Requests\Admin\UpdateAdminProfileRequest;
use App\Models\AdminUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminProfileController extends Controller
{
    public function edit(): View
    {
        /** @var AdminUser $adminUser */
        $adminUser = auth('admin')->user();

        $adminUser->loadMissing('role:id,name,slug');

        return view('admin.profile.edit', [
            'adminUser' => $adminUser,
        ]);
    }

    public function update(UpdateAdminProfileRequest $request): RedirectResponse
    {
        /** @var AdminUser $adminUser */
        $adminUser = auth('admin')->user();

        $validated = $request->validated();

        $adminUser->update([
            'name' => trim((string) $validated['name']),
            'email' => strtolower(trim((string) $validated['email'])),
        ]);

        return redirect()
            ->route('admin.profile.edit')
            ->with('success', __('Profile updated successfully.'));
    }

    public function updatePassword(UpdateAdminPasswordRequest $request): RedirectResponse
    {
        /** @var AdminUser $adminUser */
        $adminUser = auth('admin')->user();

        $validated = $request->validated();

        $adminUser->update([
            'password' => Hash::make((string) $validated['new_password']),
        ]);

        return redirect()
            ->route('admin.profile.edit', ['tab' => 'security'])
            ->with('success', __('Password updated successfully.'));
    }
}
