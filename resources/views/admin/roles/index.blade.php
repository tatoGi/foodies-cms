@extends('admin.layouts.app')

@section('title', __('Roles & Permissions'))
@section('page_title', __('Roles & Permissions'))

@section('content')
    <div class="row align-items-center mb-4">
        <div class="col-lg-8">
            <h2 class="welcome-title mb-1">{{ __('Roles & Permissions') }}</h2>
            <p class="text-muted mb-0">{{ __('Manage roles and map permissions for admin users.') }}</p>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0 d-flex justify-content-lg-end gap-2 flex-wrap">
            <span class="badge rounded-pill bg-light-soft text-primary border px-3 py-2 align-self-center">
                {{ __('Total') }}: {{ $roles->total() }}
            </span>
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary px-4">
                <i class="bi bi-plus-lg me-1"></i>{{ __('Add Role') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">{{ session('error') }}</div>
    @endif

    <div class="dashboard-panel premium-shadow overflow-hidden">
        <div class="panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light-soft">
                        <tr>
                            <th class="ps-4 py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Role') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Slug') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Users') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Permissions') }}</th>
                            <th class="pe-4 py-3 text-muted small fw-bold uppercase letter-spacing-1 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $role)
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="fw-semibold">{{ $role->name }}</div>
                                    @if($role->description)
                                        <div class="small text-muted">{{ $role->description }}</div>
                                    @endif
                                </td>
                                <td class="py-3"><code>{{ $role->slug }}</code></td>
                                <td class="py-3">
                                    <span class="badge bg-light-soft text-primary border">{{ $role->users_count }}</span>
                                </td>
                                <td class="py-3">
                                    <span class="badge bg-light-soft text-muted border">{{ $role->permissions_count }}</span>
                                </td>
                                <td class="pe-4 py-3 text-end">
                                    <div class="d-inline-flex gap-2">
                                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-light-soft">
                                            <i class="bi bi-pencil me-1"></i>{{ __('Edit') }}
                                        </a>
                                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('{{ __('Delete this role?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash me-1"></i>{{ __('Delete') }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-5 text-center text-muted">
                                    <i class="bi bi-shield-lock h1 d-block mb-3 opacity-25"></i>
                                    {{ __('No roles found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($roles->hasPages())
            <div class="panel-body border-top">
                {{ $roles->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
