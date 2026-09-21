@extends('admin.layouts.app')

@section('title', __('Blocks'))
@section('page_title', __('Blocks'))

@section('content')
    <div class="row align-items-center mb-4">
        <div class="col-lg-8">
            <h2 class="welcome-title mb-1">{{ __('Blocks Management') }}</h2>
            <p class="text-muted mb-0">{{ __('Define and manage reusable content block types.') }}</p>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
            <a href="{{ route('admin.blocks.create') }}" class="btn btn-primary px-4 rounded-3 shadow-premium d-inline-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i>
                <span class="fw-bold">{{ __('Create Block Type') }}</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="dashboard-panel premium-shadow overflow-hidden">
        <div class="panel-header">
            <div class="panel-header-title">
                <i class="bi bi-bounding-box me-2 text-primary"></i>
                <span>{{ __('Block Types') }}</span>
            </div>
        </div>

        <div class="panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light-soft">
                        <tr>
                            <th class="ps-4 py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Icon') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Label') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Key') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Scope') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Type') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1 text-center">{{ __('Status') }}</th>
                            <th class="pe-4 py-3 text-muted small fw-bold uppercase letter-spacing-1 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($blockTypes as $blockType)
                            @php
                                $translatedLabels = data_get($blockType->schema, 'translations.labels', []);
                                $localizedLabel = $translatedLabels[$currentLocale] ?? collect($translatedLabels)->first() ?? $blockType->label;
                            @endphp
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="action-icon bg-primary-soft text-primary">
                                        <i class="bi {{ $blockType->icon ?: 'bi-box' }}"></i>
                                    </div>
                                </td>
                                <td class="py-3 fw-semibold text-dark">{{ $localizedLabel }}</td>
                                <td class="py-3"><code class="text-primary">{{ $blockType->key }}</code></td>
                                <td class="py-3">
                                    <span class="badge bg-light-soft text-muted border text-capitalize px-3">
                                        {{ $blockType->scope }}
                                    </span>
                                </td>
                                <td class="py-3">
                                    <span class="badge {{ $blockType->is_system ? 'bg-primary-soft text-primary' : 'bg-light-soft text-muted border' }} px-3">
                                        {{ $blockType->is_system ? __('System') : __('Custom') }}
                                    </span>
                                </td>
                                <td class="py-3 text-center">
                                    <span class="badge {{ $blockType->is_enabled ? 'bg-success-soft text-success' : 'bg-danger-soft text-danger' }} px-3">
                                        {{ $blockType->is_enabled ? __('Enabled') : __('Disabled') }}
                                    </span>
                                </td>
                                <td class="pe-4 py-3 text-end">
                                    <div class="d-inline-flex gap-2">
                                        <a href="{{ route('admin.blocks.edit', $blockType) }}" class="btn btn-sm btn-light-soft">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.blocks.destroy', $blockType) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('Are you sure?') }}')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-5 text-center text-muted">
                                    <i class="bi bi-bounding-box h1 d-block mb-3 opacity-25"></i>
                                    {{ __('No block types found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
