<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only management of website-registered users (the public `User` model),
 * as opposed to admin-panel accounts (handled by AdminUserController).
 */
class RegisteredUserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->withCount('wishlist')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.registered-users.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    public function show(User $user): View
    {
        $user->loadCount('wishlist');

        return view('admin.registered-users.show', [
            'user' => $user,
        ]);
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', __('User deleted successfully.'));
    }
}
