@extends('admin.layouts.app')

@section('title', __('Create Block Type'))
@section('page_title', __('Create Block Type'))

@php
    $selectedLocaleCodes = collect($selectedLocaleCodes ?? [])->map(static fn ($code): string => (string) $code)->values()->all();
    $firstSelectedLocaleCode = collect($locales)->pluck('code')->first(
        static fn ($code): bool => in_array((string) $code, $selectedLocaleCodes, true)
    );
    $selectedDefinitionKey = (string) old('key', ($availableBlockTypeOptions[0]['key'] ?? ''));
    $selectedDefinition = collect($availableBlockTypeOptions)->firstWhere('key', $selectedDefinitionKey) ?? ($availableBlockTypeOptions[0] ?? null);
@endphp

@section('content')
    <div class="row mb-5">
        <div class="col-md-8">
            <h2 class="welcome-title mb-1">{{ __('New Block Type') }}</h2>
            <p class="text-muted mb-0">{{ __('Create a block type from the predefined renderer list.') }}</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="{{ route('admin.blocks.index') }}" class="btn btn-light-soft px-4 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>{{ __('Back to List') }}</span>
            </a>
        </div>
    </div>

    @if(count($availableBlockTypeOptions) === 0)
        <div class="alert alert-info border-0 shadow-sm rounded-3">
            {{ __('All predefined block types are already created.') }}
        </div>
    @else
    <form method="POST" action="{{ route('admin.blocks.store') }}" id="blockForm">
        @csrf
        <div class="row g-4 align-items-start">
            <div class="col-lg-8">
                <div class="dashboard-panel premium-shadow mb-4">
                    <div class="panel-header border-bottom-0 pb-0">
                        <div class="panel-header-title mb-3">
                            <i class="bi bi-translate me-2 text-primary"></i>
                            <span>{{ __('Block Configuration') }}</span>
                            <span class="ms-2 small text-muted text-capitalize">({{ __('Fill at least one locale') }})</span>
                        </div>
                        @include('admin.partials.lang-tabs')
                    </div>

                    <div class="panel-body pt-0">
                        @error('labels')
                            <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-3">{{ $message }}</div>
                        @enderror

                        <div class="tab-content tab-content-premium" id="langTabsContent">
                            @foreach($locales as $locale)
                                @php $isSelected = in_array($locale['code'], $selectedLocaleCodes, true); @endphp
                                <div class="tab-pane fade {{ $locale['code'] === $firstSelectedLocaleCode ? 'show active' : '' }} {{ $isSelected ? '' : 'd-none' }}"
                                     id="lang-panel-{{ $locale['code'] }}"
                                     data-locale-code="{{ $locale['code'] }}"
                                     role="tabpanel">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">
                                                {{ __('Label') }} ({{ strtoupper($locale['code']) }})
                                            </label>
                                            <input type="text"
                                                   name="labels[{{ $locale['code'] }}]"
                                                   id="blockLabel-{{ $locale['code'] }}"
                                                   data-locale-code="{{ $locale['code'] }}"
                                                   class="form-control form-control-sm block-label-input @error('labels.'.$locale['code']) is-invalid @enderror"
                                                   placeholder="{{ __('e.g. Hero Section') }}"
                                                   value="{{ old('labels.'.$locale['code']) }}"
                                                   {{ $isSelected ? '' : 'disabled' }}>
                                            @error('labels.'.$locale['code'])
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">
                                                {{ __('Description') }} ({{ strtoupper($locale['code']) }})
                                            </label>
                                            <input type="text"
                                                   name="descriptions[{{ $locale['code'] }}]"
                                                   data-locale-code="{{ $locale['code'] }}"
                                                   class="form-control form-control-sm @error('descriptions.'.$locale['code']) is-invalid @enderror"
                                                   placeholder="{{ __('Optional description...') }}"
                                                   value="{{ old('descriptions.'.$locale['code']) }}"
                                                   {{ $isSelected ? '' : 'disabled' }}>
                                            @error('descriptions.'.$locale['code'])
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="dashboard-panel premium-shadow mb-4">
                    <div class="panel-header d-flex justify-content-between align-items-center bg-light-soft">
                        <div class="panel-header-title">
                            <i class="bi bi-list-task me-2 text-primary"></i>
                            <span>{{ __('Fields Builder') }}</span>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-primary btn-sm rounded-pill px-3 dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-plus-lg me-1"></i> {{ __('Add Field') }}
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-premium border-0 rounded-4 p-2">
                                <li><a class="dropdown-item rounded-3 py-2 add-field" data-type="text" href="#"><i class="bi bi-type me-2"></i>{{ __('Text Input') }}</a></li>
                                <li><a class="dropdown-item rounded-3 py-2 add-field" data-type="textarea" href="#"><i class="bi bi-textarea-t me-2"></i>{{ __('Textarea') }}</a></li>
                                <li><a class="dropdown-item rounded-3 py-2 add-field" data-type="rich_text" href="#"><i class="bi bi-file-earmark-richtext me-2"></i>{{ __('Rich Text') }}</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item rounded-3 py-2 add-field" data-type="image" href="#"><i class="bi bi-image me-2"></i>{{ __('Single Image') }}</a></li>
                                <li><a class="dropdown-item rounded-3 py-2 add-field" data-type="gallery" href="#"><i class="bi bi-images me-2"></i>{{ __('Image Gallery') }}</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item rounded-3 py-2 add-field" data-type="number" href="#"><i class="bi bi-hash me-2"></i>{{ __('Number') }}</a></li>
                                <li><a class="dropdown-item rounded-3 py-2 add-field" data-type="select" href="#"><i class="bi bi-list-check me-2"></i>{{ __('Dropdown Select') }}</a></li>
                                <li><a class="dropdown-item rounded-3 py-2 add-field" data-type="color" href="#"><i class="bi bi-palette me-2"></i>{{ __('Color') }}</a></li>
                                <li><a class="dropdown-item rounded-3 py-2 add-field" data-type="icon" href="#"><i class="bi bi-emoji-smile me-2"></i>{{ __('Icon Picker') }}</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="px-3 pb-2">
                        <ul class="nav nav-tabs nav-tabs-premium border-0 gap-2 field-lang-tabs" id="fieldLangTabs" role="tablist">
                            @foreach($locales as $locale)
                                @php $isSelected = in_array($locale['code'], $selectedLocaleCodes, true); @endphp
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $locale['code'] === $firstSelectedLocaleCode ? 'active' : '' }} {{ $isSelected ? '' : 'd-none' }}"
                                            type="button"
                                            data-locale-code="{{ $locale['code'] }}"
                                            data-locale-name="{{ $locale['name'] }}"
                                            data-locale-flag="{{ $locale['flag'] }}">
                                        <span class="lang-flag-mini">{{ $locale['flag'] ?: 'GL' }}</span>
                                        <span class="lang-name-mini">{{ $locale['name'] }}</span>
                                        <span class="badge bg-light-soft text-muted border ms-1 small">{{ strtoupper($locale['code']) }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="panel-body p-0">
                        <div id="fieldsContainer" class="p-3 d-flex flex-column gap-2">
                            <div class="no-fields-message text-center py-4 border rounded-4 border-dashed bg-light-soft">
                                <i class="bi bi-stack h3 text-muted opacity-25 d-block mb-2"></i>
                                <p class="text-muted small mb-0">{{ __('No fields added yet.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary px-5 py-3 rounded-3 shadow-premium d-flex align-items-center gap-2">
                        <i class="bi bi-check-lg"></i>
                        <span class="fw-bold">{{ __('Save Block Structure') }}</span>
                    </button>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="dashboard-panel premium-shadow sticky-top" style="top: 20px;">
                    <div class="panel-header border-bottom-0 pb-0">
                        <div class="panel-header-title">
                            <i class="bi bi-gear me-2 text-primary"></i>
                            <span>{{ __('Settings') }}</span>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">{{ __('Key') }}</label>
                            <select name="key" id="blockKey" class="form-select form-select-sm @error('key') is-invalid @enderror" required>
                                @foreach($availableBlockTypeOptions as $option)
                                    <option value="{{ $option['key'] }}"
                                            data-scope="{{ $option['scope'] }}"
                                            data-icon="{{ $option['icon'] }}"
                                            @selected($selectedDefinitionKey === $option['key'])>
                                        {{ $option['label'] }} ({{ $option['key'] }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('You can create only predefined canonical block types.') }}</div>
                            @error('key') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">{{ __('Scope') }}</label>
                            <input type="text" id="scopePreview" class="form-control form-control-sm" value="{{ ucfirst((string) old('scope', ($selectedDefinition['scope'] ?? 'page'))) }}" readonly>
                            <input type="hidden" name="scope" id="scopeInput" value="{{ old('scope', ($selectedDefinition['scope'] ?? 'page')) }}">
                            @error('scope') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">{{ __('Icon (Class)') }}</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light-soft border-end-0"><i class="bi bi-box" id="iconPreview"></i></span>
                                <input type="text" name="icon" id="iconInput" class="form-control border-start-0" value="{{ old('icon', ($selectedDefinition['icon'] ?? 'bi-box')) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">{{ __('Sort Order') }}</label>
                            <input type="number" name="sort_order" class="form-control form-control-sm" value="{{ old('sort_order', 0) }}">
                        </div>

                        <hr class="my-3 opacity-10">

                        <div class="form-check form-switch p-0 ps-5">
                            <input class="form-check-input ms-n5" type="checkbox" value="1" id="is_enabled" name="is_enabled" @checked(old('is_enabled', true))>
                            <label class="form-check-label fw-bold" for="is_enabled">{{ __('Enabled') }}</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @endif

    <template id="fieldTemplate">
        <div class="card field-card border-0 shadow-sm rounded-3 mb-2 overflow-hidden is-collapsed" data-index="__INDEX__">
            <div class="card-body p-2">
                <div class="d-flex align-items-center gap-2 field-card-toolbar">
                    <div class="drag-handle text-muted cursor-move px-1">
                        <i class="bi bi-grip-vertical fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-4">
                                <input type="text" name="fields[__INDEX__][key]" class="form-control form-control-sm field-key-input" placeholder="{{ __('Key') }}" required>
                            </div>
                            <div class="col-md-3">
                                <span class="badge bg-primary-soft text-primary field-type-badge text-capitalize px-2 py-1 small d-block text-center rounded-2">__TYPE__</span>
                            </div>
                            <div class="col-md-3">
                                <div class="field-summary text-muted small text-truncate">{{ __('No label yet') }}</div>
                            </div>
                            <div class="col-md-2 text-end d-flex justify-content-end align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-light-soft field-toggle rounded-circle" aria-expanded="false" title="{{ __('Toggle details') }}">
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-link link-danger p-0 remove-field" title="{{ __('Remove') }}">
                                    <i class="bi bi-x-circle fs-5"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="field-card-details mt-2 px-4 border-top border-light-subtle pt-2 d-none">
                    @foreach($locales as $locale)
                        @php $isSelected = in_array($locale['code'], $selectedLocaleCodes, true); @endphp
                        <div class="field-locale-pane {{ $locale['code'] === $firstSelectedLocaleCode ? '' : 'd-none' }}" data-locale-code="{{ $locale['code'] }}">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small text-muted mb-1">{{ __('Label') }} ({{ strtoupper($locale['code']) }})</label>
                                    <input type="text"
                                           name="fields[__INDEX__][labels][{{ $locale['code'] }}]"
                                           class="form-control form-control-sm field-label-locale-input field-locale-input"
                                           data-locale-code="{{ $locale['code'] }}"
                                           placeholder="{{ __('Label') }}"
                                           {{ $isSelected ? '' : 'disabled' }}>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted mb-1">{{ __('Help text') }} ({{ strtoupper($locale['code']) }})</label>
                                    <input type="text"
                                           name="fields[__INDEX__][helps][{{ $locale['code'] }}]"
                                           class="form-control form-control-sm field-locale-input"
                                           data-locale-code="{{ $locale['code'] }}"
                                           placeholder="{{ __('Help text...') }}"
                                           {{ $isSelected ? '' : 'disabled' }}>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <input type="hidden" name="fields[__INDEX__][type]" value="__TYPE__">
        </div>
    </template>
@endsection

@push('styles')
<style>
    .cursor-move { cursor: move; }
    .border-dashed { border-style: dashed !important; border-width: 2px !important; }
    .field-card { border: 1px solid var(--admin-border) !important; transition: border-color 0.2s; }
    .field-card:hover { border-color: var(--bs-primary) !important; }
    .drag-handle { opacity: 0.5; transition: opacity 0.2s; }
    .field-card:hover .drag-handle { opacity: 1; }
    .field-card-toolbar { min-height: 44px; }
    .field-toggle { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; }
    .field-summary { line-height: 1.2; }
    .field-card.is-collapsed .field-card-details { display: none; }
    .field-card-details { background: rgba(15, 23, 42, 0.02); }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const blockForm = document.getElementById('blockForm');
    if (!blockForm) {
        return;
    }

    const fieldsContainer = document.getElementById('fieldsContainer');
    const fieldTemplate = document.getElementById('fieldTemplate');
    const noFieldsMessage = document.querySelector('.no-fields-message');
    const blockKey = document.getElementById('blockKey');
    const iconInput = document.getElementById('iconInput');
    const iconPreview = document.getElementById('iconPreview');
    const localeCodes = @json(collect($locales)->pluck('code')->values()->all());
    const localeMeta = @json($locales);
    const fieldLangTabs = document.getElementById('fieldLangTabs');

    let fieldIndex = 0;

    const reindexFieldInputs = () => {
        const cards = Array.from(fieldsContainer.querySelectorAll('.field-card'));

        cards.forEach((card, index) => {
            card.dataset.index = String(index);

            card.querySelectorAll('input[name], textarea[name], select[name]').forEach((input) => {
                input.name = input.name.replace(/fields\[\d+\]/, `fields[${index}]`);
            });
        });

        fieldIndex = cards.length;
    };

    const initFieldSortable = () => {
        if (!fieldsContainer || typeof Sortable === 'undefined') {
            return;
        }

        Sortable.create(fieldsContainer, {
            animation: 150,
            handle: '.drag-handle',
            draggable: '.field-card',
            onEnd: reindexFieldInputs,
        });
    };

    const getActiveLocaleCode = () => {
        const activeTab = document.querySelector('#langTabs .nav-link.active');
        if (!activeTab) {
            return localeCodes[0] || 'en';
        }

        return activeTab.id.replace('lang-tab-', '');
    };

    const syncFieldLocalePaneVisibility = (localeCode) => {
        document.querySelectorAll('.field-locale-pane').forEach((pane) => {
            pane.classList.toggle('d-none', pane.dataset.localeCode !== localeCode);
        });
    };

    const syncFieldSummaries = () => {
        document.querySelectorAll('.field-card').forEach((card) => {
            const summary = card.querySelector('.field-summary');
            if (!summary) {
                return;
            }

            const activeLocale = getActiveLocaleCode();
            const activeLocaleLabel = card.querySelector(`.field-label-locale-input[data-locale-code="${activeLocale}"]`);
            const firstFilledLabel = Array.from(card.querySelectorAll('.field-label-locale-input'))
                .map((input) => input.value.trim())
                .find((value) => value !== '');

            summary.textContent = activeLocaleLabel?.value?.trim() || firstFilledLabel || '{{ __('No label yet') }}';
            summary.setAttribute('title', summary.textContent);
        });
    };

    const setFieldTabsActive = (localeCode) => {
        if (!fieldLangTabs) {
            return;
        }

        fieldLangTabs.querySelectorAll('.nav-link').forEach((tab) => {
            tab.classList.toggle('active', tab.dataset.localeCode === localeCode);
        });
    };

    const getLocaleMeta = (localeCode) => {
        return localeMeta.find((locale) => locale.code === localeCode);
    };

    const ensureFieldTab = (localeCode) => {
        if (!fieldLangTabs || fieldLangTabs.querySelector(`.nav-link[data-locale-code="${localeCode}"]`)) {
            return;
        }

        const meta = getLocaleMeta(localeCode);
        if (!meta) {
            return;
        }

        const li = document.createElement('li');
        li.className = 'nav-item';
        li.setAttribute('role', 'presentation');
        li.innerHTML = `
            <button class="nav-link" type="button" data-locale-code="${meta.code}" data-locale-name="${meta.name}" data-locale-flag="${meta.flag}">
                <span class="lang-flag-mini">${meta.flag || 'GL'}</span>
                <span class="lang-name-mini">${meta.name}</span>
                <span class="badge bg-light-soft text-muted border ms-1 small">${meta.code.toUpperCase()}</span>
            </button>
        `;
        fieldLangTabs.appendChild(li);
    };

    const removeFieldTab = (localeCode) => {
        if (!fieldLangTabs) {
            return;
        }

        const tab = fieldLangTabs.querySelector(`.nav-link[data-locale-code="${localeCode}"]`);
        if (tab) {
            tab.closest('.nav-item')?.remove();
        }
    };

    const syncLocaleFieldEnabledState = () => {
        localeCodes.forEach((localeCode) => {
            const isLocaleVisible = !!document.getElementById('lang-tab-' + localeCode);
            document.querySelectorAll(`.field-locale-input[data-locale-code="${localeCode}"]`).forEach((input) => {
                input.disabled = !isLocaleVisible;
            });
        });
    };

    iconInput.addEventListener('input', function() {
        iconPreview.className = `bi ${this.value}`;
    });

    const syncSelectedBlockTypeMeta = () => {
        const selectedOption = blockKey?.selectedOptions?.[0];
        if (!selectedOption) {
            return;
        }

        const scope = selectedOption.dataset.scope || 'page';
        const icon = selectedOption.dataset.icon || 'bi-box';
        const scopeInput = document.getElementById('scopeInput');
        const scopePreview = document.getElementById('scopePreview');

        if (scopeInput) {
            scopeInput.value = scope;
        }

        if (scopePreview) {
            scopePreview.value = scope.charAt(0).toUpperCase() + scope.slice(1);
        }

        if (!iconInput.value || iconInput.dataset.auto !== 'false') {
            iconInput.value = icon;
            iconPreview.className = `bi ${icon}`;
            iconInput.dataset.auto = 'true';
        }
    };

    blockKey?.addEventListener('change', syncSelectedBlockTypeMeta);
    iconInput.addEventListener('input', function() {
        this.dataset.auto = 'false';
    });

    document.querySelectorAll('.add-field').forEach((btn) => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            addField(this.dataset.type);
        });
    });

    function addField(type) {
        if (noFieldsMessage) {
            noFieldsMessage.classList.add('d-none');
        }

        const index = fieldIndex++;
        const html = fieldTemplate.innerHTML.replace(/__INDEX__/g, index).replace(/__TYPE__/g, type);
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        const card = wrapper.firstElementChild;
        fieldsContainer.appendChild(card);
        setupCardEvents(card);
        reindexFieldInputs();
        syncFieldLocalePaneVisibility(getActiveLocaleCode());
        syncLocaleFieldEnabledState();
        syncFieldSummaries();
    }

    function setupCardEvents(card) {
        const keyInput = card.querySelector('.field-key-input');
        const removeBtn = card.querySelector('.remove-field');
        const toggleBtn = card.querySelector('.field-toggle');
        const details = card.querySelector('.field-card-details');
        const summary = card.querySelector('.field-summary');

        const syncSummary = () => {
            if (!summary) {
                return;
            }

            const activeLocale = getActiveLocaleCode();
            const activeLocaleLabel = card.querySelector(`.field-label-locale-input[data-locale-code="${activeLocale}"]`);
            const firstFilledLabel = Array.from(card.querySelectorAll('.field-label-locale-input'))
                .map((input) => input.value.trim())
                .find((value) => value !== '');

            summary.textContent = activeLocaleLabel?.value?.trim() || firstFilledLabel || '{{ __('No label yet') }}';
            summary.setAttribute('title', summary.textContent);
        };

        const setCollapsed = (collapsed) => {
            if (!details || !toggleBtn) {
                return;
            }

            card.classList.toggle('is-collapsed', collapsed);
            details.classList.toggle('d-none', collapsed);
            toggleBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                icon.className = `bi ${collapsed ? 'bi-chevron-down' : 'bi-chevron-up'}`;
            }
        };

        card.querySelectorAll('.field-label-locale-input').forEach((labelInput) => {
            labelInput.addEventListener('input', function() {
                if (!keyInput.dataset.manual && this.value) {
                    keyInput.value = slugify(this.value);
                }
                syncSummary();
            });
        });

        keyInput.addEventListener('input', () => {
            keyInput.dataset.manual = 'true';
        });

        toggleBtn?.addEventListener('click', function() {
            setCollapsed(toggleBtn.getAttribute('aria-expanded') === 'true');
        });

        removeBtn.addEventListener('click', function() {
            card.remove();
            reindexFieldInputs();
            if (fieldsContainer.querySelectorAll('.field-card').length === 0 && noFieldsMessage) {
                noFieldsMessage.classList.remove('d-none');
            }
        });

        syncSummary();
        setCollapsed(true);
    }

    document.querySelectorAll('#langTabs [data-bs-toggle="tab"]').forEach((tab) => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const localeCode = e.target.id.replace('lang-tab-', '');
            syncFieldLocalePaneVisibility(localeCode);
            setFieldTabsActive(localeCode);
            syncFieldSummaries();
        });
    });

    if (fieldLangTabs) {
        fieldLangTabs.addEventListener('click', function(event) {
            const tab = event.target.closest('.nav-link');
            if (!tab) {
                return;
            }

            const localeCode = tab.dataset.localeCode;
            const mainTab = document.getElementById('lang-tab-' + localeCode);
            if (mainTab && window.bootstrap && window.bootstrap.Tab) {
                window.bootstrap.Tab.getOrCreateInstance(mainTab).show();
            }

            syncFieldLocalePaneVisibility(localeCode);
            setFieldTabsActive(localeCode);
            syncFieldSummaries();
        });
    }

    document.addEventListener('locale-tab-added', function(event) {
        const localeCode = event.detail.localeCode;
        ensureFieldTab(localeCode);
        const newTab = document.getElementById('lang-tab-' + localeCode);
        if (newTab) {
            newTab.addEventListener('shown.bs.tab', function(e) {
                const activeLocaleCode = e.target.id.replace('lang-tab-', '');
                syncFieldLocalePaneVisibility(activeLocaleCode);
                setFieldTabsActive(activeLocaleCode);
            });
        }
        syncFieldLocalePaneVisibility(localeCode);
        syncLocaleFieldEnabledState();
        syncFieldSummaries();
    });

    const originalClearLocaleData = window.clearLocaleData;
    window.clearLocaleData = function(event, localeCode) {
        const result = originalClearLocaleData(event, localeCode);
        removeFieldTab(localeCode);
        document.querySelectorAll(`.field-locale-input[data-locale-code="${localeCode}"]`).forEach((input) => {
            input.value = '';
            input.disabled = true;
        });
        syncLocaleFieldEnabledState();
        syncFieldLocalePaneVisibility(getActiveLocaleCode());
        setFieldTabsActive(getActiveLocaleCode());
        syncFieldSummaries();

        return result;
    };

    blockForm.addEventListener('submit', () => {
        reindexFieldInputs();
    });

    initFieldSortable();
    syncSelectedBlockTypeMeta();
    syncFieldLocalePaneVisibility(getActiveLocaleCode());
    syncLocaleFieldEnabledState();
    setFieldTabsActive(getActiveLocaleCode());
    syncFieldSummaries();
});
</script>
@endpush
