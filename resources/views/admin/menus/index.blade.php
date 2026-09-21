@extends('admin.layouts.app')

@section('title', __('Navigation Menus'))
@section('page_title', __('Navigation Menus'))

@section('content')
    <div class="row align-items-center mb-5">
        <div class="col-md-8">
            <h2 class="welcome-title mb-1">{{ __('Navigation Menus') }}</h2>
            <p class="text-muted mb-0">{{ __('Create header, footer, and custom menus.') }}</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="{{ route('admin.menus.create') }}" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i>
                <span>{{ __('Create New Menu') }}</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="dashboard-panel premium-shadow overflow-hidden">
        <div class="panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light-soft">
                        <tr>
                            <th class="ps-4 py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Title') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Slug') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1 text-center">{{ __('Items') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1 text-center">{{ __('Status') }}</th>
                            <th class="pe-4 py-3 text-muted small fw-bold uppercase letter-spacing-1 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($menus as $menu)
                            <tr>
                                <td class="ps-4 py-4">
                                    <div class="fw-semibold">{{ $menu->title }}</div>
                                    <div class="small text-muted">{{ __('ID') }}: {{ $menu->id }}</div>
                                </td>
                                <td class="py-4"><code>{{ $menu->slug }}</code></td>
                                <td class="py-4 text-center">{{ (int) $menu->items_count }}</td>
                                <td class="py-4 text-center">
                                    @if($menu->is_active)
                                        <span class="badge-soft badge-success">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge-soft badge-secondary">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="pe-4 py-4 text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('admin.menus.edit', $menu) }}" class="btn btn-icon btn-light-soft" title="{{ __('Edit') }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.menus.destroy', $menu) }}" method="POST" onsubmit="return confirm('{{ __('Delete this menu?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-icon btn-light-soft text-danger" title="{{ __('Delete') }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-5 text-center text-muted">
                                    {{ __('No menus found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 bg-light-soft border-top">
                {{ $menus->links() }}
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
.table thead th {
    font-size: 0.7rem;
    background: var(--admin-surface-2);
    border-bottom: 2px solid var(--admin-border);
}
.badge-soft.badge-success {
    background: var(--success-soft);
    color: var(--success);
}
.badge-soft.badge-secondary {
    background: var(--admin-surface-2);
    color: var(--admin-muted);
}
.table code {
    color: var(--admin-text);
}
.table .text-muted {
    color: var(--admin-muted) !important;
}
</style>
@endpush
