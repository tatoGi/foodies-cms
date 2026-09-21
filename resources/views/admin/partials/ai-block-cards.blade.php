@foreach($localeBlocks as $block)
    @php
        $editor = $blockTypeEditors[$block['type']] ?? null;
        $blockKey = (string) ($block['type'] ?? '');
        $instanceKey = (string) ($block['instance_key'] ?? $blockKey);
        $blockLabelValue = $editor['label'] ?? strtoupper($blockKey);
        $blockLabel = is_array($blockLabelValue) ? strtoupper($blockKey) : (string) $blockLabelValue;
        $blockIcon = $editor['icon'] ?? 'bi-box';
        $blockDescriptionValue = $editor['description'] ?? null;
        $blockDescription = is_array($blockDescriptionValue) ? null : $blockDescriptionValue;
        $sortOrder = (int) ($block['sort_order'] ?? 0);
        $blockData = is_array($block['data'] ?? null) ? $block['data'] : [];
    @endphp
    @if(is_array($editor))
        <div class="card page-block-card" data-block-key="{{ $blockKey }}" data-instance-key="{{ $instanceKey }}" data-locale-code="{{ $localeCode }}">
            <div class="card-header bg-light-soft d-flex align-items-center justify-content-between py-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi {{ $blockIcon }} text-muted"></i>
                    <span class="fw-semibold">{{ $blockLabel }}</span>
                    <code class="small text-primary">{{ $blockKey }}</code>
                </div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-light move-block-up" title="{{ __('Move up') }}">
                        <i class="bi bi-arrow-up"></i>
                    </button>
                    <button type="button" class="btn btn-light move-block-down" title="{{ __('Move down') }}">
                        <i class="bi bi-arrow-down"></i>
                    </button>
                    <button type="button" class="btn btn-light remove-block-instance" title="{{ __('Remove') }}">
                        <i class="bi bi-trash"></i>
                    </button>
                    <button type="button" class="btn btn-light toggle-block-body" title="{{ __('Collapse / Expand') }}">
                        <i class="bi bi-chevron-up"></i>
                    </button>
                </div>
            </div>
            <div class="card-body block-body-content">
                @if($blockDescription)
                    <p class="text-muted small mb-3">{{ $blockDescription }}</p>
                @endif
                <input type="hidden" class="block-type-input" name="blocks[{{ $localeCode }}][{{ $instanceKey }}][type]" value="{{ $blockKey }}">
                <input type="hidden" class="block-sort-order-input" name="blocks[{{ $localeCode }}][{{ $instanceKey }}][sort_order]" value="{{ $sortOrder }}">
                @foreach($editor['fields'] as $field)
                    @php
                        $fieldKey = (string) ($field['key'] ?? '');
                        $fieldType = (string) ($field['type'] ?? 'text');
                        $fieldLabelLocale = $field['labels'][$languageCode] ?? $field['labels'][$localeCode] ?? '';
                        $fieldLabelFallback = $field['label'] ?? ucfirst(str_replace('_', ' ', $fieldKey));
                        $fieldLabel = trim((string) (is_array($fieldLabelLocale) ? '' : ($fieldLabelLocale ?: (is_array($fieldLabelFallback) ? '' : $fieldLabelFallback))));
                        $fieldHelpLocale = $field['helps'][$languageCode] ?? $field['helps'][$localeCode] ?? '';
                        $fieldHelpFallback = $field['help'] ?? '';
                        $fieldHelp = trim((string) (is_array($fieldHelpLocale) ? '' : ($fieldHelpLocale ?: (is_array($fieldHelpFallback) ? '' : $fieldHelpFallback))));
                        $rawFieldValue = $blockData[$fieldKey] ?? '';
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
                            <textarea name="blocks[{{ $localeCode }}][{{ $instanceKey }}][data][{{ $fieldKey }}]" class="form-control form-control-sm" rows="5" data-editor="quill">{{ $fieldValue }}</textarea>
                        @elseif($fieldType === 'textarea')
                            <textarea name="blocks[{{ $localeCode }}][{{ $instanceKey }}][data][{{ $fieldKey }}]" class="form-control form-control-sm" data-editor="quill" rows="5">{{ $fieldValue }}</textarea>
                        @elseif($fieldType === 'image')
                            <input type="hidden" class="remove-image-input" name="blocks[{{ $localeCode }}][{{ $instanceKey }}][remove][{{ $fieldKey }}]" value="0">
                            <input type="text" name="blocks[{{ $localeCode }}][{{ $instanceKey }}][data][{{ $fieldKey }}]" class="form-control form-control-sm mb-2" placeholder="{{ __('Stored path') }}" value="{{ $fieldValue }}">
                            <button type="button" class="btn btn-outline-secondary btn-sm open-media-picker mb-2" data-picker-mode="image">
                                {{ __('Choose from Media') }}
                            </button>
                            <input type="file" name="blocks[{{ $localeCode }}][{{ $instanceKey }}][files][{{ $fieldKey }}]" class="form-control form-control-sm" accept="image/*">
                        @elseif($fieldType === 'gallery')
                            <textarea name="blocks[{{ $localeCode }}][{{ $instanceKey }}][data][{{ $fieldKey }}]" class="form-control form-control-sm mb-2" rows="3" placeholder="{{ __('Image paths separated by comma or new line') }}">{{ is_array($rawFieldValue) ? implode(PHP_EOL, $galleryItems) : $fieldValue }}</textarea>
                            <button type="button" class="btn btn-outline-secondary btn-sm open-media-picker mb-2" data-picker-mode="gallery">
                                {{ __('Add from Media') }}
                            </button>
                            <div class="gallery-remove-inputs"></div>
                            <input type="file" name="blocks[{{ $localeCode }}][{{ $instanceKey }}][files][{{ $fieldKey }}][]" class="form-control form-control-sm" accept="image/*" multiple>
                        @elseif($fieldType === 'repeater')
                            @include('admin.partials.repeater-field', [
                                'field' => $field,
                                'fieldNamePrefix' => "blocks[{$localeCode}][{$instanceKey}][data][{$fieldKey}]",
                                'rawFieldValue' => $rawFieldValue,
                            ])
                        @elseif($fieldType === 'select' && isset($field['options']) && is_array($field['options']) && count($field['options']) > 0)
                            <select name="blocks[{{ $localeCode }}][{{ $instanceKey }}][data][{{ $fieldKey }}]" class="form-select form-select-sm">
                                @foreach($field['options'] as $optionValue => $optionLabel)
                                    <option value="{{ (string) $optionValue }}" @selected((string) $fieldValue === (string) $optionValue)>{{ (string) $optionLabel }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="{{ $fieldType === 'number' ? 'number' : 'text' }}" name="blocks[{{ $localeCode }}][{{ $instanceKey }}][data][{{ $fieldKey }}]" class="form-control form-control-sm" value="{{ $fieldValue }}">
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
