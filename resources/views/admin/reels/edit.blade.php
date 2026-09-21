@extends('admin.layouts.app')

@section('title', __('Edit Reel'))
@section('page_title', __('Edit Reel'))

@php
    $selectedLocaleCodes = collect($selectedLocaleCodes ?? [])->map(static fn ($code): string => (string) $code)->values()->all();
    $firstSelectedLocaleCode = collect($locales)->pluck('code')->first(
        static fn ($code): bool => in_array((string) $code, $selectedLocaleCodes, true)
    );
@endphp

@section('content')
    <div class="row mb-5">
        <div class="col-md-8">
            <h2 class="welcome-title mb-1">{{ __('Edit Reel') }}</h2>
            <p class="text-muted mb-0">{{ __('Update reel content and settings.') }}</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="{{ route('admin.reels.index') }}" class="btn btn-light-soft px-4 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>{{ __('Back to List') }}</span>
            </a>
        </div>
    </div>

    @if($errors->has('titles'))
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
            {{ $errors->first('titles') }}
        </div>
    @endif

    <form action="{{ route('admin.reels.update', $reel) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-4">

            {{-- Main panel --}}
            <div class="col-lg-9">
                <div class="dashboard-panel premium-shadow">
                    <div class="panel-header border-bottom-0 pb-0">
                        <div class="panel-header-title mb-3">
                            <i class="bi bi-translate me-2 text-primary"></i>
                            <span>{{ __('Reel Content') }}</span>
                        </div>
                        @include('admin.partials.lang-tabs')
                    </div>

                    <div class="panel-body pt-0">
                        <div class="tab-content tab-content-premium" id="langTabsContent">
                            @foreach($locales as $locale)
                                @php
                                    $isSelected  = in_array($locale['code'], $selectedLocaleCodes, true);
                                    $translation = $translations[$locale['code']] ?? null;
                                @endphp
                                <div class="tab-pane fade {{ $locale['code'] === $firstSelectedLocaleCode ? 'show active' : '' }} {{ $isSelected ? '' : 'd-none' }}"
                                     id="lang-panel-{{ $locale['code'] }}"
                                     data-locale-code="{{ $locale['code'] }}"
                                     role="tabpanel">

                                    @include('admin.partials.ai-locale-toolbar', [
                                        'localeCode'   => $locale['code'],
                                        'translateUrl' => isset($reel) ? route('admin.reels.ai.translate', $reel) : route('admin.reels.ai.translate-draft'),
                                        'seoUrl'       => '',
                                        'draftMode'    => false,
                                    ])

                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">
                                                {{ __('Title') }} ({{ strtoupper($locale['code']) }})
                                            </label>
                                            <input type="text"
                                                   name="titles[{{ $locale['code'] }}]"
                                                   id="title-{{ $locale['code'] }}"
                                                   class="form-control @error('titles.'.$locale['code']) is-invalid @enderror"
                                                   {{ $isSelected ? '' : 'disabled' }}
                                                   value="{{ old('titles.'.$locale['code'], $translation?->title) }}">
                                            @error('titles.'.$locale['code'])
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">
                                                {{ __('Slug') }} ({{ strtoupper($locale['code']) }})
                                            </label>
                                            <input type="text"
                                                   name="slugs[{{ $locale['code'] }}]"
                                                   id="slug-{{ $locale['code'] }}"
                                                   class="form-control @error('slugs.'.$locale['code']) is-invalid @enderror"
                                                   {{ $isSelected ? '' : 'disabled' }}
                                                   value="{{ old('slugs.'.$locale['code'], $translation?->slug) }}">
                                            @error('slugs.'.$locale['code'])
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">
                                            {{ __('Description') }} ({{ strtoupper($locale['code']) }})
                                        </label>
                                        <textarea name="descriptions[{{ $locale['code'] }}]"
                                                  rows="4"
                                                  class="form-control @error('descriptions.'.$locale['code']) is-invalid @enderror"
                                                  {{ $isSelected ? '' : 'disabled' }}>{{ old('descriptions.'.$locale['code'], $translation?->description) }}</textarea>
                                        @error('descriptions.'.$locale['code'])
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                </div>
                            @endforeach
                        </div>

                        <hr class="my-5 opacity-10">

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-5 py-3 rounded-3 shadow-premium d-flex align-items-center gap-2">
                                <i class="bi bi-check-lg"></i>
                                <span class="fw-bold">{{ __('Update Reel') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-3">
                <div class="sticky-top" style="top:20px;">
                    <div class="dashboard-panel premium-shadow">
                        <div class="panel-header border-bottom-0">
                            <div class="panel-header-title">
                                <i class="bi bi-gear me-2 text-primary"></i>
                                <span>{{ __('Reel Settings') }}</span>
                            </div>
                        </div>
                        <div class="panel-body">

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">{{ __('Category') }} <span class="text-danger">*</span></label>
                                <select name="category" class="form-select @error('category') is-invalid @enderror">
                                    <option value="new"     @selected(old('category', $reel->category) === 'new')>🆕 {{ __('New') }}</option>
                                    <option value="sale"    @selected(old('category', $reel->category) === 'sale')>🔥 {{ __('Sale') }}</option>
                                    <option value="project" @selected(old('category', $reel->category) === 'project')>🏗️ {{ __('Project') }}</option>
                                </select>
                                @error('category')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">{{ __('Video URL') }}</label>
                                <input type="text" name="video_url" id="video_url_input"
                                       class="form-control @error('video_url') is-invalid @enderror"
                                       value="{{ old('video_url', $reel->video_url) }}" placeholder="{{ __('Stored path or URL') }}">
                                <button type="button" class="btn btn-outline-secondary btn-sm open-media-picker mt-2"
                                        data-picker-mode="video" data-target-input="video_url_input">
                                    <i class="bi bi-camera-video me-1"></i>{{ __('Choose from Media') }}
                                </button>
                                <div class="form-text">{{ __('Optional — add a video for video reels. Products are shown via "Show in Reels" on the product.') }}</div>
                                @error('video_url')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">{{ __('Sort Order') }}</label>
                                <input type="number" name="sort_order" min="0"
                                       class="form-control @error('sort_order') is-invalid @enderror"
                                       value="{{ old('sort_order', $reel->sort_order) }}">
                                @error('sort_order')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="form-check form-switch p-0 ps-5">
                                <input class="form-check-input ms-n5" type="checkbox" id="is_active"
                                       name="is_active" value="1" @checked(old('is_active', $reel->is_active))>
                                <label class="form-check-label fw-bold" for="is_active">{{ __('Active') }}</label>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
@endsection

@push('scripts')
@include('admin.partials.ai-content-tools-script')
<script>
// Reel-specific AI translate (titles[] not names[])
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    document.querySelectorAll('.ai-locale-tools').forEach((tools) => {
        tools.querySelector('.ai-generate-seo-btn')?.remove();

        const localeCode   = tools.dataset.targetLocale || '';
        const translateUrl = tools.dataset.translateUrl || '';
        const draftMode    = tools.dataset.draftMode === 'true';
        const form         = tools.closest('form');
        const btn          = tools.querySelector('.ai-translate-locale-btn');
        if (!btn) { return; }

        const newBtn = btn.cloneNode(true);
        btn.replaceWith(newBtn);

        newBtn.addEventListener('click', async () => {
            if (!localeCode || !translateUrl) { return; }

            tools.querySelectorAll('button').forEach(b => b.disabled = true);
            tools.classList.add('opacity-75');

            try {
                const body = { target_locale: localeCode };

                if (draftMode && form instanceof HTMLFormElement) {
                    const fd = new FormData(form);
                    const titles = {}, descs = {};
                    fd.forEach((val, key) => {
                        const mt = key.match(/^titles\[(.+)\]$/);
                        if (mt) titles[mt[1]] = val;
                        const md = key.match(/^descriptions\[(.+)\]$/);
                        if (md) descs[md[1]] = val;
                    });
                    const allLocales = Array.from(new Set([...Object.keys(titles), ...Object.keys(descs)]));
                    body.translations = allLocales.reduce((acc, lc) => {
                        acc[lc] = { title: titles[lc] || '', description: descs[lc] || '' };
                        return acc;
                    }, {});
                }

                const res = await fetch(translateUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify(body),
                });
                const payload = await res.json();
                if (!res.ok) { throw new Error(payload.message || 'Translation failed.'); }

                const titleInput = document.querySelector('input[name="titles[' + localeCode + ']"]');
                const descInput  = document.querySelector('textarea[name="descriptions[' + localeCode + ']"]');
                const slugInput  = document.querySelector('input[name="slugs[' + localeCode + ']"]');

                if (titleInput && payload.fields?.title) {
                    titleInput.value = payload.fields.title;
                    titleInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
                if (descInput && payload.fields?.description !== undefined) {
                    descInput.value = payload.fields.description;
                }
                if (slugInput && payload.fields?.title) {
                    const slugify = t => t.toString().normalize('NFKC').toLowerCase()
                        .replace(/[^\w\s-]+/g, '').replace(/\s+/g, '-')
                        .replace(/--+/g, '-').replace(/^-+/, '').replace(/-+$/, '');
                    slugInput.value = slugify(payload.fields.title);
                    slugInput.dataset.manual = 'true';
                }
            } catch (err) {
                alert(err instanceof Error ? err.message : 'Translation failed.');
            } finally {
                tools.querySelectorAll('button').forEach(b => b.disabled = false);
                tools.classList.remove('opacity-75');
            }
        });
    });
});
</script>
@endpush
