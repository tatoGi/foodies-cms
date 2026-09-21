@extends('admin.layouts.app')

@section('title', __('Page Templates'))
@section('page_title', __('Page Templates'))

@section('content')
    <div class="row align-items-center mb-5">
        <div class="col-md-8">
            <h2 class="welcome-title mb-1">{{ __('Page Templates') }}</h2>
            <p class="text-muted mb-0">{{ __('Define layouts and structures for your pages.') }}</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="{{ route('admin.page-templates.create') }}" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i>
                <span>{{ __('Create Template') }}</span>
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
        <div class="panel-body p-0">
            <div class="table-responsive" data-mobile-columns="true">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light-soft">
                        <tr>
                            <th class="ps-4 py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Template Name') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1 table-col-secondary">{{ __('Slug') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1 text-center table-col-secondary">{{ __('Locales') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1 text-center">{{ __('Pages Used') }}</th>
                            <th class="pe-4 py-3 text-muted small fw-bold uppercase letter-spacing-1 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                            @php
                                $preferred = $template->translations->firstWhere('locale', $currentLocale);
                                $displayName = $preferred?->name ?? $template->translations->first()?->name ?? $template->slug;
                                $displaySlug = $template->slug;
                            @endphp
                            <tr>
                                <td class="ps-4 py-4">
                                    <h6 class="mb-0 fw-bold">{{ $displayName }}</h6>
                                </td>
                                <td class="py-4 table-col-secondary"><code>{{ $displaySlug }}</code></td>
                                <td class="py-4 text-center table-col-secondary">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        @foreach($template->translations as $translation)
                                            <span class="badge bg-light-soft text-primary border px-2 py-1">
                                                {{ strtoupper($translation->locale) }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="py-4 text-center">
                                    <span class="badge rounded-pill bg-light-soft text-primary border px-3">
                                        {{ $template->pages_count }}
                                    </span>
                                </td>
                                <td class="pe-4 py-4 text-end">
                                    <div class="table-actions-inline d-flex justify-content-end gap-2">
                                        <a href="{{ route('admin.page-templates.edit', $template) }}" class="btn btn-icon btn-light-soft" title="{{ __('Edit') }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.page-templates.destroy', $template) }}" method="POST" onsubmit="return confirm('{{ __('Are you sure you want to delete this template?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-icon btn-light-soft text-danger" title="{{ __('Delete') }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <div class="dropdown table-actions-menu">
                                        <button class="btn btn-sm btn-light-soft dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            {{ __('Actions') }}
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end premium-dropdown">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('admin.page-templates.edit', $template) }}">
                                                    <i class="bi bi-pencil me-2"></i>{{ __('Edit') }}
                                                </a>
                                            </li>
                                            <li>
                                                <form action="{{ route('admin.page-templates.destroy', $template) }}" method="POST" onsubmit="return confirm('{{ __('Are you sure you want to delete this template?') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="bi bi-trash me-2"></i>{{ __('Delete') }}
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-5 text-center text-muted">
                                    <i class="bi bi-layers h1 d-block mb-3 opacity-25"></i>
                                    {{ __('No templates found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
