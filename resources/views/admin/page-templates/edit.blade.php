@extends('admin.layouts.app')

@section('title', __('Edit Page Template'))
@section('page_title', __('Edit Template'))

@php
    $selectedLocaleCodes = collect($selectedLocaleCodes ?? [])->map(static fn ($code): string => (string) $code)->values()->all();
    $firstSelectedLocaleCode = collect($locales)->pluck('code')->first(
        static fn ($code): bool => in_array((string) $code, $selectedLocaleCodes, true)
    );
@endphp

@section('content')
    <div class="row mb-5">
        <div class="col-md-8">
            <h2 class="welcome-title mb-1">{{ __('Edit Page Template') }}</h2>
            <p class="text-muted mb-0">{{ __('Update the names or configuration of this template.') }}</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="{{ route('admin.page-templates.index') }}" class="btn btn-light-soft px-4 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>{{ __('Back to List') }}</span>
            </a>
        </div>
    </div>

    @if($errors->has('names') || $errors->has('slug'))
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
            {{ $errors->first('names') ?: $errors->first('slug') }}
        </div>
    @endif

    <form action="{{ route('admin.page-templates.update', $pageTemplate) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-lg-12">
                <div class="dashboard-panel premium-shadow mb-4">
                    <div class="panel-header border-bottom-0 pb-0">
                        <div class="panel-header-title mb-3">
                            <i class="bi bi-translate me-2 text-primary"></i>
                            <span>{{ __('Template Information') }}</span>
                        </div>

                        @include('admin.partials.lang-tabs', ['isMaster' => false])
                    </div>

                    <div class="panel-body pt-0">
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">
                                    {{ __('Template Slug') }}
                                </label>
                                <div class="input-wrapper-simple">
                                    <input type="text"
                                           name="slug"
                                           id="template-slug"
                                           class="form-control @error('slug') is-invalid @enderror"
                                           placeholder="{{ __('e.g. service-page') }}"
                                           value="{{ old('slug', $pageTemplate->slug) }}">
                                </div>
                                <div class="form-text">{{ __('This slug is shared across all languages.') }}</div>
                                @error('slug')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="tab-content tab-content-premium" id="langTabsContent">
                            @foreach($locales as $locale)
                                @php $isSelected = in_array($locale['code'], $selectedLocaleCodes, true); @endphp
                                <div class="tab-pane fade {{ $locale['code'] === $firstSelectedLocaleCode ? 'show active' : '' }} {{ $isSelected ? '' : 'd-none' }}"
                                     id="lang-panel-{{ $locale['code'] }}"
                                     data-locale-code="{{ $locale['code'] }}"
                                     role="tabpanel">
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">
                                                {{ __('Name in') }} {{ $locale['name'] }}
                                            </label>
                                            <div class="input-wrapper-simple">
                                                <input type="text"
                                                       name="names[{{ $locale['code'] }}]"
                                                       id="name-{{ $locale['code'] }}"
                                                       class="form-control @error('names.'.$locale['code']) is-invalid @enderror"
                                                       {{ $isSelected ? '' : 'disabled' }}
                                                       placeholder="{{ __('e.g. Service Page') }}"
                                                       value="{{ old('names.'.$locale['code'], $translations[$locale['code']]->name ?? '') }}">
                                            </div>
                                            @error('names.'.$locale['code'])
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <hr class="my-5 opacity-10">

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-5 py-3 rounded-3 shadow-premium d-flex align-items-center gap-2">
                                <i class="bi bi-check-lg"></i>
                                <span class="fw-bold">{{ __('Update Template') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const slugify = (text) => {
        return text.toString()
            .normalize('NFKC')
            .toLowerCase()
            .replace(/[^\p{L}\p{N}\s-]+/gu, '')
            .replace(/\s+/g, '-')
            .replace(/\-\-+/g, '-')
            .replace(/^-+/, '')
            .replace(/-+$/, '');
    };

    const localeCodes = @json(collect($locales)->pluck('code')->values()->all());
    const templateSlugInput = document.getElementById('template-slug');

    if (templateSlugInput && templateSlugInput.value.trim() !== '') {
        templateSlugInput.dataset.manual = 'true';
    }

    localeCodes.forEach((localeCode) => {
        const nameInput = document.getElementById(`name-${localeCode}`);

        if (!nameInput || !templateSlugInput) {
            return;
        }

        nameInput.addEventListener('input', function() {
            if (!templateSlugInput.dataset.manual && this.value) {
                templateSlugInput.value = slugify(this.value);
            }
        });

        const checkContent = () => {
            const liveTabBtn = document.getElementById(`lang-tab-${localeCode}`);
            if (!liveTabBtn) {
                return;
            }

            if (nameInput.value || slugInput.value) {
                liveTabBtn.classList.add('has-content');
            } else {
                liveTabBtn.classList.remove('has-content');
            }
        };

        checkContent();
        nameInput.addEventListener('input', checkContent);

        document.addEventListener('locale-tab-added', function(event) {
            if (event.detail && event.detail.localeCode === localeCode) {
                checkContent();
            }
        });
    });

    if (templateSlugInput) {
        templateSlugInput.addEventListener('input', function() {
            if (document.activeElement === this) {
                this.dataset.manual = 'true';
            }
        });
    }
});
</script>
@endpush

@push('styles')
<style>
.input-wrapper-simple .form-control {
    border-radius: 12px;
    border-color: var(--admin-border);
    background-color: var(--admin-surface-2);
    padding: 12px 16px;
}
.input-wrapper-simple .form-control:focus {
    background-color: var(--admin-surface);
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-soft);
}
.nav-tabs-premium .nav-link.has-content {
    border-left: 3px solid var(--success) !important;
}
</style>
@endpush

