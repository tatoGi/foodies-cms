@extends('admin.layouts.app')

@section('title', __('Edit Block Type'))
@section('page_title', __('Edit Block Type'))

@php
    $selectedLocaleCodes = collect($selectedLocaleCodes ?? [])->map(static fn ($code): string => (string) $code)->values()->all();
    $firstSelectedLocaleCode = collect($locales)->pluck('code')->first(
        static fn ($code): bool => in_array((string) $code, $selectedLocaleCodes, true)
    );
    $schemaTranslations = $blockType->schema['translations'] ?? [];
    $translationLabels = $schemaTranslations['labels'] ?? [];
    $translationDescriptions = $schemaTranslations['descriptions'] ?? [];
    $fields = $blockType->schema['fields'] ?? [];
    $isSystemBlock = (bool) ($blockType->is_system ?? false);
@endphp

@section('content')
    <div class="row mb-5">
        <div class="col-md-8">
            <h2 class="welcome-title mb-1">{{ __('Edit Block Type') }}</h2>
            <p class="text-muted mb-0">
                {{ $isSystemBlock ? __('Update this block type fields and labels while keeping the renderer identity stable.') : __('Update reusable content structure.') }}
            </p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="{{ route('admin.blocks.index') }}" class="btn btn-light-soft px-4 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>{{ __('Back to List') }}</span>
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.blocks.update', $blockType) }}" id="blockForm">
        @csrf
        @method('PUT')
        <div class="row g-4 align-items-start">
            <div class="col-lg-8">
                <div class="dashboard-panel premium-shadow mb-4">
                    <div class="panel-header border-bottom-0 pb-0">
                        <div class="panel-header-title mb-3">
                            <i class="bi bi-translate me-2 text-primary"></i>
                            <span>{{ __('Block Configuration') }}</span>
                        </div>
                        @include('admin.partials.lang-tabs')
                    </div>

                    <div class="panel-body pt-0">
                        @if($isSystemBlock)
                            <div class="alert alert-info border-0 shadow-sm rounded-3 mb-3">
                                {{ __('This is a system block type. Renderer key and scope are locked, but you can freely configure the fields and their labels.') }}
                            </div>
                        @endif

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
                                                   value="{{ old('labels.'.$locale['code'], $translationLabels[$locale['code']] ?? ($locale['code'] === $defaultLocale ? $blockType->label : '')) }}"
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
                                                   value="{{ old('descriptions.'.$locale['code'], $translationDescriptions[$locale['code']] ?? ($locale['code'] === $defaultLocale ? $blockType->description : '')) }}"
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
                                <li><a class="dropdown-item rounded-3 py-2 add-field" data-type="icon" href="#"><i class="bi bi-emoji-smile me-2"></i>{{ __('Icon Picker') }}</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item rounded-3 py-2 add-field" data-type="repeater" href="#"><i class="bi bi-collection me-2"></i>{{ __('Repeater') }}</a></li>
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
                            @if(count($fields) === 0)
                                <div class="no-fields-message text-center py-4 border rounded-4 border-dashed bg-light-soft">
                                    <i class="bi bi-stack h3 text-muted opacity-25 d-block mb-2"></i>
                                    <p class="text-muted small mb-0">{{ __('No fields added yet.') }}</p>
                                </div>
                            @endif

                            @foreach($fields as $index => $field)
                                @php
                                    $fieldLabels = $field['labels'] ?? [];
                                    $fieldHelps = $field['helps'] ?? [];
                                    $fieldPreviewLabel = $fieldLabels[$defaultLocale] ?? ($field['label'] ?? '');
                                    $fieldType = $field['type'] ?? 'text';
                                    $subFields = $field['fields'] ?? [];
                                    $addButtonLabels = $field['add_button_labels'] ?? [];
                                @endphp
                                <div class="card field-card border-0 shadow-sm rounded-3 mb-2 overflow-hidden is-collapsed" data-index="{{ $index }}" data-field-type="{{ $fieldType }}">
                                    <div class="card-body p-2">
                                        <div class="d-flex align-items-center gap-2 field-card-toolbar">
                                            <div class="drag-handle text-muted cursor-move px-1">
                                                <i class="bi bi-grip-vertical fs-5"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="row g-2 align-items-center">
                                                    <div class="col-md-4">
                                                        <input type="text" name="fields[{{ $index }}][key]" class="form-control form-control-sm field-key-input" value="{{ $field['key'] ?? '' }}" required>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <span class="badge bg-primary-soft text-primary field-type-badge text-capitalize px-2 py-1 small d-block text-center rounded-2">{{ str_replace('_', ' ', $fieldType) }}</span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="field-summary text-muted small text-truncate" title="{{ $fieldPreviewLabel }}">
                                                            {{ $fieldPreviewLabel !== '' ? $fieldPreviewLabel : __('No label yet') }}
                                                        </div>
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
                                                                   name="fields[{{ $index }}][labels][{{ $locale['code'] }}]"
                                                                   class="form-control form-control-sm field-label-locale-input field-locale-input"
                                                                   data-locale-code="{{ $locale['code'] }}"
                                                                   placeholder="{{ __('Label') }}"
                                                                   value="{{ old('fields.'.$index.'.labels.'.$locale['code'], $fieldLabels[$locale['code']] ?? ($locale['code'] === $defaultLocale ? ($field['label'] ?? '') : '')) }}"
                                                                   {{ $isSelected ? '' : 'disabled' }}>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label small text-muted mb-1">{{ __('Help text') }} ({{ strtoupper($locale['code']) }})</label>
                                                            <input type="text"
                                                                   name="fields[{{ $index }}][helps][{{ $locale['code'] }}]"
                                                                   class="form-control form-control-sm field-locale-input"
                                                                   data-locale-code="{{ $locale['code'] }}"
                                                                   placeholder="{{ __('Help text...') }}"
                                                                   value="{{ old('fields.'.$index.'.helps.'.$locale['code'], $fieldHelps[$locale['code']] ?? ($locale['code'] === $defaultLocale ? ($field['help'] ?? '') : '')) }}"
                                                                   {{ $isSelected ? '' : 'disabled' }}>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                            @if($fieldType === 'repeater')
                                                <div class="repeater-add-button-section mt-3 p-3 bg-light-soft rounded-3">
                                                    <label class="form-label small fw-bold text-muted mb-2 d-block">{{ __('Add Button Label') }}</label>
                                                    @foreach($locales as $locale)
                                                        @php $isSelected = in_array($locale['code'], $selectedLocaleCodes, true); @endphp
                                                        <div class="mb-2 repeater-button-label-pane {{ $locale['code'] === $firstSelectedLocaleCode ? '' : 'd-none' }}" data-locale-code="{{ $locale['code'] }}">
                                                            <input type="text"
                                                                   name="fields[{{ $index }}][add_button_labels][{{ $locale['code'] }}]"
                                                                   class="form-control form-control-sm field-locale-input"
                                                                   data-locale-code="{{ $locale['code'] }}"
                                                                   placeholder="{{ __('e.g. Add item') }}"
                                                                   value="{{ old('fields.'.$index.'.add_button_labels.'.$locale['code'], $addButtonLabels[$locale['code']] ?? ($locale['code'] === $defaultLocale ? ($field['add_button_label'] ?? '') : '')) }}"
                                                                   {{ $isSelected ? '' : 'disabled' }}>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <div class="repeater-subfields-section mt-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <label class="form-label small fw-bold text-muted mb-0">{{ __('Sub-Fields') }}</label>
                                                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-2 add-sub-field" data-field-index="{{ $index }}">
                                                            <i class="bi bi-plus-lg me-1"></i> {{ __('Add Sub-Field') }}
                                                        </button>
                                                    </div>
                                                    <div class="sub-fields-container p-3 bg-light-soft rounded-3 border border-light-subtle">
                                                        @if(count($subFields) === 0)
                                                            <div class="no-sub-fields-message text-center text-muted small">{{ __('No sub-fields yet') }}</div>
                                                        @else
                                                            @foreach($subFields as $subIndex => $subField)
                                                                @php
                                                                    $subFieldLabels = $subField['labels'] ?? [];
                                                                    $subFieldType = $subField['type'] ?? 'text';
                                                                @endphp
                                                                <div class="card sub-field-card border-0 shadow-sm rounded-3 mb-2 bg-white" data-sub-index="{{ $subIndex }}">
                                                                    <div class="card-body p-2">
                                                                        <div class="d-flex align-items-center gap-2">
                                                                            <div class="flex-grow-1">
                                                                                <div class="row g-2 align-items-center">
                                                                                    <div class="col-md-4">
                                                                                        <input type="text" name="fields[{{ $index }}][fields][{{ $subIndex }}][key]" class="form-control form-control-sm sub-field-key-input" value="{{ $subField['key'] ?? '' }}" required>
                                                                                    </div>
                                                                                    <div class="col-md-3">
                                                                                        <select name="fields[{{ $index }}][fields][{{ $subIndex }}][type]" class="form-select form-select-sm sub-field-type-select">
                                                                                            <option value="text" @selected($subFieldType === 'text')>Text</option>
                                                                                            <option value="textarea" @selected($subFieldType === 'textarea')>Textarea</option>
                                                                                            <option value="rich_text" @selected($subFieldType === 'rich_text')>Rich Text</option>
                                                                                            <option value="image" @selected($subFieldType === 'image')>Image</option>
                                                                                            <option value="gallery" @selected($subFieldType === 'gallery')>Gallery</option>
                                                                                            <option value="number" @selected($subFieldType === 'number')>Number</option>
                                                                                            <option value="select" @selected($subFieldType === 'select')>Select</option>
                                                                                            <option value="icon" @selected($subFieldType === 'icon')>Icon</option>
                                                                                        </select>
                                                                                    </div>
                                                                                    <div class="col-md-3">
                                                                                        <input type="text" name="fields[{{ $index }}][fields][{{ $subIndex }}][labels][{{ $defaultLocale }}]" class="form-control form-control-sm sub-field-label-input" value="{{ $subFieldLabels[$defaultLocale] ?? ($subField['label'] ?? '') }}" placeholder="{{ __('Label') }}">
                                                                                    </div>
                                                                                    <div class="col-md-2 text-end d-flex justify-content-end align-items-center gap-2">
                                                                                        <button type="button" class="btn btn-sm btn-link link-danger p-0 remove-sub-field" title="{{ __('Remove') }}">
                                                                                            <i class="bi bi-x-circle fs-5"></i>
                                                                                        </button>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <input type="hidden" name="fields[{{ $index }}][type]" value="{{ $fieldType }}">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary px-5 py-3 rounded-3 shadow-premium d-flex align-items-center gap-2">
                        <i class="bi bi-check-lg"></i>
                        <span class="fw-bold">{{ __('Update Block Structure') }}</span>
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
                            <input type="text" name="key" id="blockKey" class="form-control form-control-sm @error('key') is-invalid @enderror" value="{{ old('key', $blockType->key) }}" required @readonly($isSystemBlock)>
                            @error('key') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">{{ __('Scope') }}</label>
                            @if($isSystemBlock)
                                <input type="hidden" name="scope" value="{{ old('scope', $blockType->scope) }}">
                                <input type="text" class="form-control form-control-sm" value="{{ ucfirst(old('scope', $blockType->scope)) }}" readonly>
                            @else
                                <select class="form-select form-select-sm @error('scope') is-invalid @enderror" name="scope" required>
                                    <option value="page" @selected(old('scope', $blockType->scope) === 'page')>{{ __('Page') }}</option>
                                    <option value="post" @selected(old('scope', $blockType->scope) === 'post')>{{ __('Post') }}</option>
                                    <option value="product" @selected(old('scope', $blockType->scope) === 'product')>{{ __('Product') }}</option>
                                    <option value="global" @selected(old('scope', $blockType->scope) === 'global')>{{ __('Global') }}</option>
                                </select>
                            @endif
                            @error('scope') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">{{ __('Icon (Class)') }}</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light-soft border-end-0"><i class="bi {{ old('icon', $blockType->icon ?: 'bi-box') }}" id="iconPreview"></i></span>
                                <input type="text" name="icon" id="iconInput" class="form-control border-start-0" value="{{ old('icon', $blockType->icon ?: 'bi-box') }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">{{ __('Sort Order') }}</label>
                            <input type="number" name="sort_order" class="form-control form-control-sm" value="{{ old('sort_order', $blockType->sort_order ?? 0) }}">
                        </div>

                        <hr class="my-3 opacity-10">

                        <div class="form-check form-switch p-0 ps-5">
                            <input class="form-check-input ms-n5" type="checkbox" value="1" id="is_enabled" name="is_enabled" @checked(old('is_enabled', $blockType->is_enabled))>
                            <label class="form-check-label fw-bold" for="is_enabled">{{ __('Enabled') }}</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <template id="fieldTemplate">
        <div class="card field-card border-0 shadow-sm rounded-3 mb-2 overflow-hidden is-collapsed" data-index="__INDEX__" data-field-type="__TYPE__">
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
                    <div class="repeater-add-button-section mt-3 p-3 bg-light-soft rounded-3 d-none" style="display: none;">
                        <label class="form-label small fw-bold text-muted mb-2 d-block">{{ __('Add Button Label') }}</label>
                        @foreach($locales as $locale)
                            @php $isSelected = in_array($locale['code'], $selectedLocaleCodes, true); @endphp
                            <div class="mb-2 repeater-button-label-pane {{ $locale['code'] === $firstSelectedLocaleCode ? '' : 'd-none' }}" data-locale-code="{{ $locale['code'] }}">
                                <input type="text"
                                       name="fields[__INDEX__][add_button_labels][{{ $locale['code'] }}]"
                                       class="form-control form-control-sm field-locale-input"
                                       data-locale-code="{{ $locale['code'] }}"
                                       placeholder="{{ __('e.g. Add item') }}"
                                       {{ $isSelected ? '' : 'disabled' }}>
                            </div>
                        @endforeach
                    </div>
                    <div class="repeater-subfields-section mt-3 d-none">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label small fw-bold text-muted mb-0">{{ __('Sub-Fields') }}</label>
                            <button type="button" class="btn btn-sm btn-primary rounded-pill px-2 add-sub-field">
                                <i class="bi bi-plus-lg me-1"></i> {{ __('Add Sub-Field') }}
                            </button>
                        </div>
                        <div class="sub-fields-container p-3 bg-light-soft rounded-3 border border-light-subtle">
                            <div class="no-sub-fields-message text-center text-muted small">{{ __('No sub-fields yet') }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <input type="hidden" name="fields[__INDEX__][type]" value="__TYPE__">
        </div>
    </template>

    <template id="subFieldTemplate">
        <div class="card sub-field-card border-0 shadow-sm rounded-3 mb-2 bg-white" data-sub-index="__SUB_INDEX__">
            <div class="card-body p-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-grow-1">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-4">
                                <input type="text" name="fields[__INDEX__][fields][__SUB_INDEX__][key]" class="form-control form-control-sm sub-field-key-input" placeholder="{{ __('Key') }}" required>
                            </div>
                            <div class="col-md-3">
                                <select name="fields[__INDEX__][fields][__SUB_INDEX__][type]" class="form-select form-select-sm sub-field-type-select">
                                    <option value="text">Text</option>
                                    <option value="textarea">Textarea</option>
                                    <option value="rich_text">Rich Text</option>
                                    <option value="image">Image</option>
                                    <option value="gallery">Gallery</option>
                                    <option value="number">Number</option>
                                    <option value="select">Select</option>
                                    <option value="icon">Icon</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="fields[__INDEX__][fields][__SUB_INDEX__][labels][__LOCALE__]" class="form-control form-control-sm sub-field-label-input" placeholder="{{ __('Label') }}">
                            </div>
                            <div class="col-md-2 text-end d-flex justify-content-end align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-link link-danger p-0 remove-sub-field" title="{{ __('Remove') }}">
                                    <i class="bi bi-x-circle fs-5"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
    const fieldsContainer = document.getElementById('fieldsContainer');
    const fieldTemplate = document.getElementById('fieldTemplate');
    const noFieldsMessage = document.querySelector('.no-fields-message');
    const blockKey = document.getElementById('blockKey');
    const iconInput = document.getElementById('iconInput');
    const iconPreview = document.getElementById('iconPreview');
    const localeCodes = @json(collect($locales)->pluck('code')->values()->all());
    const localeMeta = @json($locales);
    const fieldLangTabs = document.getElementById('fieldLangTabs');

    let fieldIndex = {{ count($fields) }};

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

    const slugify = (text) => {
        return text.toString().toLowerCase().replace(/\s+/g, '_').replace(/[^\w-]+/g, '').replace(/--+/g, '_').replace(/^-+/, '').replace(/-+$/, '');
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

    document.querySelectorAll('.block-label-input').forEach((input) => {
        input.addEventListener('input', function() {
            if (!blockKey.dataset.manual && this.value) {
                blockKey.value = slugify(this.value);
            }
        });
    });

    if (blockKey.value.trim() !== '') {
        blockKey.dataset.manual = 'true';
    }

    blockKey.addEventListener('input', () => {
        blockKey.dataset.manual = 'true';
    });

    iconInput.addEventListener('input', function() {
        iconPreview.className = `bi ${this.value}`;
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
    }

    function setupCardEvents(card) {
        const keyInput = card.querySelector('.field-key-input');
        const removeBtn = card.querySelector('.remove-field');
        const toggleBtn = card.querySelector('.field-toggle');
        const details = card.querySelector('.field-card-details');
        const summary = card.querySelector('.field-summary');
        const fieldIndex = card.dataset.index;
        const fieldType = card.dataset.fieldType;

        if (!keyInput) {
            return;
        }

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

        const initRepeaterUI = () => {
            if (fieldType === 'repeater') {
                const addButtonSection = card.querySelector('.repeater-add-button-section');
                const subFieldsSection = card.querySelector('.repeater-subfields-section');
                const addSubFieldBtn = card.querySelector('.add-sub-field');

                if (addButtonSection) {
                    addButtonSection.style.display = 'block';
                    addButtonSection.classList.remove('d-none');
                }

                if (subFieldsSection) {
                    subFieldsSection.style.display = 'block';
                }

                if (addSubFieldBtn) {
                    addSubFieldBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        addSubField(card, fieldIndex);
                    });
                }

                setupSubFieldEvents(card, fieldIndex);
            }
        };

        if (keyInput.value.trim() !== '') {
            keyInput.dataset.manual = 'true';
        }

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

        removeBtn?.addEventListener('click', function() {
            card.remove();
            reindexFieldInputs();
            if (fieldsContainer.querySelectorAll('.field-card').length === 0 && noFieldsMessage) {
                noFieldsMessage.classList.remove('d-none');
            }
        });

        syncSummary();
        setCollapsed(toggleBtn?.getAttribute('aria-expanded') !== 'true');
        initRepeaterUI();
    }

    function setupSubFieldEvents(fieldCard, fieldIndex) {
        const removeSubFieldBtns = fieldCard.querySelectorAll('.remove-sub-field');
        const container = fieldCard.querySelector('.sub-fields-container');
        const noMessage = container?.querySelector('.no-sub-fields-message');

        removeSubFieldBtns.forEach((btn) => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const subFieldCard = this.closest('.sub-field-card');
                subFieldCard.remove();
                updateSubFieldIndices(fieldCard, fieldIndex);
                updateNoSubFieldsMessage(fieldCard);
            });
        });
    }

    function addSubField(fieldCard, fieldIndex) {
        const container = fieldCard.querySelector('.sub-fields-container');
        const subFieldTemplate = document.getElementById('subFieldTemplate');
        const existingSubFields = container.querySelectorAll('.sub-field-card');
        const subIndex = existingSubFields.length;
        const noMessage = container.querySelector('.no-sub-fields-message');

        const localeCode = getActiveLocaleCode();
        const html = subFieldTemplate.innerHTML
            .replace(/__INDEX__/g, fieldIndex)
            .replace(/__SUB_INDEX__/g, subIndex)
            .replace(/__LOCALE__/g, localeCode);

        const wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        const subFieldCard = wrapper.firstElementChild;
        container.appendChild(subFieldCard);

        if (noMessage) {
            noMessage.style.display = 'none';
        }

        setupSubFieldEvents(fieldCard, fieldIndex);
    }

    function updateSubFieldIndices(fieldCard, fieldIndex) {
        const subFields = fieldCard.querySelectorAll('.sub-field-card');
        subFields.forEach((subField, index) => {
            subField.dataset.subIndex = String(index);
            subField.querySelectorAll('input[name], select[name]').forEach((input) => {
                input.name = input.name.replace(/fields\[\d+\]\[fields\]\[\d+\]/, `fields[${fieldIndex}][fields][${index}]`);
            });
        });
    }

    function updateNoSubFieldsMessage(fieldCard) {
        const container = fieldCard.querySelector('.sub-fields-container');
        const subFields = container.querySelectorAll('.sub-field-card');
        const noMessage = container.querySelector('.no-sub-fields-message');

        if (subFields.length === 0 && noMessage) {
            noMessage.style.display = 'block';
        }
    }

    document.querySelectorAll('.field-card').forEach((card) => {
        setupCardEvents(card);
    });

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

    document.getElementById('blockForm')?.addEventListener('submit', () => {
        reindexFieldInputs();
    });

    initFieldSortable();
    reindexFieldInputs();
    syncFieldLocalePaneVisibility(getActiveLocaleCode());
    syncLocaleFieldEnabledState();
    setFieldTabsActive(getActiveLocaleCode());
    syncFieldSummaries();
});
</script>
@endpush

