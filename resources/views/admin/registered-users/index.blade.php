@extends('admin.layouts.app')

@section('title', __('Users'))
@section('page_title', __('Users'))

@section('content')
    <div class="row align-items-center mb-4">
        <div class="col-lg-6">
            <h2 class="welcome-title mb-1">{{ __('Registered Users') }}</h2>
            <p class="text-muted mb-0">{{ __('Customers who registered on the website.') }}</p>
        </div>
        <div class="col-lg-6 text-lg-end mt-3 mt-lg-0 d-flex justify-content-lg-end gap-2 flex-wrap align-items-center">
            <span class="badge rounded-pill bg-light-soft text-primary border px-3 py-2 align-self-center">
                {{ __('Total') }}: {{ $users->total() }}
            </span>
            <form method="GET" class="d-flex gap-2" role="search">
                <input type="search" name="search" value="{{ $search }}" class="form-control form-control-sm"
                       placeholder="{{ __('Search name, email, phone') }}" style="min-width: 220px;">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-search"></i>
                </button>
            </form>
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
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Phone') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Wishlist') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Registered') }}</th>
                            <th class="pe-4 py-3 text-muted small fw-bold uppercase letter-spacing-1 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td class="ps-4 py-3 fw-semibold">{{ $user->name }}</td>
                                <td class="py-3">{{ $user->email }}</td>
                                <td class="py-3">{{ $user->phone ?: '—' }}</td>
                                <td class="py-3">
                                    <span class="badge bg-light-soft text-primary border">{{ $user->wishlist_count }}</span>
                                </td>
                                <td class="py-3 text-muted small">{{ $user->created_at?->format('Y-m-d') ?? '—' }}</td>
                                <td class="pe-4 py-3 text-end">
                                    <div class="d-inline-flex gap-2">
                                        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-light-soft">
                                            <i class="bi bi-eye me-1"></i>{{ __('View') }}
                                        </a>
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('{{ __('Delete this user?') }}');">
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
                                <td colspan="6" class="py-5 text-center text-muted">
                                    <i class="bi bi-people h1 d-block mb-3 opacity-25"></i>
                                    {{ __('No registered users found.') }}
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
