@extends('admin.layouts.app')

@section('title', __('Admin Users'))
@section('page_title', __('Admin Users'))

@section('content')
    <div class="row align-items-center mb-4">
        <div class="col-lg-8">
            <h2 class="welcome-title mb-1">{{ __('Admin Users') }}</h2>
            <p class="text-muted mb-0">{{ __('Manage admin panel accounts and role assignments.') }}</p>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0 d-flex justify-content-lg-end gap-2 flex-wrap">
            <span class="badge rounded-pill bg-light-soft text-primary border px-3 py-2 align-self-center">
                {{ __('Total') }}: {{ $users->total() }}
            </span>
            <a href="{{ route('admin.admins.create') }}" class="btn btn-primary px-4">
                <i class="bi bi-plus-lg me-1"></i>{{ __('Add User') }}
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
                            <th class="ps-4 py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Name') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Email') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Role') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Status') }}</th>
                            <th class="pe-4 py-3 text-muted small fw-bold uppercase letter-spacing-1 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td class="ps-4 py-3 fw-semibold">{{ $user->name }}</td>
                                <td class="py-3">{{ $user->email }}</td>
                                <td class="py-3">
                                    <span class="badge bg-light-soft text-primary border">
                                        {{ $user->role?->name ?? __('No Role') }}
                                    </span>
                                </td>
                                <td class="py-3">
                                    <span class="badge {{ $user->is_active ? 'bg-success-soft text-success border' : 'bg-danger-soft text-danger border' }}">
                                        {{ $user->is_active ? __('Active') : __('Inactive') }}
                                    </span>
                                </td>
                                <td class="pe-4 py-3 text-end">
                                    <div class="d-inline-flex gap-2">
                                        <a href="{{ route('admin.admins.edit', $user) }}" class="btn btn-sm btn-light-soft">
                                            <i class="bi bi-pencil me-1"></i>{{ __('Edit') }}
                                        </a>
                                        <form method="POST" action="{{ route('admin.admins.destroy', $user) }}" onsubmit="return confirm('{{ __('Delete this user?') }}');">
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
                                    <i class="bi bi-people h1 d-block mb-3 opacity-25"></i>
                                    {{ __('No users found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($users->hasPages())
            <div class="panel-body border-top">
                {{ $users->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
