@extends('admin.layouts.app')

@section('title', __('Languages'))
@section('page_title', __('Languages'))

@section('content')
    <div class="row align-items-center mb-4">
        <div class="col-lg-8">
            <h2 class="welcome-title mb-1">{{ __('Languages Management') }}</h2>
            <p class="text-muted mb-0">{{ __('Manage system languages and regional settings.') }}</p>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0 d-flex justify-content-lg-end gap-2 flex-wrap">
            <span class="badge rounded-pill bg-light-soft text-primary border px-3 py-2 align-self-center">
                {{ __('Total') }}: {{ $languages->total() }}
            </span>
            <a href="{{ route('admin.languages.create') }}" class="btn btn-primary px-4">
                <i class="bi bi-plus-lg me-1"></i>{{ __('Add New Language') }}
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

    <div class="dashboard-panel premium-shadow mb-4">
        <div class="panel-body">
            <form method="GET" action="{{ route('admin.languages.index') }}" class="row g-3 align-items-end">
                <div class="col-lg-5">
                    <label for="q" class="form-label small fw-bold text-muted uppercase letter-spacing-1">{{ __('Search') }}</label>
                    <input type="search" class="form-control" id="q" name="q" value="{{ $search }}" placeholder="{{ __('Search by language name or code...') }}">
                </div>
                <div class="col-lg-3">
                    <label for="filter" class="form-label small fw-bold text-muted uppercase letter-spacing-1">{{ __('Filter') }}</label>
                    <select class="form-select" id="filter" name="filter">
                        <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>{{ __('All') }}</option>
                        <option value="active" {{ $filter === 'active' ? 'selected' : '' }}>{{ __('Active Only') }}</option>
                        <option value="inactive" {{ $filter === 'inactive' ? 'selected' : '' }}>{{ __('Inactive Only') }}</option>
                    </select>
                </div>
                <div class="col-lg-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4">{{ __('Apply') }}</button>
                    <a href="{{ route('admin.languages.index') }}" class="btn btn-light-soft px-4">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dashboard-panel premium-shadow mb-4 overflow-hidden">
        <div class="panel-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="panel-header-title">
                <i class="bi bi-translate me-2 text-primary"></i>
                <span>{{ __('Languages') }}</span>
            </div>
            <form id="bulkActionForm" method="POST" action="{{ route('admin.languages.bulk-action') }}" class="d-flex align-items-center gap-2">
                @csrf
                <select name="action" class="form-select form-select-sm" style="min-width: 180px;">
                    <option value="activate">{{ __('Activate Selected') }}</option>
                    <option value="deactivate">{{ __('Deactivate Selected') }}</option>
                </select>
                <button type="submit" class="btn btn-sm btn-primary" id="bulkApplyBtn" disabled>{{ __('Bulk Apply') }}</button>
            </form>
        </div>

        <div class="panel-body p-0">
            <div class="table-responsive" data-mobile-columns="true">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light-soft">
                        <tr>
                            <th class="ps-4 py-3">
                                <input type="checkbox" id="selectAllLanguages" class="form-check-input">
                            </th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Language') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Code') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1 table-col-secondary">{{ __('Direction') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Status') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Default') }}</th>
                            <th class="pe-4 py-3 text-muted small fw-bold uppercase letter-spacing-1 text-end table-col-secondary">{{ __('Sort Order') }}</th>
                            <th class="pe-4 py-3 text-muted small fw-bold uppercase letter-spacing-1 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($languages as $language)
                            <tr>
                                <td class="ps-4 py-3">
                                    <input
                                        type="checkbox"
                                        class="form-check-input language-checkbox"
                                        value="{{ $language->id }}"
                                        form="bulkActionForm"
                                        name="selected_ids[]"
                                    >
                                </td>
                                <td class="py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="fi fi-{{ strtolower($language->country_code) }} language-flag"></span>
                                        <div>
                                            <div class="fw-semibold">{{ $language->name }}</div>
                                            <div class="text-muted small">{{ $language->english_name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <span class="badge bg-light-soft text-primary border">{{ strtoupper($language->code) }}</span>
                                </td>
                                <td class="py-3 table-col-secondary">
                                    <span class="badge {{ $language->direction === 'rtl' ? 'bg-warning-soft text-warning border' : 'bg-light-soft text-muted border' }}">
                                        {{ strtoupper($language->direction) }}
                                    </span>
                                </td>
                                <td class="py-3">
                                    <form method="POST" action="{{ route('admin.languages.toggle-active', $language) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="active" value="{{ $language->is_active ? 0 : 1 }}">
                                        <button type="submit" class="btn btn-sm {{ $language->is_active ? 'btn-success' : 'btn-outline-secondary' }}">
                                            {{ $language->is_active ? __('Active') : __('Inactive') }}
                                        </button>
                                    </form>
                                </td>
                                <td class="py-3">
                                    <form method="POST" action="{{ route('admin.languages.set-default', $language) }}" class="default-language-form mb-0">
                                        @csrf
                                        @method('PATCH')
                                        <label class="form-check d-inline-flex align-items-center gap-2 mb-0">
                                            <input
                                                class="form-check-input default-language-radio"
                                                type="radio"
                                                name="default_language"
                                                value="{{ $language->id }}"
                                                {{ $language->is_default ? 'checked' : '' }}
                                                {{ $language->is_default ? 'disabled' : '' }}
                                            >
                                            <span class="small {{ $language->is_default ? 'text-primary fw-semibold' : 'text-muted' }}">{{ __('Default') }}</span>
                                        </label>
                                    </form>
                                </td>
                                <td class="pe-4 py-3 text-end table-col-secondary">
                                    <span class="badge bg-light-soft text-muted border px-3">{{ $language->sort_order }}</span>
                                </td>
                                <td class="pe-4 py-3 text-end">
                                    <div class="d-inline-flex gap-2">
                                        <a href="{{ route('admin.languages.edit', $language) }}" class="btn btn-sm btn-light-soft">
                                            <i class="bi bi-pencil me-1"></i>{{ __('Edit') }}
                                        </a>
                                        @if(! $language->is_default)
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger delete-language-btn"
                                                data-delete-action="{{ route('admin.languages.destroy', $language) }}"
                                                data-language-name="{{ $language->english_name }}"
                                            >
                                                <i class="bi bi-trash me-1"></i>{{ __('Delete') }}
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-5 text-center text-muted">
                                    <i class="bi bi-translate h1 d-block mb-3 opacity-25"></i>
                                    {{ __('No languages found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($languages->hasPages())
            <div class="panel-body border-top">
                {{ $languages->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

    <div class="dashboard-panel premium-shadow">
        <div class="panel-header d-flex justify-content-between align-items-center gap-3">
            <div class="panel-header-title">
                <i class="bi bi-arrow-down-up me-2 text-primary"></i>
                <span>{{ __('Active Languages Sort Order') }}</span>
            </div>
            <small class="text-muted">{{ __('Drag and drop rows, then save.') }}</small>
        </div>
        <div class="panel-body">
            <form method="POST" action="{{ route('admin.languages.sort-order') }}" id="sortOrderForm">
                @csrf
                <ul class="sortable-language-list" id="sortableLanguages">
                    @foreach($activeLanguages as $activeLanguage)
                        <li class="sortable-item" draggable="true" data-id="{{ $activeLanguage->id }}">
                            <div class="d-flex align-items-center gap-3">
                                <span class="drag-handle"><i class="bi bi-grip-vertical"></i></span>
                                <span class="fi fi-{{ strtolower($activeLanguage->country_code) }} language-flag"></span>
                                <div>
                                    <div class="fw-semibold">{{ $activeLanguage->name }}</div>
                                    <div class="small text-muted">{{ $activeLanguage->english_name }} ({{ strtoupper($activeLanguage->code) }})</div>
                                </div>
                            </div>
                            <span class="badge bg-light-soft text-muted border">{{ $activeLanguage->sort_order }}</span>
                        </li>
                    @endforeach
                </ul>
                <div id="orderedIdsContainer"></div>

                <div class="mt-3 text-end">
                    <button type="submit" class="btn btn-primary px-4">{{ __('Save Active Order') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="deleteLanguageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Delete Language') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">
                        {{ __('Are you sure you want to delete') }}
                        <strong id="deleteLanguageName"></strong>?
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-soft" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <form id="deleteLanguageForm" method="POST" action="">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">{{ __('Delete') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
.language-flag {
    width: 1.35rem;
    height: 1rem;
    border-radius: 2px;
    box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.08);
}

.sortable-language-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    gap: 10px;
}

.sortable-item {
    border: 1px solid var(--admin-border);
    border-radius: 12px;
    background: var(--admin-surface-2);
    padding: 12px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: grab;
}

.sortable-item.dragging {
    opacity: 0.65;
    border-style: dashed;
}

.drag-handle {
    color: var(--admin-muted);
    font-size: 1.1rem;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const selectAll = document.getElementById('selectAllLanguages');
    const checkboxes = Array.from(document.querySelectorAll('.language-checkbox'));
    const bulkApplyBtn = document.getElementById('bulkApplyBtn');

    const updateBulkButtonState = () => {
        const checkedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
        bulkApplyBtn.disabled = checkedCount === 0;
    };

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach((checkbox) => {
                checkbox.checked = this.checked;
            });
            updateBulkButtonState();
        });
    }

    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', updateBulkButtonState);
    });

    updateBulkButtonState();

    document.querySelectorAll('.default-language-radio:not(:disabled)').forEach((radio) => {
        radio.addEventListener('change', () => {
            radio.closest('form')?.submit();
        });
    });

    const sortableList = document.getElementById('sortableLanguages');
    const sortOrderForm = document.getElementById('sortOrderForm');
    const orderedIdsContainer = document.getElementById('orderedIdsContainer');
    const deleteLanguageModalEl = document.getElementById('deleteLanguageModal');
    const deleteLanguageForm = document.getElementById('deleteLanguageForm');
    const deleteLanguageName = document.getElementById('deleteLanguageName');

    if (!sortableList || !sortOrderForm || !orderedIdsContainer) {
        // Continue to keep delete modal wiring active even if sortable section is not present.
    }

    if (deleteLanguageModalEl && deleteLanguageForm && deleteLanguageName && window.bootstrap) {
        const deleteModal = new bootstrap.Modal(deleteLanguageModalEl);
        document.querySelectorAll('.delete-language-btn').forEach((button) => {
            button.addEventListener('click', () => {
                deleteLanguageForm.setAttribute('action', button.dataset.deleteAction || '');
                deleteLanguageName.textContent = button.dataset.languageName || '';
                deleteModal.show();
            });
        });
    }

    if (!sortableList || !sortOrderForm || !orderedIdsContainer) {
        return;
    }

    let draggedItem = null;

    const items = () => Array.from(sortableList.querySelectorAll('.sortable-item'));

    items().forEach((item) => {
        item.addEventListener('dragstart', () => {
            draggedItem = item;
            item.classList.add('dragging');
        });

        item.addEventListener('dragend', () => {
            item.classList.remove('dragging');
            draggedItem = null;
        });

        item.addEventListener('dragover', (event) => {
            event.preventDefault();
        });

        item.addEventListener('drop', (event) => {
            event.preventDefault();
            if (!draggedItem || draggedItem === item) {
                return;
            }

            const rect = item.getBoundingClientRect();
            const shouldInsertAfter = event.clientY > rect.top + (rect.height / 2);

            if (shouldInsertAfter) {
                item.after(draggedItem);
            } else {
                item.before(draggedItem);
            }
        });
    });

    sortOrderForm.addEventListener('submit', () => {
        orderedIdsContainer.innerHTML = '';

        items().forEach((item) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ordered_ids[]';
            input.value = item.dataset.id;
            orderedIdsContainer.appendChild(input);
        });
    });
});
</script>
@endpush
