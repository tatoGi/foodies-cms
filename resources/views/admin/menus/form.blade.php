@php
    $menuTitle = old('title', $menu?->title ?? '');
    $menuSlug = old('slug', $menu?->slug ?? '');
    $menuActive = (bool) old('is_active', $menu?->is_active ?? true);
@endphp

<div class="row mb-5">
    <div class="col-md-8">
        <h2 class="welcome-title mb-1">{{ $menu ? __('Edit Menu') : __('Create Menu') }}</h2>
        <p class="text-muted mb-0">{{ __('WordPress style menu builder for header/footer/custom navigation.') }}</p>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <a href="{{ route('admin.menus.index') }}" class="btn btn-light-soft px-4 py-2 rounded-3 d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i>
            <span>{{ __('Back to List') }}</span>
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
        {{ $errors->first() }}
    </div>
@endif

<form action="{{ $action }}" method="POST" id="menuForm">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    {{-- Top row: Settings + Add Items side by side --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="dashboard-panel premium-shadow h-100">
                <div class="panel-header border-bottom-0">
                    <div class="panel-header-title">
                        <i class="bi bi-gear me-2 text-primary"></i>
                        <span>{{ __('Menu Settings') }}</span>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('Title') }}</label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ $menuTitle }}" required>
                        @error('title')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('Slug') }}</label>
                        <input type="text" name="slug" id="menu-slug" class="form-control @error('slug') is-invalid @enderror" value="{{ $menuSlug }}" required>
                        @error('slug')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check form-switch p-0 ps-5 mt-3">
                        <input class="form-check-input ms-n5" type="checkbox" id="is_active" name="is_active" value="1" @checked($menuActive)>
                        <label class="form-check-label fw-bold" for="is_active">{{ __('Active') }}</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="dashboard-panel premium-shadow h-100">
                <div class="panel-header border-bottom-0">
                    <div class="panel-header-title">
                        <i class="bi bi-link-45deg me-2 text-primary"></i>
                        <span>{{ __('Add Menu Items') }}</span>
                    </div>
                </div>
                <div class="panel-body p-0">
                    <div class="accordion accordion-flush premium-accordion" id="menuSourcesAccordion">
                        <div class="accordion-item border-0">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed py-3 px-4 fw-bold small text-muted text-uppercase" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePages">
                                    <i class="bi bi-files me-2"></i> {{ __('Pages') }}
                                </button>
                            </h2>
                            <div id="collapsePages" class="accordion-collapse collapse" data-bs-parent="#menuSourcesAccordion">
                                <div class="accordion-body px-4 pb-4 pt-0">
                                    <div class="source-list-scroll mb-3" style="max-height: 200px; overflow-y: auto;">
                                        @foreach($sources['pages'] as $source)
                                            <div class="form-check py-1">
                                                <input class="form-check-input page-source-checkbox" type="checkbox" value="{{ $source['id'] }}" id="page-{{ $source['id'] }}" data-source='@json($source)'>
                                                <label class="form-check-label small" for="page-{{ $source['id'] }}">
                                                    {!! str_repeat('&mdash; ', (int) ($source['depth'] ?? 0)) !!}{{ $source['title'] }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light-soft w-100" id="add-selected-pages">{{ __('Add to Menu') }}</button>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item border-0">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed py-3 px-4 fw-bold small text-muted text-uppercase" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePosts">
                                    <i class="bi bi-pencil-square me-2"></i> {{ __('Posts') }}
                                </button>
                            </h2>
                            <div id="collapsePosts" class="accordion-collapse collapse" data-bs-parent="#menuSourcesAccordion">
                                <div class="accordion-body px-4 pb-4 pt-0">
                                    <div class="source-list-scroll mb-3" style="max-height: 200px; overflow-y: auto;">
                                        @foreach($sources['posts'] as $source)
                                            <div class="form-check py-1">
                                                <input class="form-check-input post-source-checkbox" type="checkbox" value="{{ $source['id'] }}" id="post-{{ $source['id'] }}" data-source='@json($source)'>
                                                <label class="form-check-label small" for="post-{{ $source['id'] }}">
                                                    {{ $source['title'] }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light-soft w-100" id="add-selected-posts">{{ __('Add to Menu') }}</button>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item border-0">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed py-3 px-4 fw-bold small text-muted text-uppercase" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProducts">
                                    <i class="bi bi-box-seam me-2"></i> {{ __('Products') }}
                                </button>
                            </h2>
                            <div id="collapseProducts" class="accordion-collapse collapse" data-bs-parent="#menuSourcesAccordion">
                                <div class="accordion-body px-4 pb-4 pt-0">
                                    <div class="source-list-scroll mb-3" style="max-height: 200px; overflow-y: auto;">
                                        @foreach($sources['products'] as $source)
                                            <div class="form-check py-1">
                                                <input class="form-check-input product-source-checkbox" type="checkbox" value="{{ $source['id'] }}" id="prod-{{ $source['id'] }}" data-source='@json($source)'>
                                                <label class="form-check-label small" for="prod-{{ $source['id'] }}">
                                                    {{ $source['title'] }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light-soft w-100" id="add-selected-products">{{ __('Add to Menu') }}</button>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item border-0">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed py-3 px-4 fw-bold small text-muted text-uppercase" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCustom">
                                    <i class="bi bi-link me-2"></i> {{ __('Custom Link') }}
                                </button>
                            </h2>
                            <div id="collapseCustom" class="accordion-collapse collapse" data-bs-parent="#menuSourcesAccordion">
                                <div class="accordion-body px-4 pb-4 pt-0">
                                    <div class="mb-2">
                                        <label class="form-label small text-muted">{{ __('URL') }}</label>
                                        <input type="text" id="custom-link-url" class="form-control form-control-sm" placeholder="https://...">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small text-muted">{{ __('Label') }}</label>
                                        <input type="text" id="custom-link-label" class="form-control form-control-sm" placeholder="{{ __('My Link') }}">
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light-soft w-100" id="add-custom-link">{{ __('Add to Menu') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom row: Menu Structure full-width --}}
    <div class="row">
        <div class="col-12">
            <div class="dashboard-panel premium-shadow">
                <div class="panel-header border-bottom-0">
                    <div class="panel-header-title">
                        <i class="bi bi-list-ul me-2 text-primary"></i>
                        <span>{{ __('Menu Structure') }}</span>
                    </div>
                </div>
                <div class="panel-body">
                    <p class="text-muted small mb-4">{{ __('Drag each item into the order you prefer. Use the "Parent" selector to create hierarchy.') }}</p>
                    <div id="menu-items-container" class="d-flex flex-column gap-2 menu-sortable"></div>
                    <div id="menu-items-empty" class="text-muted text-center py-5 border rounded-3 border-dashed">
                        <i class="bi bi-layers d-block h2 text-light-soft mb-2"></i>
                        {{ __('No items yet. Add from the panel above.') }}
                    </div>

                    <div class="d-flex justify-content-end mt-5 border-top pt-4">
                        <button type="submit" class="btn btn-primary px-5 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                            <i class="bi bi-check-lg fw-bold"></i>
                            <span class="fw-bold">{{ $submitLabel }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<template id="menu-item-template">
    <div class="card menu-item-card premium-shadow-sm border-0 mb-2" style="transition: all 0.2s ease;">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2 border-0">
            <div class="d-flex align-items-center gap-3">
                <div class="menu-drag-handle cursor-move text-muted">
                    <i class="bi bi-grip-vertical"></i>
                </div>
                <div>
                    <strong class="menu-item-title h6 mb-0"></strong>
                    <span class="badge bg-light-soft text-primary-soft menu-item-type ms-2 uppercase-xs"></span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-link btn-sm text-muted p-0 toggle-item-settings" title="{{ __('Settings') }}">
                    <i class="bi bi-chevron-down"></i>
                </button>
                <button type="button" class="btn btn-link btn-sm text-danger p-0 remove-item" title="{{ __('Remove') }}">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
        </div>
        <div class="card-body border-top bg-light-soft item-settings-panel d-none">
            <input type="hidden" class="field-id">
            <input type="hidden" class="field-parent-key">
            <input type="hidden" class="field-type">
            <input type="hidden" class="field-reference-id">
            <input type="hidden" class="field-order">

            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-muted uppercase-xs">{{ __('URL / Slug') }}</label>
                    <input type="text" class="form-control form-control-sm field-url">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted uppercase-xs">{{ __('Target') }}</label>
                    <select class="form-select form-select-sm field-target border-0 shadow-sm">
                        <option value="_self">{{ __('Same tab') }}</option>
                        <option value="_blank">{{ __('New tab') }}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted uppercase-xs">{{ __('Parent Link') }}</label>
                    <select class="form-select form-select-sm field-parent-select border-0 shadow-sm">
                        <option value="">{{ __('None') }}</option>
                    </select>
                </div>
            </div>

            <div class="row g-2 mt-2">
                @foreach($locales as $locale)
                    <div class="col-md-6">
                        <label class="form-label small text-muted uppercase-xs">{{ __('Label') }} ({{ strtoupper($locale['code']) }})</label>
                        <input type="text" class="form-control form-control-sm label-input" data-locale="{{ $locale['code'] }}">
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</template>

@push('styles')
<style>
    .premium-accordion .accordion-item,
    .premium-accordion .accordion-button,
    .premium-accordion .accordion-body {
        background: var(--admin-surface);
        color: var(--admin-text);
    }
    .premium-accordion .accordion-item + .accordion-item {
        border-top: 1px solid var(--admin-border) !important;
    }
    .premium-accordion .accordion-button {
        box-shadow: none;
    }
    .premium-accordion .accordion-button:not(.collapsed) {
        background: var(--admin-surface-2);
        color: var(--admin-text);
    }
    .premium-accordion .accordion-button::after {
        filter: var(--accordion-icon-filter, none);
    }
    .source-list-scroll {
        border: 1px solid var(--admin-border);
        border-radius: 14px;
        padding: 0.75rem 0.9rem;
        background: var(--admin-surface-2);
    }
    .source-list-scroll .form-check {
        margin-bottom: 0;
    }
    .source-list-scroll .form-check-label {
        color: var(--admin-text);
    }
    .menu-sortable {
        min-height: 1rem;
    }
    #menu-items-empty {
        background: var(--admin-surface-2);
        border-style: dashed;
        border-color: var(--admin-border) !important;
    }
    .menu-item-card.sortable-ghost {
        opacity: 0.4;
        background: rgba(99, 102, 241, 0.12) !important;
        border: 2px dashed var(--primary) !important;
    }
    .menu-item-card.sortable-chosen {
        background: var(--admin-surface) !important;
        box-shadow: 0 10px 30px rgba(0,0,0,0.12) !important;
    }
    .uppercase-xs {
        text-transform: uppercase;
        font-size: 0.65rem;
        letter-spacing: 0.5px;
    }
    .text-primary-soft { color: #6366f1; }
    .bg-light-soft { background-color: var(--admin-surface-2); }
    .menu-item-card {
        background: var(--admin-surface);
        border: 1px solid var(--admin-border) !important;
    }
    .menu-item-card[data-depth]:not([data-depth="0"]) {
        margin-left: calc(var(--menu-depth, 0) * 1.5rem);
    }
    .menu-item-card[data-depth]:not([data-depth="0"]) .card-header {
        position: relative;
    }
    .menu-item-card[data-depth]:not([data-depth="0"]) .card-header::before {
        content: '';
        position: absolute;
        left: calc((var(--menu-depth, 0) * -1.15rem) + 0.45rem);
        top: 50%;
        width: calc(var(--menu-depth, 0) * 0.7rem);
        border-top: 1px dashed var(--admin-border-strong);
        opacity: 1;
        transform: translateY(-50%);
    }
    .menu-item-card .card-header {
        background: var(--admin-surface);
        color: var(--admin-text);
    }
    .menu-item-card .card-body {
        background: var(--admin-surface-2);
        border-top-color: var(--admin-border) !important;
    }
    .menu-item-card .menu-item-title,
    .menu-item-card .field-target,
    .menu-item-card .field-parent-select {
        color: var(--admin-text);
    }
    .menu-item-card .menu-item-type {
        background: var(--admin-surface-2) !important;
        color: var(--primary);
        border: 1px solid var(--admin-border);
    }
    .menu-item-card .field-url[readonly],
    .menu-item-card .label-input[readonly] {
        background-color: var(--admin-surface);
        color: var(--admin-muted);
        cursor: not-allowed;
    }
    body.theme-dark {
        --accordion-icon-filter: invert(1) brightness(1.8);
    }
    body.theme-dark .premium-accordion .accordion-button:hover {
        background: rgba(255, 255, 255, 0.03);
    }
    body.theme-dark .source-list-scroll {
        background: rgba(255, 255, 255, 0.02);
    }
    body.theme-dark .form-check-input {
        background-color: var(--admin-surface);
        border-color: var(--admin-border-strong);
    }
    body.theme-dark .form-check-input:checked {
        background-color: var(--primary);
        border-color: var(--primary);
    }
    body.theme-dark #menu-items-empty .bi {
        color: var(--admin-muted) !important;
    }
    body.theme-dark .menu-item-card.sortable-chosen {
        box-shadow: 0 16px 36px rgba(0,0,0,0.3) !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const locales = @json(collect($locales)->pluck('code')->values()->all());
    const defaultLocale = @json($defaultLocale);
    const initialItems = @json($items);
    const pageSources = @json($sources['pages']);
    const pageSourcesById = Object.fromEntries(pageSources.map((source) => [String(source.id), source]));
    const itemsContainer = document.getElementById('menu-items-container');
    const emptyState = document.getElementById('menu-items-empty');
    const template = document.getElementById('menu-item-template');
    const slugInput = document.getElementById('menu-slug');
    const titleInput = document.querySelector('input[name="title"]');

    const state = {
        items: [],
    };

    const checkboxMap = Object.fromEntries(
        Array.from(document.querySelectorAll('.page-source-checkbox')).map((checkbox) => [String(checkbox.value), checkbox])
    );

    const findItemByReference = (type, referenceId) => {
        return state.items.find((item) => item.type === type && String(item.reference_id) === String(referenceId)) || null;
    };

    const slugify = (text) => text
        .toString()
        .normalize('NFKC')
        .toLowerCase()
        .replace(/[^\p{L}\p{N}\s-]+/gu, '')
        .replace(/\s+/g, '-')
        .replace(/\-+/g, '-')
        .replace(/^-+/, '')
        .replace(/-+$/, '');

    const uid = () => `new_${Math.random().toString(36).slice(2, 10)}`;

    const getPageSource = (referenceId) => {
        return pageSourcesById[String(referenceId)] || null;
    };

    const getPageSourceDepth = (referenceId) => {
        const source = getPageSource(referenceId);
        return source ? Number(source.depth || 0) : 0;
    };

    const updateEmptyState = () => {
        emptyState.classList.toggle('d-none', state.items.length > 0);
    };

    const updateParentOptions = () => {
        const options = state.items.map((item) => ({ 
            key: item.key, 
            label: item.labels[defaultLocale] || item.url || item.type 
        }));
        
        state.items.forEach((item) => {
            const select = item.el.querySelector('.field-parent-select');
            const current = item.parent_key || '';
            select.innerHTML = `<option value="">{{ __('None') }}</option>`;
            options.forEach((option) => {
                if (option.key === item.key) {
                    return;
                }
                const opt = document.createElement('option');
                opt.value = option.key;
                opt.textContent = option.label;
                if (current === option.key) {
                    opt.selected = true;
                }
                select.appendChild(opt);
            });
        });
    };

    const refreshOrder = () => {
        Array.from(itemsContainer.querySelectorAll('.menu-item-card')).forEach((card, index) => {
            const key = card.dataset.key;
            const item = state.items.find((entry) => entry.key === key);
            if (!item) {
                return;
            }
            item.order = index;
            card.querySelector('.field-order').value = String(index);
        });
    };

    const bindNames = (item) => {
        const prefix = `items[${item.key}]`;
        item.el.querySelector('.field-id').name = `${prefix}[id]`;
        item.el.querySelector('.field-parent-key').name = `${prefix}[parent_key]`;
        item.el.querySelector('.field-type').name = `${prefix}[type]`;
        item.el.querySelector('.field-reference-id').name = `${prefix}[reference_id]`;
        item.el.querySelector('.field-url').name = `${prefix}[url]`;
        item.el.querySelector('.field-target').name = `${prefix}[target]`;
        item.el.querySelector('.field-order').name = `${prefix}[order]`;
        item.el.querySelectorAll('.label-input').forEach((input) => {
            input.name = `${prefix}[labels][${input.dataset.locale}]`;
        });
    };

    const setHeader = (item) => {
        const displayLabel = item.labels[defaultLocale] || item.url || item.type;
        item.el.querySelector('.menu-item-title').textContent = displayLabel;
        item.el.querySelector('.menu-item-type').textContent = item.type;
    };

    const syncParentField = (item) => {
        item.el.querySelector('.field-parent-key').value = item.parent_key;

        const select = item.el.querySelector('.field-parent-select');
        if (select instanceof HTMLSelectElement) {
            select.value = item.parent_key || '';
        }
    };

    const applyVisualHierarchy = (item) => {
        let depth = 0;
        let currentKey = item.parent_key;
        const visited = new Set();
        while (currentKey && !visited.has(currentKey)) {
            visited.add(currentKey);
            const parentItem = state.items.find(i => i.key === currentKey);
            if (!parentItem) break;
            depth++;
            currentKey = parentItem.parent_key;
        }

        item.el.classList.toggle('has-parent', depth > 0);
        item.el.dataset.depth = String(depth);
        item.el.style.setProperty('--menu-depth', String(depth));
    };

    const refreshAllVisualHierarchy = () => {
        state.items.forEach(item => applyVisualHierarchy(item));
    };

    const syncPageHierarchyFromSources = () => {
        const pageItemsByReferenceId = Object.fromEntries(
            state.items
                .filter((item) => item.type === 'page')
                .map((item) => [String(item.reference_id), item])
        );

        state.items.forEach((item) => {
            if (item.type !== 'page') {
                applyVisualHierarchy(item);
                return;
            }

            const source = getPageSource(item.reference_id);
            const parentReferenceId = source?.parent_id != null ? String(source.parent_id) : '';
            const parentItem = parentReferenceId !== '' ? pageItemsByReferenceId[parentReferenceId] || null : null;

            item.parent_key = parentItem ? parentItem.key : '';
            syncParentField(item);
            applyVisualHierarchy(item);
        });

        updateParentOptions();
    };

    const applyPageLockState = (item) => {
        const isPageItem = item.type === 'page';
        const source = isPageItem && item.reference_id !== ''
            ? pageSourcesById[String(item.reference_id)] || null
            : null;

        if (isPageItem && source) {
            item.url = String(source.url || '');
            item.labels = { ...(source.labels || {}) };
        }

        const urlInput = item.el.querySelector('.field-url');
        const parentSelect = item.el.querySelector('.field-parent-select');
        urlInput.readOnly = isPageItem;
        urlInput.value = item.url;
        if (parentSelect instanceof HTMLSelectElement) {
            parentSelect.disabled = isPageItem;
        }

        item.el.querySelectorAll('.label-input').forEach((input) => {
            const locale = input.dataset.locale;
            input.readOnly = isPageItem;

            if (isPageItem && source) {
                input.value = item.labels[locale] || '';
            }
        });

        setHeader(item);
        applyVisualHierarchy(item);
    };

    const createItem = (payload) => {
        const fragment = template.content.cloneNode(true);
        const el = fragment.querySelector('.menu-item-card');
        const key = payload.key || uid();
        el.dataset.key = key;

        const item = {
            key,
            id: payload.id || '',
            parent_key: payload.parent_key || '',
            type: payload.type || 'custom',
            reference_id: payload.reference_id || '',
            url: payload.url || '',
            target: payload.target || '_self',
            order: Number.isInteger(payload.order) ? payload.order : state.items.length,
            labels: payload.labels || {},
            el,
        };

        bindNames(item);

        el.querySelector('.field-id').value = item.id ? String(item.id) : '';
        el.querySelector('.field-parent-key').value = item.parent_key;
        el.querySelector('.field-type').value = item.type;
        el.querySelector('.field-reference-id').value = item.reference_id ? String(item.reference_id) : '';
        el.querySelector('.field-url').value = item.url;
        el.querySelector('.field-target').value = item.target;
        el.querySelector('.field-order').value = String(item.order);

        el.querySelectorAll('.label-input').forEach((input) => {
            const locale = input.dataset.locale;
            input.value = item.labels[locale] || '';
            input.addEventListener('input', () => {
                if (item.type === 'page') {
                    input.value = item.labels[locale] || '';
                    return;
                }
                item.labels[locale] = input.value;
                setHeader(item);
                // Update parent select labels globally if this item's label changed
                if (locale === defaultLocale) {
                    updateParentOptions();
                }
            });
        });

        el.querySelector('.field-url').addEventListener('input', (event) => {
            if (item.type === 'page') {
                event.target.value = item.url;
                return;
            }
            item.url = event.target.value;
            setHeader(item);
        });

        el.querySelector('.field-target').addEventListener('change', (event) => {
            item.target = event.target.value;
        });

        el.querySelector('.field-parent-select').addEventListener('change', (event) => {
            item.parent_key = event.target.value;
            el.querySelector('.field-parent-key').value = item.parent_key;
            refreshAllVisualHierarchy();
        });

        // Toggle settings
        el.querySelector('.toggle-item-settings').addEventListener('click', function() {
            const panel = el.querySelector('.item-settings-panel');
            const icon = this.querySelector('i');
            panel.classList.toggle('d-none');
            icon.classList.toggle('bi-chevron-down');
            icon.classList.toggle('bi-chevron-up');
        });

        // Remove item
        el.querySelector('.remove-item').addEventListener('click', () => {
            state.items = state.items.filter((entry) => entry.key !== item.key);
            state.items.forEach((entry) => {
                if (entry.parent_key === item.key) {
                    entry.parent_key = '';
                    syncParentField(entry);
                }
            });
            el.remove();
            refreshOrder();
            updateParentOptions();
            updateEmptyState();
        });

        applyPageLockState(item);
        state.items.push(item);
        itemsContainer.appendChild(el);
        refreshOrder();
        syncPageHierarchyFromSources();
        updateEmptyState();
    };

    // Initialize SortableJS
    if (typeof Sortable !== 'undefined') {
        new Sortable(itemsContainer, {
            animation: 150,
            handle: '.menu-drag-handle',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: function() {
                refreshOrder();
                syncPageHierarchyFromSources();
            }
        });
    }

    // Source Adding Logic
    const addSelected = (checkboxClass, type) => {
        const checkboxes = document.querySelectorAll(`.${checkboxClass}:checked`);
        checkboxes.forEach(cb => {
            const source = JSON.parse(cb.dataset.source);

            if (type === 'page') {
                addPageSourceTree(source);
                cb.checked = false;
                return;
            }

            const existingItem = findItemByReference(type, source.id);
            if (existingItem) {
                cb.checked = false;
                return;
            }

            createItem({
                type,
                reference_id: source.id,
                url: source.url || '',
                target: '_self',
                labels: source.labels || { [defaultLocale]: source.title || '' },
            });
            cb.checked = false;
        });
    };

    const addPageSourceTree = (source, parentKey = '') => {
        if (!source || typeof source !== 'object') {
            return null;
        }

        let item = findItemByReference('page', source.id);
        if (item) {
            item.parent_key = parentKey;
            syncParentField(item);
            applyPageLockState(item);
        } else {
            createItem({
                type: 'page',
                reference_id: source.id,
                url: source.url || '',
                target: '_self',
                parent_key: parentKey,
                labels: source.labels || { [defaultLocale]: source.title || '' },
            });

            item = findItemByReference('page', source.id);
        }

        syncPageHierarchyFromSources();

        const currentParentKey = item ? item.key : parentKey;
        const children = Array.isArray(source.children) ? source.children : [];
        children.forEach((child) => addPageSourceTree(child, currentParentKey));

        return item;
    };

    const togglePageDescendantCheckboxes = (source, checked) => {
        if (!source || !Array.isArray(source.children)) {
            return;
        }

        source.children.forEach((child) => {
            const childCheckbox = checkboxMap[String(child.id)];
            if (childCheckbox) {
                childCheckbox.checked = checked;
            }

            togglePageDescendantCheckboxes(child, checked);
        });
    };

    Object.values(checkboxMap).forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            const source = JSON.parse(checkbox.dataset.source || '{}');
            togglePageDescendantCheckboxes(source, checkbox.checked);
        });
    });

    document.getElementById('add-selected-pages').addEventListener('click', () => addSelected('page-source-checkbox', 'page'));
    document.getElementById('add-selected-posts').addEventListener('click', () => addSelected('post-source-checkbox', 'post'));
    document.getElementById('add-selected-products').addEventListener('click', () => addSelected('product-source-checkbox', 'product'));
    
    document.getElementById('add-custom-link').addEventListener('click', () => {
        const urlInput = document.getElementById('custom-link-url');
        const labelInput = document.getElementById('custom-link-label');
        
        if (labelInput.value.trim() === '') {
            labelInput.focus();
            return;
        }

        createItem({
            type: 'custom',
            url: urlInput.value.trim(),
            target: '_self',
            labels: { [defaultLocale]: labelInput.value.trim() },
        });

        urlInput.value = '';
        labelInput.value = '';
    });

    if (slugInput && titleInput && slugInput.value.trim() === '') {
        titleInput.addEventListener('input', () => {
            if (slugInput.dataset.manual === 'true') {
                return;
            }
            slugInput.value = slugify(titleInput.value);
        });
        slugInput.addEventListener('input', () => {
            slugInput.dataset.manual = 'true';
        });
    }

    initialItems.forEach((item) => createItem(item));
    refreshOrder();
    syncPageHierarchyFromSources();
    updateEmptyState();
});
</script>
@endpush
