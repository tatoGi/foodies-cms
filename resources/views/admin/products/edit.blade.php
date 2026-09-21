@extends('admin.layouts.app')

@section('title', __('Edit Product'))
@section('page_title', __('Edit Product'))

@php
    $selectedLocaleCodes = collect($selectedLocaleCodes ?? [])->map(static fn ($code): string => (string) $code)->values()->all();
    $firstSelectedLocaleCode = collect($locales)->pluck('code')->first(
        static fn ($code): bool => in_array((string) $code, $selectedLocaleCodes, true)
    );
@endphp

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .settings-panel { height: auto !important; }
    .tox-tinymce { border-radius: 12px !important; border: 1px solid var(--admin-border-strong) !important; }
    .page-block-card .card-header { border-bottom: 1px solid var(--admin-border); }
</style>
@endpush

@section('content')
    <div class="row mb-5">
        <div class="col-md-8">
            <h2 class="welcome-title mb-1">{{ __('Edit Product') }}</h2>
            <p class="text-muted mb-0">{{ __('Update product data, blocks and SEO.') }}</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="{{ route('admin.products.index') }}" class="btn btn-light-soft px-4 py-2 rounded-3 d-inline-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>{{ __('Back to List') }}</span>
            </a>
        </div>
    </div>

    @if($errors->has('names') || $errors->has('slugs'))
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
            {{ $errors->first('names') ?: $errors->first('slugs') }}
        </div>
    @endif

    <form action="{{ route('admin.products.update', $product) }}" method="POST" id="pageForm" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div id="selected-block-types-inputs">
            @foreach($selectedBlockTypes as $selectedBlockType)
                <input type="hidden" name="block_types[]" value="{{ $selectedBlockType }}">
            @endforeach
        </div>

        <div class="row g-4">
            <div class="col-xxl-9 col-xl-8 col-lg-7">
                <div class="dashboard-panel premium-shadow">
                    <div class="panel-header border-bottom-0 pb-0">
                        <div class="panel-header-title mb-3">
                            <i class="bi bi-translate me-2 text-primary"></i>
                            <span>{{ __('Product Content & SEO') }}</span>
                        </div>
                        @include('admin.partials.lang-tabs', ['showSeoTab' => true])
                    </div>

                    <div class="panel-body pt-0">
                        <div class="dashboard-panel premium-shadow mb-4">
                            <div class="panel-header border-bottom-0">
                                <div class="panel-header-title">
                                    <i class="bi bi-gear me-2 text-primary"></i>
                                    <span>{{ __('Product Settings') }}</span>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="row g-3">
                                    <div class="col-md-6 col-xl-3">
                                        <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('SKU') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku', $product->sku) }}" placeholder="PROD-001">
                                        @error('sku')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6 col-xl-3">
                                        <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('Brand') }}</label>
                                        <input type="text" name="brand" class="form-control @error('brand') is-invalid @enderror" value="{{ old('brand', $product->brand) }}" placeholder="e.g. NewHome">
                                        @error('brand')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6 col-xl-3">
                                        <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('Price') }} (GEL) <span class="text-danger">*</span></label>
                                        <input type="number" name="price" step="0.01" min="0" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $product->price) }}">
                                        @error('price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6 col-xl-3">
                                        <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('Sale Price') }} (GEL)</label>
                                        <input type="number" name="sale_price" id="sale_price_input" step="0.01" min="0" class="form-control @error('sale_price') is-invalid @enderror" value="{{ old('sale_price', $product->sale_price) }}" @disabled(!old('on_sale', $product->on_sale))>
                                        @error('sale_price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6 col-xl-3">
                                        <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('Stock') }}</label>
                                        <input type="number" name="stock" min="0" class="form-control @error('stock') is-invalid @enderror" value="{{ old('stock', $product->stock) }}">
                                        @error('stock')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6 col-xl-6">
                                        <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('Dimensions') }}</label>
                                        <input type="text" name="spec_dimensions" class="form-control @error('spec_dimensions') is-invalid @enderror" value="{{ old('spec_dimensions', $manualSpecDefaults['dimensions'] ?? '') }}" placeholder="40*60; 45*40; 40*31">
                                        @error('spec_dimensions')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6 col-xl-6">
                                        <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('Height') }}</label>
                                        <input type="text" name="spec_height" class="form-control @error('spec_height') is-invalid @enderror" value="{{ old('spec_height', $manualSpecDefaults['height'] ?? '') }}" placeholder="52 სმ ; 48 სმ ; 43 სმ">
                                        @error('spec_height')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6 col-xl-6">
                                        <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('Material') }}</label>
                                        <input type="text" name="spec_material" class="form-control @error('spec_material') is-invalid @enderror" value="{{ old('spec_material', $manualSpecDefaults['material'] ?? '') }}" placeholder="ხე">
                                        @error('spec_material')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6 col-xl-6">
                                        <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('Colors (text)') }}</label>
                                        <input type="text" name="spec_colors" class="form-control @error('spec_colors') is-invalid @enderror" value="{{ old('spec_colors', $manualSpecDefaults['colors'] ?? '') }}" placeholder="თეთრი, ყავისფერი">
                                        @error('spec_colors')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('Cover Image') }}</label>
                                        <input type="text" name="cover_image" id="cover_image_input" class="form-control mb-2 @error('cover_image') is-invalid @enderror" value="{{ old('cover_image', $product->cover_image) }}" placeholder="{{ __('Stored path') }}">
                                        <button type="button" class="btn btn-outline-secondary btn-sm open-media-picker" data-picker-mode="image" data-target-input="cover_image_input">
                                            <i class="bi bi-image me-1"></i>{{ __('Choose from Media') }}
                                        </button>
                                        @if($product->cover_image)
                                            @php
                                                $coverImageUrl = \Illuminate\Support\Str::startsWith($product->cover_image, ['http://', 'https://', '/'])
                                                    ? $product->cover_image
                                                    : asset('storage/'.$product->cover_image);
                                            @endphp
                                            <div class="mt-2">
                                                <img src="{{ $coverImageUrl }}" alt="{{ __('Cover Image') }}" style="max-width: 180px; max-height: 120px; border-radius: 10px; border: 1px solid var(--admin-border);">
                                            </div>
                                        @endif
                                        @error('cover_image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('Colors') }}</label>
                                        <div id="colors-container" class="mb-2">
                                            @forelse(old('colors', $product->colors ?? []) as $color)
                                                <div class="d-flex gap-2 align-items-center mb-2">
                                                    <input type="color" name="colors[]" class="form-control form-control-sm" style="width: 50px; height: 40px;" value="{{ $color }}">
                                                    <button type="button" class="btn btn-sm btn-danger remove-color-btn">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            @empty
                                            @endforelse
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-color-btn">
                                            <i class="bi bi-plus-lg me-1"></i>{{ __('Add Color') }}
                                        </button>
                                        @error('colors')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-3 mt-3">
                                    <div class="form-check form-switch p-0 ps-5 mb-0">
                                        <input class="form-check-input ms-n5" type="checkbox" id="on_sale" name="on_sale" value="1" @checked(old('on_sale', $product->on_sale)) onchange="document.getElementById('sale_price_input').disabled=!this.checked">
                                        <label class="form-check-label fw-bold" for="on_sale">{{ __('On Sale') }}</label>
                                    </div>
                                    <div class="form-check form-switch p-0 ps-5 mb-0">
                                        <input class="form-check-input ms-n5" type="checkbox" id="is_featured" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured))>
                                        <label class="form-check-label fw-bold" for="is_featured">{{ __('Featured') }}</label>
                                    </div>
                                    <div class="form-check form-switch p-0 ps-5 mb-0">
                                        <input class="form-check-input ms-n5" type="checkbox" id="show_in_reels" name="show_in_reels" value="1" @checked(old('show_in_reels', $product->show_in_reels))>
                                        <label class="form-check-label fw-bold" for="show_in_reels">{{ __('Show in Reels') }}</label>
                                    </div>
                                    <div class="form-check form-switch p-0 ps-5 mb-0">
                                        <input class="form-check-input ms-n5" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $product->is_active))>
                                        <label class="form-check-label fw-bold" for="is_active">{{ __('Active') }}</label>
                                    </div>
                                    <div class="form-check form-switch p-0 ps-5 mb-0">
                                        <input class="form-check-input ms-n5" type="checkbox" id="published" name="published" value="1" @checked(old('published', $product->published))>
                                        <label class="form-check-label fw-bold" for="published">{{ __('Published') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-content tab-content-premium" id="langTabsContent">
                            @foreach($locales as $locale)
                                @php $isSelected = in_array($locale['code'], $selectedLocaleCodes, true); @endphp
                                <div class="tab-pane fade {{ $locale['code'] === $firstSelectedLocaleCode ? 'show active' : '' }} {{ $isSelected ? '' : 'd-none' }}"
                                     id="lang-panel-{{ $locale['code'] }}"
                                     data-locale-code="{{ $locale['code'] }}"
                                     role="tabpanel">

                                    <div class="row g-4 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">
                                                {{ __('Name') }} ({{ strtoupper($locale['code']) }})
                                            </label>
                                            <input type="text"
                                                   name="names[{{ $locale['code'] }}]"
                                                   id="name-{{ $locale['code'] }}"
                                                   class="form-control @error('names.'.$locale['code']) is-invalid @enderror"
                                                   {{ $isSelected ? '' : 'disabled' }}
                                                   value="{{ old('names.'.$locale['code'], $translations[$locale['code']]->title ?? '') }}">
                                            @error('names.'.$locale['code'])
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
                                                   value="{{ old('slugs.'.$locale['code'], $translations[$locale['code']]->slug ?? '') }}">
                                            @error('slugs.'.$locale['code'])
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">
                                                {{ __('Category') }} ({{ strtoupper($locale['code']) }})
                                            </label>
                                            <input type="text"
                                                   name="categories[{{ $locale['code'] }}]"
                                                   class="form-control @error('categories.'.$locale['code']) is-invalid @enderror"
                                                   {{ $isSelected ? '' : 'disabled' }}
                                                   value="{{ old('categories.'.$locale['code'], $translations[$locale['code']]->category ?? '') }}">
                                            @error('categories.'.$locale['code'])
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">
                                            {{ __('Description') }}
                                        </label>
                                        <textarea name="descriptions[{{ $locale['code'] }}]"
                                                  class="form-control editor-field @error('descriptions.'.$locale['code']) is-invalid @enderror"
                                                  rows="5" {{ $isSelected ? '' : 'disabled' }}>{{ old('descriptions.'.$locale['code'], $translations[$locale['code']]->content ?? '') }}</textarea>
                                        @error('descriptions.'.$locale['code'])
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-4">
                                        @include('admin.partials.ai-locale-toolbar', [
                                            'localeCode' => $locale['code'],
                                            'translateUrl' => route('admin.products.ai.translate', $product),
                                            'seoUrl' => route('admin.products.ai.seo', $product),
                                        ])
                                        <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">
                                            {{ __('Product Blocks') }}
                                        </label>
                                        <div class="page-blocks-container d-flex flex-column gap-3" id="page-blocks-{{ $locale['code'] }}" data-locale-code="{{ $locale['code'] }}">
                                            @foreach($localeBlocks[$locale['code']] ?? [] as $block)
                                                @php
                                                    $editor = $blockTypeEditors[$block['key']] ?? null;
                                                    $blockLabelValue = $editor['label'] ?? strtoupper($block['key']);
                                                    $blockLabel = is_array($blockLabelValue) ? strtoupper($block['key']) : (string) $blockLabelValue;
                                                    $blockIcon = $editor ? ($editor['icon'] ?? 'bi-box') : 'bi-box';
                                                    $blockDescriptionValue = $editor['description'] ?? null;
                                                    $blockDescription = is_array($blockDescriptionValue) ? null : $blockDescriptionValue;
                                                @endphp
                                                @if($editor)
                                                    <div class="card page-block-card" data-block-key="{{ $block['key'] }}" data-instance-key="{{ $block['instance_key'] }}" data-locale-code="{{ $locale['code'] }}">
                                                        <div class="card-header bg-light-soft d-flex align-items-center justify-content-between py-2">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <i class="bi {{ $blockIcon }} text-muted"></i>
                                                                <span class="fw-semibold">{{ $blockLabel }}</span>
                                                                <code class="small text-primary">{{ $block['key'] }}</code>
                                                            </div>
                                                            <div class="btn-group btn-group-sm">
                                                                <button type="button" class="btn btn-light move-block-up" title="{{ __('Move up') }}"><i class="bi bi-arrow-up"></i></button>
                                                                <button type="button" class="btn btn-light move-block-down" title="{{ __('Move down') }}"><i class="bi bi-arrow-down"></i></button>
                                                                <button type="button" class="btn btn-light remove-block-instance" title="{{ __('Remove') }}"><i class="bi bi-trash"></i></button>
                                                                <button type="button" class="btn btn-light toggle-block-body" title="{{ __('Collapse / Expand') }}"><i class="bi bi-chevron-up"></i></button>
                                                            </div>
                                                        </div>
                                                        <div class="card-body block-body-content">
                                                            <input type="hidden" class="block-type-input" name="blocks[{{ $locale['code'] }}][{{ $block['instance_key'] }}][type]" value="{{ $block['key'] }}">
                                                            <input type="hidden" class="block-sort-order-input" name="blocks[{{ $locale['code'] }}][{{ $block['instance_key'] }}][sort_order]" value="{{ (int) $block['sort_order'] }}">
                                                            @foreach($editor['fields'] as $field)
                                                                @php
                                                                    $fieldKey = (string) ($field['key'] ?? '');
                                                                    $fieldType = (string) ($field['type'] ?? 'text');
                                                                    $fieldLabelLocale = $field['labels'][$locale['code']] ?? '';
                                                                    $fieldLabelFallback = $field['label'] ?? ucfirst(str_replace('_', ' ', $fieldKey));
                                                                    $fieldLabel = trim((string) (is_array($fieldLabelLocale) ? '' : ($fieldLabelLocale ?: (is_array($fieldLabelFallback) ? '' : $fieldLabelFallback))));
                                                                    $fieldHelpLocale = $field['helps'][$locale['code']] ?? '';
                                                                    $fieldHelpFallback = $field['help'] ?? '';
                                                                    $fieldHelp = trim((string) (is_array($fieldHelpLocale) ? '' : ($fieldHelpLocale ?: (is_array($fieldHelpFallback) ? '' : $fieldHelpFallback))));
                                                                    $rawFieldValue = $block['data'][$fieldKey] ?? '';
                                                                    $fieldValue = is_array($rawFieldValue) ? '' : (string) $rawFieldValue;
                                                                    $galleryItems = ($fieldType === 'gallery' && is_array($rawFieldValue))
                                                                        ? collect($rawFieldValue)
                                                                            ->filter(static fn ($value): bool => is_scalar($value) || $value === null)
                                                                            ->map(static fn ($value): string => trim((string) $value))
                                                                            ->filter(static fn (string $value): bool => $value !== '')
                                                                            ->values()
                                                                            ->all()
                                                                        : [];
                                                                @endphp
                                                                <div class="mb-3">
                                                                    <label class="form-label small fw-bold text-muted">{{ $fieldLabel }}</label>
                                                                    @if($fieldType === 'rich_text')
                                                                        <textarea name="blocks[{{ $locale['code'] }}][{{ $block['key'] }}][data][{{ $fieldKey }}]" class="form-control form-control-sm" rows="5" data-editor="quill">{{ $fieldValue }}</textarea>
                                                                    @elseif($fieldType === 'textarea')
                                                                        <textarea name="blocks[{{ $locale['code'] }}][{{ $block['key'] }}][data][{{ $fieldKey }}]" class="form-control form-control-sm" data-editor="quill" rows="5">{{ $fieldValue }}</textarea>
                                                                    @elseif($fieldType === 'image')
                                                                        <input type="hidden" class="remove-image-input" name="blocks[{{ $locale['code'] }}][{{ $block['key'] }}][remove][{{ $fieldKey }}]" value="0">
                                                                        @if($fieldValue !== '')
                                                                            @php
                                                                                $imageUrl = \Illuminate\Support\Str::startsWith($fieldValue, ['http://', 'https://', '/'])
                                                                                    ? $fieldValue
                                                                                    : asset('storage/'.$fieldValue);
                                                                            @endphp
                                                                            <div class="mb-2 image-preview-wrap position-relative d-inline-block">
                                                                                <a href="{{ $imageUrl }}" target="_blank" class="small text-decoration-none d-inline-block mb-2">{{ __('Current image') }}</a>
                                                                                <div>
                                                                                    <img src="{{ $imageUrl }}" alt="{{ $fieldLabel }}" style="max-width: 180px; max-height: 120px; border-radius: 8px; border: 1px solid #dee2e6;">
                                                                                </div>
                                                                                <button type="button" class="btn btn-sm btn-danger position-absolute remove-single-image" data-confirm="{{ __('Remove image?') }}" style="top: -6px; right: -6px; line-height: 1; padding: 2px 7px;">&times;</button>
                                                                            </div>
                                                                        @endif
                                                                        <input type="text" name="blocks[{{ $locale['code'] }}][{{ $block['key'] }}][data][{{ $fieldKey }}]" class="form-control form-control-sm mb-2" placeholder="{{ __('Stored path') }}" value="{{ $fieldValue }}">
                                                                        <button type="button" class="btn btn-outline-secondary btn-sm open-media-picker mb-2" data-picker-mode="image">{{ __('Choose from Media') }}</button>
                                                                        <input type="file" name="blocks[{{ $locale['code'] }}][{{ $block['key'] }}][files][{{ $fieldKey }}]" class="form-control form-control-sm" accept="image/*">
                                                                    @elseif($fieldType === 'gallery')
                                                                        <textarea name="blocks[{{ $locale['code'] }}][{{ $block['key'] }}][data][{{ $fieldKey }}]" class="form-control form-control-sm mb-2" rows="3" placeholder="{{ __('Image paths separated by comma or new line') }}">{{ is_array($block['data'][$fieldKey] ?? null) ? implode(PHP_EOL, (array) $block['data'][$fieldKey]) : $fieldValue }}</textarea>
                                                                        <button type="button" class="btn btn-outline-secondary btn-sm open-media-picker mb-2" data-picker-mode="gallery">{{ __('Add from Media') }}</button>
                                                                        @if(count($galleryItems) > 0)
                                                                            <div class="d-flex flex-wrap gap-2 mb-2">
                                                                                @foreach($galleryItems as $galleryPath)
                                                                                    @php
                                                                                        $galleryUrl = \Illuminate\Support\Str::startsWith($galleryPath, ['http://', 'https://', '/'])
                                                                                            ? $galleryPath
                                                                                            : asset('storage/'.$galleryPath);
                                                                                    @endphp
                                                                                    <div class="position-relative d-inline-block gallery-item" data-gallery-path="{{ $galleryPath }}">
                                                                                        <a href="{{ $galleryUrl }}" target="_blank">
                                                                                            <img src="{{ $galleryUrl }}" alt="{{ $fieldLabel }}" style="width: 78px; height: 78px; object-fit: cover; border-radius: 8px; border: 1px solid #dee2e6;">
                                                                                        </a>
                                                                                        <button type="button" class="btn btn-sm btn-danger position-absolute remove-gallery-item" data-path="{{ $galleryPath }}" style="top: -6px; right: -6px; line-height: 1; padding: 0 6px;">&times;</button>
                                                                                    </div>
                                                                                @endforeach
                                                                            </div>
                                                                        @endif
                                                                        <div class="gallery-remove-inputs"></div>
                                                                        <input type="file" name="blocks[{{ $locale['code'] }}][{{ $block['key'] }}][files][{{ $fieldKey }}][]" class="form-control form-control-sm" accept="image/*" multiple>
                                                                    @elseif($fieldType === 'repeater')
                                                                        @include('admin.partials.repeater-field', [
                                                                            'field' => $field,
                                                                            'fieldNamePrefix' => "blocks[{$locale['code']}][{$block['key']}][data][{$fieldKey}]",
                                                                            'rawFieldValue' => $rawFieldValue,
                                                                        ])
                                                                    @elseif($fieldType === 'select' && isset($field['options']) && is_array($field['options']) && count($field['options']) > 0)
                                                                        <select name="blocks[{{ $locale['code'] }}][{{ $block['key'] }}][data][{{ $fieldKey }}]" class="form-select form-select-sm">
                                                                            @foreach($field['options'] as $optionValue => $optionLabel)
                                                                                <option value="{{ (string) $optionValue }}" @selected((string) $fieldValue === (string) $optionValue)>{{ (string) $optionLabel }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    @else
                                                                        <input type="{{ $fieldType === 'number' ? 'number' : 'text' }}" name="blocks[{{ $locale['code'] }}][{{ $block['key'] }}][data][{{ $fieldKey }}]" class="form-control form-control-sm" value="{{ $fieldValue }}">
                                                                    @endif
                                                                    @if($fieldHelp !== '')
                                                                        <div class="form-text">{{ $fieldHelp }}</div>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                        <div class="text-muted small mt-2 page-blocks-empty {{ count($localeBlocks[$locale['code']] ?? []) > 0 ? 'd-none' : '' }}" id="page-blocks-empty-{{ $locale['code'] }}">
                                            {{ __('Select blocks from the right panel to edit them here.') }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            {{-- SEO Tab --}}
                            <div class="tab-pane fade" id="seo-main-panel" role="tabpanel">
                                <div class="panel-header border-bottom-0 pb-0">
                                    <div class="panel-header-title mb-2">
                                        <i class="bi bi-search me-2 text-primary"></i>
                                        <span>{{ __('SEO Settings') }}</span>
                                    </div>
                                    @include('admin.partials.lang-tabs', ['prefix' => 'seo', 'targetPrefix' => 'seo-panel', 'isMaster' => false])
                                </div>
                                <div class="panel-body pt-3">
                                    <div class="tab-content" id="seoLangContent">
                                        @foreach($locales as $locale)
                                            @php $isSelected = in_array($locale['code'], $selectedLocaleCodes, true); @endphp
                                            <div class="tab-pane fade {{ $locale['code'] === $firstSelectedLocaleCode ? 'show active' : '' }} {{ $isSelected ? '' : 'd-none' }}"
                                                 id="seo-panel-{{ $locale['code'] }}" data-locale-code="{{ $locale['code'] }}" role="tabpanel">

                                                @php
                                                    $seoLabel = app()->getLocale() === 'ka' ? 'SEO-ის გენერაცია' : 'Generate SEO';
                                                @endphp
                                                <div class="d-flex justify-content-end mb-3">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary ai-generate-seo-btn">
                                                        <i class="bi bi-search me-1"></i>{{ $seoLabel }}
                                                    </button>
                                                </div>

                                                @php
                                                    $seoLabel = app()->getLocale() === 'ka' ? 'SEO-ის გენერაცია' : 'Generate SEO';
                                                    $seoUrl = isset($product) ? route('admin.products.ai.seo', $product) : route('admin.products.ai.seo-draft');
                                                    $draftMode = isset($product) ? 'false' : 'true';
                                                @endphp
                                                <div class="d-flex justify-content-end mb-3" data-seo-url="{{ $seoUrl }}" data-draft-mode="{{ $draftMode }}">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary ai-generate-seo-btn">
                                                        <i class="bi bi-search me-1"></i>{{ $seoLabel }}
                                                    </button>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">{{ __('Meta Title') }}</label>
                                                    <input type="text" name="meta_titles[{{ $locale['code'] }}]" class="form-control form-control-sm shadow-none" value="{{ old('meta_titles.'.$locale['code'], $translations[$locale['code']]->meta_title ?? '') }}" {{ $isSelected ? '' : 'disabled' }}>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">{{ __('Meta Description') }}</label>
                                                    <textarea name="meta_descriptions[{{ $locale['code'] }}]" class="form-control form-control-sm shadow-none" rows="3" {{ $isSelected ? '' : 'disabled' }}>{{ old('meta_descriptions.'.$locale['code'], $translations[$locale['code']]->meta_description ?? '') }}</textarea>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">{{ __('Keywords') }}</label>
                                                    <div class="keywords-tag-input" data-keywords-widget>
                                                        <div class="keywords-tag-list d-flex flex-wrap gap-2 mb-2"></div>
                                                        <input type="text" class="form-control form-control-sm shadow-none keywords-tag-editor" placeholder="{{ __('Type keyword and press Enter') }}" {{ $isSelected ? '' : 'disabled' }}>
                                                        <input type="hidden" class="keywords-tag-hidden" name="keywords[{{ $locale['code'] }}]" value="{{ old('keywords.'.$locale['code'], $translations[$locale['code']]->keywords ?? '') }}" {{ $isSelected ? '' : 'disabled' }}>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">{{ __('Focus Keyword') }}</label>
                                                    <input type="text" name="focus_keywords[{{ $locale['code'] }}]" class="form-control form-control-sm shadow-none" value="{{ old('focus_keywords.'.$locale['code'], $translations[$locale['code']]->focus_keyword ?? '') }}" {{ $isSelected ? '' : 'disabled' }}>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">{{ __('Canonical URL') }}</label>
                                                    <input type="url" name="canonical_urls[{{ $locale['code'] }}]" class="form-control form-control-sm shadow-none" value="{{ old('canonical_urls.'.$locale['code'], $translations[$locale['code']]->canonical_url ?? '') }}" {{ $isSelected ? '' : 'disabled' }}>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-5 opacity-10">

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-5 py-3 rounded-3 shadow-premium d-flex align-items-center gap-2">
                                <i class="bi bi-check-lg"></i>
                                <span class="fw-bold">{{ __('Update Product') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Sidebar --}}
            <div class="col-xxl-3 col-xl-4 col-lg-5">
                <div class="admin-form-sidebar">
                    {{-- Product Blocks --}}
                    <div class="dashboard-panel premium-shadow mb-4">
                        <div class="panel-header border-bottom-0">
                            <div class="panel-header-title">
                                <i class="bi bi-grid-3x3-gap me-2 text-primary"></i>
                                <span>{{ __('Product Blocks') }}</span>
                            </div>
                        </div>
                        <div class="panel-body">
                            @if(count($blockTypes) === 0)
                                <div class="text-muted small">{{ __('No product blocks available.') }}</div>
                            @else
                                @foreach($blockTypes as $blockType)
                                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                        <div>
                                            <div class="fw-semibold">
                                                <i class="bi {{ $blockType['icon'] }} me-1 text-muted"></i>
                                                {{ $blockType['label'] }}
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary add-block-instance" data-block-key="{{ $blockType['key'] }}">
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    </div>
                                @endforeach
                            @endif
                            @error('block_types')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- Linked Pages --}}
                    <div class="dashboard-panel premium-shadow mb-4">
                        <div class="panel-header border-bottom-0">
                            <div class="panel-header-title">
                                <i class="bi bi-file-earmark-text me-2 text-primary"></i>
                                <span>{{ __('Linked Pages') }}</span>
                            </div>
                        </div>
                        <div class="panel-body">
                            <select id="linked-pages-select" name="page_ids[]" class="form-select @error('page_ids') is-invalid @enderror" multiple data-placeholder="{{ __('Search and select pages...') }}">
                                @foreach($availablePages as $pageItem)
                                    <option value="{{ $pageItem['id'] }}" @selected(in_array((int)$pageItem['id'], $selectedPageIds, true))>
                                        {{ $pageItem['title'] }}{{ $pageItem['slug'] !== '' ? ' (/'.$pageItem['slug'].')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('Assign this product to pages.') }}</div>
                            @error('page_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>

    @foreach($locales as $locale)
        @foreach($blockTypeEditors as $editor)
            @php
                $templateKey = (string) $editor['key'];
                $templateLabelValue = $editor['label'] ?? strtoupper($templateKey);
                $templateLabel = is_array($templateLabelValue) ? strtoupper($templateKey) : (string) $templateLabelValue;
                $templateIcon = (string) ($editor['icon'] ?? 'bi-box');
                $templateDescriptionValue = $editor['description'] ?? '';
                $templateDescription = is_array($templateDescriptionValue) ? '' : (string) $templateDescriptionValue;
            @endphp
            <template id="page-block-template-{{ $locale['code'] }}-{{ $templateKey }}">
                <div class="card page-block-card" data-block-key="{{ $templateKey }}" data-instance-key="__INSTANCE_KEY__" data-locale-code="{{ $locale['code'] }}">
                    <div class="card-header bg-light-soft d-flex align-items-center justify-content-between py-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi {{ $templateIcon }} text-muted"></i>
                            <span class="fw-semibold">{{ $templateLabel }}</span>
                            <code class="small text-primary">{{ $templateKey }}</code>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-light move-block-up" title="{{ __('Move up') }}"><i class="bi bi-arrow-up"></i></button>
                            <button type="button" class="btn btn-light move-block-down" title="{{ __('Move down') }}"><i class="bi bi-arrow-down"></i></button>
                            <button type="button" class="btn btn-light remove-block-instance" title="{{ __('Remove') }}"><i class="bi bi-trash"></i></button>
                            <button type="button" class="btn btn-light toggle-block-body" title="{{ __('Collapse / Expand') }}"><i class="bi bi-chevron-up"></i></button>
                        </div>
                    </div>
                    <div class="card-body block-body-content">
                        <input type="hidden" class="block-type-input" name="blocks[{{ $locale['code'] }}][__INSTANCE_KEY__][type]" value="{{ $templateKey }}">
                        <input type="hidden" class="block-sort-order-input" name="blocks[{{ $locale['code'] }}][__INSTANCE_KEY__][sort_order]" value="0">
                        @foreach($editor['fields'] as $field)
                            @php
                                $fieldKey = (string) ($field['key'] ?? '');
                                $fieldType = (string) ($field['type'] ?? 'text');
                                $fieldLabelLocale = $field['labels'][$locale['code']] ?? '';
                                $fieldLabelFallback = $field['label'] ?? ucfirst(str_replace('_', ' ', $fieldKey));
                                $fieldLabel = trim((string) (is_array($fieldLabelLocale) ? '' : ($fieldLabelLocale ?: (is_array($fieldLabelFallback) ? '' : $fieldLabelFallback))));
                                $fieldHelpLocale = $field['helps'][$locale['code']] ?? '';
                                $fieldHelpFallback = $field['help'] ?? '';
                                $fieldHelp = trim((string) (is_array($fieldHelpLocale) ? '' : ($fieldHelpLocale ?: (is_array($fieldHelpFallback) ? '' : $fieldHelpFallback))));
                                $defaultData = (array) ($editor['default_data'] ?? []);
                                $rawDefaultValue = $defaultData[$fieldKey] ?? '';
                                $defaultValue = is_array($rawDefaultValue) ? '' : (string) $rawDefaultValue;
                            @endphp
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">{{ $fieldLabel }}</label>
                                @if($fieldType === 'rich_text')
                                    <textarea name="blocks[{{ $locale['code'] }}][{{ $templateKey }}][data][{{ $fieldKey }}]" class="form-control form-control-sm" rows="5" data-editor="quill">{{ $defaultValue }}</textarea>
                                @elseif($fieldType === 'textarea')
                                    <textarea name="blocks[{{ $locale['code'] }}][{{ $templateKey }}][data][{{ $fieldKey }}]" class="form-control form-control-sm" data-editor="quill" rows="5">{{ $defaultValue }}</textarea>
                                @elseif($fieldType === 'image')
                                    <input type="hidden" class="remove-image-input" name="blocks[{{ $locale['code'] }}][{{ $templateKey }}][remove][{{ $fieldKey }}]" value="0">
                                    <input type="text" name="blocks[{{ $locale['code'] }}][{{ $templateKey }}][data][{{ $fieldKey }}]" class="form-control form-control-sm mb-2" placeholder="{{ __('Stored path') }}" value="{{ $defaultValue }}">
                                    <button type="button" class="btn btn-outline-secondary btn-sm open-media-picker mb-2" data-picker-mode="image">{{ __('Choose from Media') }}</button>
                                    <input type="file" name="blocks[{{ $locale['code'] }}][{{ $templateKey }}][files][{{ $fieldKey }}]" class="form-control form-control-sm" accept="image/*">
                                @elseif($fieldType === 'gallery')
                                    <textarea name="blocks[{{ $locale['code'] }}][{{ $templateKey }}][data][{{ $fieldKey }}]" class="form-control form-control-sm mb-2" rows="3">{{ $defaultValue }}</textarea>
                                    <button type="button" class="btn btn-outline-secondary btn-sm open-media-picker mb-2" data-picker-mode="gallery">{{ __('Add from Media') }}</button>
                                    <div class="gallery-remove-inputs"></div>
                                    <input type="file" name="blocks[{{ $locale['code'] }}][{{ $templateKey }}][files][{{ $fieldKey }}][]" class="form-control form-control-sm" accept="image/*" multiple>
                                @elseif($fieldType === 'repeater')
                                    @include('admin.partials.repeater-field', [
                                        'field' => $field,
                                        'fieldNamePrefix' => "blocks[{$locale['code']}][{$templateKey}][data][{$fieldKey}]",
                                        'rawFieldValue' => $rawDefaultValue,
                                    ])
                                @elseif($fieldType === 'select' && isset($field['options']) && is_array($field['options']) && count($field['options']) > 0)
                                    <select name="blocks[{{ $locale['code'] }}][{{ $templateKey }}][data][{{ $fieldKey }}]" class="form-select form-select-sm">
                                        @foreach($field['options'] as $optionValue => $optionLabel)
                                            <option value="{{ (string) $optionValue }}" @selected((string)$defaultValue === (string)$optionValue)>{{ (string) $optionLabel }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="{{ $fieldType === 'number' ? 'number' : 'text' }}" name="blocks[{{ $locale['code'] }}][{{ $templateKey }}][data][{{ $fieldKey }}]" class="form-control form-control-sm" value="{{ $defaultValue }}">
                                @endif
                                @if($fieldHelp !== '')
                                    <div class="form-text">{{ $fieldHelp }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </template>
        @endforeach
    @endforeach

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const linkedPagesSelect = document.getElementById('linked-pages-select');
    if (linkedPagesSelect) {
        new TomSelect(linkedPagesSelect, {
            plugins: ['remove_button'],
            maxOptions: 5000,
            hideSelected: true,
            closeAfterSelect: false,
            create: false,
            render: {
                option: function(data, escape) {
                    return '<div class="linked-item-option">' +
                        '<div class="linked-item-icon"><i class="bi bi-file-earmark-text"></i></div>' +
                        '<div>' +
                            '<div class="fw-bold">' + escape(data.text) + '</div>' +
                            '<div class="small text-muted">' + (data.value || '') + '</div>' +
                        '</div>' +
                    '</div>';
                },
                item: function(data, escape) {
                    return '<div><i class="bi bi-file-earmark-text me-2"></i>' + escape(data.text) + '</div>';
                }
            }
        });
    }

    const slugify = (text) => text.toString().normalize('NFKC').toLowerCase().replace(/[^\p{L}\p{N}\s-]+/gu, '').replace(/\s+/g, '-').replace(/\-\-+/g, '-').replace(/^-+/, '').replace(/-+$/, '');

    const localeCodes = @json(collect($locales)->pluck('code')->values()->all());
    const addBlockButtons = document.querySelectorAll('.add-block-instance');
@include('admin.partials.repeater-script')
@include('admin.partials.block-instance-script')

    addBlockButtons.forEach((button) => {
        button.addEventListener('click', function() {
            const blockKey = this.dataset.blockKey || '';
            if (blockKey !== '') {
                addBlockInstance(blockKey);
            }
        });
    });

    document.addEventListener('click', function(event) {
        const removeBlockButton = event.target.closest('.remove-block-instance');
        const toggleButton = event.target.closest('.toggle-block-body');
        const upButton = event.target.closest('.move-block-up');
        const downButton = event.target.closest('.move-block-down');
        const removeSingle = event.target.closest('.remove-single-image');
        const removeGallery = event.target.closest('.remove-gallery-item');
        const addRepeaterItemButton = event.target.closest('.add-repeater-item');
        const removeRepeaterItemButton = event.target.closest('.remove-repeater-item');

        if (addRepeaterItemButton) {
            const repeaterField = addRepeaterItemButton.closest('[data-repeater-field]');
            const template = repeaterField?.querySelector('template[data-repeater-template]');
            const itemsContainer = repeaterField?.querySelector('.repeater-items');
            if (template && itemsContainer) {
                itemsContainer.appendChild(template.content.cloneNode(true));
                refreshRepeaterField(repeaterField);
            }
            return;
        }

        if (removeRepeaterItemButton) {
            const repeaterField = removeRepeaterItemButton.closest('[data-repeater-field]');
            const repeaterItem = removeRepeaterItemButton.closest('[data-repeater-item]');
            if (repeaterItem) {
                repeaterItem.remove();
            }
            if (repeaterField) {
                refreshRepeaterField(repeaterField);
            }
            return;
        }

        if (removeSingle) {
            const wrap = removeSingle.closest('.image-preview-wrap');
            const removeInput = removeSingle.closest('.card-body, .panel-body')?.querySelector('.remove-image-input');
            if (removeInput) removeInput.value = '1';
            if (wrap) wrap.remove();
            return;
        }

        if (removeGallery) {
            const path = removeGallery.dataset.path;
            const item = removeGallery.closest('.gallery-item');
            const container = removeGallery.closest('.card-body, .panel-body');
            if (path && container) {
                const removeContainer = container.querySelector('.gallery-remove-inputs');
                if (removeContainer) {
                    const inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = removeGallery.closest('[data-block-key]')
                        ? `blocks[${removeGallery.closest('[data-locale-code]')?.dataset.localeCode}][${removeGallery.closest('[data-block-key]')?.dataset.blockKey}][remove_gallery][]`
                        : 'remove_gallery[]';
                    inp.value = path;
                    removeContainer.appendChild(inp);
                }
            }
            if (item) item.remove();
            return;
        }

        if (toggleButton) {
            const card = toggleButton.closest('.page-block-card');
            const body = card?.querySelector('.block-body-content');
            const icon = toggleButton.querySelector('i');
            if (body && icon) {
                const willCollapse = !body.classList.contains('d-none');
                body.classList.toggle('d-none', willCollapse);
                icon.classList.toggle('bi-chevron-up', !willCollapse);
                icon.classList.toggle('bi-chevron-down', willCollapse);
            }
            return;
        }

        const button = upButton || downButton;
        if (!button) return;
        const instanceKey = button.closest('.page-block-card')?.dataset.instanceKey || '';
        if (instanceKey !== '') {
            moveBlockInstance(instanceKey, upButton ? 'up' : 'down');
        }
    });

    localeCodes.forEach(lc => {
        const nameInput = document.getElementById(`name-${lc}`);
        const slugInput = document.getElementById(`slug-${lc}`);
        if (!nameInput || !slugInput) return;
        if (slugInput.value.trim() !== '') slugInput.dataset.manual = 'true';
        nameInput.addEventListener('input', function() {
            if (!slugInput.dataset.manual && this.value) slugInput.value = slugify(this.value);
        });
        slugInput.addEventListener('input', function() {
            if (document.activeElement === this) this.dataset.manual = 'true';
        });
    });

    // Color Picker Functionality
    const addColorBtn = document.getElementById('add-color-btn');
    const colorsContainer = document.getElementById('colors-container');

    function addColorInput() {
        const colorDiv = document.createElement('div');
        colorDiv.className = 'd-flex gap-2 align-items-center mb-2';
        colorDiv.innerHTML = `
            <input type="color" name="colors[]" class="form-control form-control-sm" style="width: 50px; height: 40px;" value="#000000">
            <button type="button" class="btn btn-sm btn-danger remove-color-btn">
                <i class="bi bi-trash"></i>
            </button>
        `;
        colorsContainer.appendChild(colorDiv);
    }

    function setupColorRemoveButtons() {
        document.querySelectorAll('.remove-color-btn').forEach(btn => {
            btn.removeEventListener('click', removeColorHandler);
            btn.addEventListener('click', removeColorHandler);
        });
    }

    function removeColorHandler(e) {
        e.preventDefault();
        e.target.closest('div').remove();
    }

    addColorBtn.addEventListener('click', function(e) {
        e.preventDefault();
        addColorInput();
        setupColorRemoveButtons();
    });

    setupColorRemoveButtons();
});
</script>
@include('admin.partials.ai-content-tools-script')
@include('admin.partials.ai-block-generate-script')
@endpush
