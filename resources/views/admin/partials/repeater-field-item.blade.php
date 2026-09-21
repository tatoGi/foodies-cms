@php
    $rowIndexValue = $isTemplate ? '__INDEX__' : (string) $rowIndex;
    $firstSubFieldKey = isset($repeaterFields[0]['key']) ? (string) $repeaterFields[0]['key'] : null;
    $firstSubFieldValue = $firstSubFieldKey && isset($rowData[$firstSubFieldKey]) ? (string) $rowData[$firstSubFieldKey] : '';
@endphp

<div class="card repeater-item border" data-repeater-item>
    <div class="card-body p-3">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="small fw-semibold text-muted repeater-item-label">
                {{ __('Item') }} <span data-repeater-index>{{ $isTemplate ? '__NUMBER__' : $rowIndex + 1 }}</span>
                @if($firstSubFieldValue !== '' && !$isTemplate)
                    <span class="ms-2 text-dark" data-repeater-value-display>{{ $firstSubFieldValue }}</span>
                @elseif($firstSubFieldValue !== '' && $isTemplate)
                    <span class="ms-2 text-dark" data-repeater-value-display></span>
                @endif
            </span>
            <button type="button" class="btn btn-outline-danger btn-sm remove-repeater-item">
                <i class="bi bi-trash"></i>
            </button>
        </div>

        <div class="row g-3">
            @foreach($repeaterFields as $subField)
                @php
                    $subFieldKey = (string) ($subField['key'] ?? '');
                    $subFieldType = (string) ($subField['type'] ?? 'text');
                    $subFieldLabel = trim((string) ($subField['label'] ?? ucfirst(str_replace('_', ' ', $subFieldKey))));
                    $subFieldHelp = trim((string) ($subField['help'] ?? ''));
                    $subFieldValue = $isTemplate ? '' : (string) ($rowData[$subFieldKey] ?? '');
                    $subFieldNameTemplate = $fieldNamePrefix.'[__INDEX__]['.$subFieldKey.']';
                    $subFieldName = $fieldNamePrefix.'['.$rowIndexValue.']['.$subFieldKey.']';
                    $isFirstField = $subFieldKey === $firstSubFieldKey;
                @endphp
                <div class="col-md-{{ $subFieldType === 'textarea' ? '12' : '6' }}">
                    <label class="form-label small fw-bold text-muted">{{ $subFieldLabel }}</label>
                    @if($subFieldType === 'textarea')
                        <textarea
                            class="form-control form-control-sm {{ $isFirstField ? 'repeater-leading-field' : '' }}"
                            rows="3"
                            name="{{ $subFieldName }}"
                            data-name-template="{{ $subFieldNameTemplate }}"
                        >{{ $subFieldValue }}</textarea>
                    @elseif($subFieldType === 'select' && isset($subField['options']) && is_array($subField['options']))
                        <select
                            class="form-select form-select-sm {{ $isFirstField ? 'repeater-leading-field' : '' }}"
                            name="{{ $subFieldName }}"
                            data-name-template="{{ $subFieldNameTemplate }}"
                        >
                            @foreach($subField['options'] as $optionValue => $optionLabel)
                                <option value="{{ (string) $optionValue }}" @selected($subFieldValue === (string) $optionValue)>{{ (string) $optionLabel }}</option>
                            @endforeach
                        </select>
                    @elseif($subFieldType === 'color')
                        @php
                            $colorValue = trim($subFieldValue) !== '' ? $subFieldValue : '#000000';
                        @endphp
                        <div class="d-flex align-items-center gap-2 repeater-color-field">
                            <input
                                type="color"
                                class="form-control form-control-color form-control-sm repeater-color-picker"
                                value="{{ $colorValue }}"
                                data-color-source="{{ $subFieldName }}"
                            >
                            <input
                                type="text"
                                class="form-control form-control-sm repeater-color-text {{ $isFirstField ? 'repeater-leading-field' : '' }}"
                                name="{{ $subFieldName }}"
                                data-name-template="{{ $subFieldNameTemplate }}"
                                value="{{ $subFieldValue }}"
                                placeholder="#000000"
                            >
                        </div>
                    @else
                        <input
                            type="{{ $subFieldType === 'number' ? 'number' : 'text' }}"
                            class="form-control form-control-sm {{ $isFirstField ? 'repeater-leading-field' : '' }}"
                            name="{{ $subFieldName }}"
                            data-name-template="{{ $subFieldNameTemplate }}"
                            value="{{ $subFieldValue }}"
                        >
                    @endif
                    @if($subFieldHelp !== '')
                        <div class="form-text">{{ $subFieldHelp }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
