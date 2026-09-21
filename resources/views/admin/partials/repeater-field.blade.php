@php
    $repeaterFields = collect((array) ($field['fields'] ?? []))
        ->filter(static fn ($subField): bool => is_array($subField) && trim((string) ($subField['key'] ?? '')) !== '')
        ->values()
        ->all();

    $rows = collect(is_array($rawFieldValue ?? null) ? $rawFieldValue : [])
        ->filter(static fn ($row): bool => is_array($row))
        ->values()
        ->all();

    if ($rows === []) {
        $rows = [[]];
    }

    $addButtonLabel = trim((string) ($field['add_button_label'] ?? __('Add item')));
@endphp

<div class="repeater-field" data-repeater-field>
    <div class="repeater-items d-flex flex-column gap-3">
        @foreach($rows as $rowIndex => $rowData)
            @include('admin.partials.repeater-field-item', [
                'repeaterFields' => $repeaterFields,
                'fieldNamePrefix' => $fieldNamePrefix,
                'rowIndex' => $rowIndex,
                'rowData' => is_array($rowData) ? $rowData : [],
                'isTemplate' => false,
            ])
        @endforeach
    </div>

    <template data-repeater-template>
        @include('admin.partials.repeater-field-item', [
            'repeaterFields' => $repeaterFields,
            'fieldNamePrefix' => $fieldNamePrefix,
            'rowIndex' => 0,
            'rowData' => [],
            'isTemplate' => true,
        ])
    </template>

    <button type="button" class="btn btn-outline-secondary btn-sm add-repeater-item mt-3">
        <i class="bi bi-plus-lg me-1"></i>{{ $addButtonLabel }}
    </button>
</div>

@once
    @push('styles')
    <style>
        [data-repeater-value-display] {
            font-weight: 600;
            color: var(--bs-body-color);
            background-color: rgba(var(--bs-primary-rgb), 0.1);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            display: inline-block;
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
    @endpush
@endonce
