@extends('admin.layouts.app')

@section('title', __('Media Library'))
@section('page_title', __('Media Library'))

@php
    $typeIcons = [
        'image' => 'bi-image',
        'video' => 'bi-camera-video',
        'audio' => 'bi-music-note-beamed',
        'document' => 'bi-file-earmark-text',
        'archive' => 'bi-file-earmark-zip',
        'other' => 'bi-file-earmark',
    ];
@endphp

@section('content')
    <div class="media-library-wrapper">
        <!-- Sidebar -->
        <aside class="media-sidebar">
            <div class="sidebar-section">
                <button class="btn btn-primary w-100 mb-4 py-2 shadow-sm rounded-3" data-bs-toggle="modal"
                    data-bs-target="#uploadModal">
                    <i class="bi bi-cloud-arrow-up me-2"></i>{{ __('Upload') }}
                </button>

                <h6 class="sidebar-title uppercase letter-spacing-1 small text-muted mb-3">{{ __('Library') }}</h6>
                <nav class="media-nav">
                    <a href="{{ route('admin.media.index', ['type' => 'all']) }}"
                        class="media-nav-link {{ $type === 'all' && !$currentFolder ? 'active' : '' }}">
                        <i class="bi bi-grid-1x2"></i>
                        <span>{{ __('All Files') }}</span>
                    </a>
                    <a href="{{ route('admin.media.index', ['type' => 'image']) }}"
                        class="media-nav-link {{ $type === 'image' ? 'active' : '' }}">
                        <i class="bi bi-image"></i>
                        <span>{{ __('Images') }}</span>
                    </a>
                    <a href="{{ route('admin.media.index', ['type' => 'video']) }}"
                        class="media-nav-link {{ $type === 'video' ? 'active' : '' }}">
                        <i class="bi bi-camera-video"></i>
                        <span>{{ __('Videos') }}</span>
                    </a>
                    <a href="{{ route('admin.media.index', ['status' => 'trashed']) }}"
                        class="media-nav-link {{ $status === 'trashed' ? 'active' : '' }}">
                        <i class="bi bi-trash3"></i>
                        <span>{{ __('Trash') }}</span>
                    </a>
                </nav>
            </div>

            <div class="sidebar-section mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="sidebar-title uppercase letter-spacing-1 small text-muted mb-0">{{ __('Folders') }}</h6>
                    <button class="btn btn-link p-0 text-primary" data-bs-toggle="modal"
                        data-bs-target="#createFolderModal">
                        <i class="bi bi-plus-circle"></i>
                    </button>
                </div>
                <div class="folder-tree">
                    @forelse($folders as $folder)
                        @include('admin.media.partials.folder-item', [
                            'folder' => $folder,
                            'currentFolder' => $currentFolder,
                        ])
                    @empty
                        <p class="text-muted small ps-3">{{ __('No folders created') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="sidebar-section mt-auto pt-4 border-top">
                <div class="storage-info p-3 rounded-3 bg-light-soft">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="fw-bold">{{ __('Storage') }}</span>
                        <span>85%</span>
                    </div>
                    <div class="progress mb-2" style="height: 6px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 85%"></div>
                    </div>
                    <p class="text-muted small mb-0">{{ __('8.5 GB of 10 GB used') }}</p>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="media-main">
            <header class="media-main-header">
                <div class="media-search-wrap">
                    <form action="{{ route('admin.media.index') }}" method="GET" class="w-100">
                        @if ($currentFolder)
                            <input type="hidden" name="folder_id" value="{{ $currentFolder->id }}">
                        @endif
                        @if ($type !== 'all')
                            <input type="hidden" name="type" value="{{ $type }}">
                        @endif
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-0 pe-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="search" name="q" value="{{ $search }}"
                                class="form-control border-0 shadow-none"
                                placeholder="{{ __('Search in :name...', ['name' => $currentFolder ? $currentFolder->name : __('All Files')]) }}">
                        </div>
                    </form>
                </div>
                <div class="header-actions">
                    <nav aria-label="breadcrumb" class="d-none d-md-block me-3">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.media.index') }}">{{ __('Media') }}</a>
                            </li>
                            @foreach ($breadcrumbs as $bc)
                                <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}">
                                    @if (!$loop->last)
                                        <a
                                            href="{{ route('admin.media.index', ['folder_id' => $bc['id']]) }}">{{ $bc['name'] }}</a>
                                    @else
                                        {{ $bc['name'] }}
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                    <div class="btn-group border rounded-3 p-1 bg-light-soft">
                        <button
                            class="btn btn-sm btn-light border-0 px-3 {{ $currentView === 'grid' ? 'active shadow-sm' : 'bg-transparent text-muted' }}"
                            data-view="grid">
                            <i class="bi bi-grid-fill"></i>
                        </button>
                        <button
                            class="btn btn-sm btn-light border-0 px-3 {{ $currentView === 'list' ? 'active shadow-sm' : 'bg-transparent text-muted' }}"
                            data-view="list">
                            <i class="bi bi-list"></i>
                        </button>
                    </div>
                </div>
            </header>

            <div class="media-grid-container scrollbar-hidden">
                @if (session('success'))
                    <div class="alert alert-success border-0 shadow-sm rounded-3 mx-4 mb-4">{{ session('success') }}</div>
                @endif

                @if ($media->count() > 0)
                    <div class="d-flex align-items-center gap-2 px-4 pt-3 pb-1">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="selectAllCheckbox" title="{{ __('Select All') }}">
                            <label class="form-check-label small text-muted" for="selectAllCheckbox">{{ __('Select All') }}</label>
                        </div>
                        <span class="text-muted small ms-1">({{ $media->count() }} {{ __('on this page') }})</span>
                    </div>
                @endif

                <div class="media-grid" id="mediaGrid">
                    @forelse($media as $item)
                        <article class="media-item-card" data-id="{{ $item->id }}" data-type="{{ $item->type }}">
                            <div class="media-item-checkbox">
                                <input type="checkbox" class="form-check-input media-checkbox" value="{{ $item->id }}">
                            </div>
                            <div class="media-item-preview">
                                @if ($item->isImage())
                                    <img src="{{ $item->thumbnail_url }}" alt="{{ $item->filename }}" loading="lazy">
                                @else
                                    <div class="file-icon">
                                        <i class="bi {{ $typeIcons[$item->type] ?? 'bi-file-earmark' }}"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="media-item-info">
                                <span class="media-item-name" title="{{ $item->filename }}">{{ $item->filename }}</span>
                                <span class="media-item-meta">{{ $item->human_readable_size }} •
                                    {{ strtoupper($item->type) }}</span>
                            </div>
                        </article>
                    @empty
                        <div class="media-empty">
                            <i class="bi bi-images mb-3"></i>
                            <h4>{{ __('No items found') }}</h4>
                            <p class="text-muted">{{ __('Try adjusting your search or filters') }}</p>
                            <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                <i class="bi bi-cloud-arrow-up me-2"></i>{{ __('Upload Files') }}
                            </button>
                        </div>
                    @endforelse
                </div>

                @if ($media->hasPages())
                    <div class="media-pagination p-4 d-flex justify-content-center">
                        {{ $media->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </main>

        <!-- Right Side Panel -->
        <aside class="media-details-panel scrollbar-hidden" id="mediaDetailsPanel">
            <div class="details-placeholder text-center p-5">
                <i class="bi bi-info-circle display-4 text-muted opacity-25"></i>
                <p class="mt-3 text-muted">{{ __('Select an item to view details') }}</p>
            </div>

            <div class="details-content d-none">
                <div class="details-header">
                    <h5 class="mb-0">{{ __('Asset Details') }}</h5>
                    <button class="btn-close" id="closeDetails"></button>
                </div>
                <div class="details-preview" id="detailsPreview"></div>
                <div class="details-body">
                    <form id="mediaUpdateForm">
                        @csrf
                        @method('PATCH')
                        <div class="detail-group">
                            <label class="detail-label">{{ __('File Name') }}</label>
                            <div class="detail-value text-break" id="detailsFilename"></div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="detail-label">{{ __('Size') }}</label>
                                <div class="detail-value" id="detailsSize"></div>
                            </div>
                            <div class="col-6">
                                <label class="detail-label">{{ __('Type') }}</label>
                                <div class="detail-value text-uppercase" id="detailsType"></div>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="detail-label">{{ __('Dimensions') }}</label>
                                <div class="detail-value" id="detailsDimensions"></div>
                            </div>
                            <div class="col-6">
                                <label class="detail-label">{{ __('Usage Count') }}</label>
                                <div class="detail-value" id="detailsUsage"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">{{ __('Title') }}</label>
                            <input type="text" name="title" class="form-control form-control-sm" id="editTitle">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">{{ __('Alt Text') }}</label>
                            <input type="text" name="alt_text" class="form-control form-control-sm" id="editAlt">
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold">{{ __('Description') }}</label>
                            <textarea name="description" class="form-control form-control-sm editor-field" rows="3" id="editDescription"></textarea>
                        </div>

                        <div class="detail-actions">
                            @if ($status !== 'trashed')
                                <button type="submit" class="btn btn-primary btn-sm w-100 mb-2">
                                    <i class="bi bi-save me-1"></i>{{ __('Save Changes') }}
                                </button>
                            @endif
                            <a href="#" id="downloadBtn" class="btn btn-outline-secondary btn-sm w-100 mb-2">
                                <i class="bi bi-download me-1"></i>{{ __('Download') }}
                            </a>
                            @if ($status === 'trashed')
                                <button type="button" id="restoreBtn" class="btn btn-outline-success btn-sm w-100 mb-2">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>{{ __('Restore') }}
                                </button>
                                <button type="button" id="forceDeleteBtn" class="btn btn-danger btn-sm w-100">
                                    <i class="bi bi-trash3-fill me-1"></i>{{ __('Delete Permanently') }}
                                </button>
                            @else
                                <button type="button" id="trashBtn" class="btn btn-outline-danger btn-sm w-100">
                                    <i class="bi bi-trash3 me-1"></i>{{ __('Move to Trash') }}
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </aside>
    </div>

    <!-- Modals -->
    @include('admin.media.partials.modals')

    <!-- Bulk Action Toolbar -->
    <div class="bulk-toolbar shadow-lg border rounded-4 bg-dark text-white p-3" id="bulkToolbar">
        <div class="d-flex align-items-center gap-3">
            <span class="selected-count fw-bold border-end pe-3 me-1">0 Selected</span>
            <div class="bulk-actions d-flex gap-2">
                @if ($status === 'trashed')
                    <button class="btn btn-sm btn-outline-success border-0" id="bulkRestore">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>{{ __('Restore') }}
                    </button>
                    <button class="btn btn-sm btn-danger border-0" id="bulkForceDelete">
                        <i class="bi bi-trash3-fill me-1"></i>{{ __('Delete Permanently') }}
                    </button>
                @else
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-light border-0 dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-folder-symlink me-1"></i>{{ __('Move') }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item" href="#" data-folder-id="">{{ __('Root') }}</a></li>
                            @foreach ($flatFolders as $folder)
                                <li><a class="dropdown-item" href="#"
                                        data-folder-id="{{ $folder->id }}">{{ $folder->path ?: $folder->name }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    <button class="btn btn-sm btn-outline-danger border-0" id="bulkDelete">
                        <i class="bi bi-trash3 me-1"></i>{{ __('Delete') }}
                    </button>
                @endif
            </div>
            <button class="btn-close btn-close-white ms-auto" id="closeBulk"></button>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/media.css') }}">
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const grid = document.getElementById('mediaGrid');
            const detailsPanel = document.getElementById('mediaDetailsPanel');
            const bulkToolbar = document.getElementById('bulkToolbar');
            const selectedCountSpan = bulkToolbar.querySelector('.selected-count');
            const detailsContent = detailsPanel.querySelector('.details-content');
            const detailsPlaceholder = detailsPanel.querySelector('.details-placeholder');

            let selectedIds = [];
            const isTrashView = {{ $status === 'trashed' ? 'true' : 'false' }};

            // Select All checkbox
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', () => {
                    const cards = grid.querySelectorAll('.media-item-card');
                    if (selectAllCheckbox.checked) {
                        selectedIds = [];
                        cards.forEach(card => {
                            const id = card.dataset.id;
                            if (!selectedIds.includes(id)) selectedIds.push(id);
                            card.classList.add('selected');
                            card.querySelector('.media-checkbox').checked = true;
                        });
                    } else {
                        clearSelection();
                    }
                    updateBulkToolbar();
                });
            }

            // Keep select-all in sync when individual items are toggled
            function syncSelectAll() {
                if (!selectAllCheckbox) return;
                const total = grid.querySelectorAll('.media-item-card').length;
                selectAllCheckbox.indeterminate = selectedIds.length > 0 && selectedIds.length < total;
                selectAllCheckbox.checked = selectedIds.length > 0 && selectedIds.length === total;
            }

            // Select Item logic
            grid.addEventListener('click', (e) => {
                const card = e.target.closest('.media-item-card');
                if (!card) return;

                if (e.target.classList.contains('media-checkbox'))
                    return; // Let the checkbox change event handle it

                const id = card.dataset.id;

                // Multi-select with Ctrl or Meta
                if (e.ctrlKey || e.metaKey) {
                    toggleSelection(id, card);
                } else {
                    // Single select: show details
                    clearSelection();
                    selectOne(id, card);
                }
            });

            // Checkbox logic
            grid.addEventListener('change', (e) => {
                if (e.target.classList.contains('media-checkbox')) {
                    const card = e.target.closest('.media-item-card');
                    toggleSelection(e.target.value, card);
                }
            });

            function selectOne(id, card) {
                card.classList.add('selected');
                selectedIds = [id];
                showDetails(id);
                updateBulkToolbar();
            }

            function toggleSelection(id, card) {
                if (selectedIds.includes(id)) {
                    selectedIds = selectedIds.filter(i => i !== id);
                    card.classList.remove('selected');
                    card.querySelector('.media-checkbox').checked = false;
                } else {
                    selectedIds.push(id);
                    card.classList.add('selected');
                    card.querySelector('.media-checkbox').checked = true;
                }

                if (selectedIds.length === 1) {
                    showDetails(selectedIds[0]);
                } else {
                    hideDetails();
                }
                syncSelectAll();
                updateBulkToolbar();
            }

            function clearSelection() {
                selectedIds = [];
                grid.querySelectorAll('.media-item-card').forEach(c => {
                    c.classList.remove('selected');
                    c.querySelector('.media-checkbox').checked = false;
                });
                if (selectAllCheckbox) { selectAllCheckbox.checked = false; selectAllCheckbox.indeterminate = false; }
                updateBulkToolbar();
            }

            function updateBulkToolbar() {
                if (selectedIds.length > 0) {
                    selectedCountSpan.textContent =
                        `${selectedIds.length} ${selectedIds.length === 1 ? 'Item' : 'Items'} Selected`;
                    bulkToolbar.classList.add('visible');
                } else {
                    bulkToolbar.classList.remove('visible');
                }
            }

            async function showDetails(id) {
                detailsPlaceholder.classList.add('d-none');
                detailsContent.classList.remove('d-none');
                if (window.innerWidth <= 1200) detailsPanel.classList.add('open');

                // Loading state
                detailsContent.style.opacity = '0.5';

                try {
                    const response = await fetch(`/media/${id}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const data = await response.json();
                    const media = data.media;

                    const locale = '{{ app()->getLocale() }}';
                    const getTranslation = (field) => {
                        if (!field) return '';
                        if (typeof field === 'string') return field;
                        return field[locale] || Object.values(field)[0] || '';
                    };

                    // Fill details
                    document.getElementById('detailsFilename').textContent = media.filename;
                    document.getElementById('detailsSize').textContent = media.human_readable_size;
                    document.getElementById('detailsType').textContent = (media.type || 'unknown');
                    document.getElementById('detailsUsage').textContent = media.usage_count || 0;
                    document.getElementById('detailsDimensions').textContent = (media.width && media.height) ?
                        `${media.width}x${media.height}` : 'N/A';
                    document.getElementById('editTitle').value = getTranslation(media.title);
                    document.getElementById('editAlt').value = getTranslation(media.alt_text);
                    const descriptionValue = getTranslation(media.description);
                    window.AdminQuill?.setValue(document.getElementById('editDescription'), descriptionValue);
                    document.getElementById('downloadBtn').href = media.download_url;

                    // Set action for update form
                    document.getElementById('mediaUpdateForm').action = `/media/${media.id}`;

                    // Preview
                    const previewEl = document.getElementById('detailsPreview');
                    if (media.type === 'image') {
                        previewEl.innerHTML = `<img src="${media.thumbnail_url}" alt="Preview">`;
                    } else {
                        const iconCard = grid.querySelector(`[data-id="${id}"] .bi`);
                        const icon = iconCard ? iconCard.className : 'bi bi-file-earmark';
                        previewEl.innerHTML = `<div class="file-icon"><i class="${icon}"></i></div>`;
                    }

                    detailsContent.style.opacity = '1';
                } catch (error) {
                    console.error('Failed to load media details', error);
                }
            }

            // Trash individual item
            const trashBtn = document.getElementById('trashBtn');
            if (trashBtn) {
                trashBtn.addEventListener('click', async () => {
                    const url = document.getElementById('mediaUpdateForm').action;
                    if (!confirm('{{ __('Move this item to trash?') }}')) return;
                    try {
                        const response = await fetch(url, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const result = await response.json();
                        if (result.success) location.reload();
                    } catch (error) { console.error('Trash failed', error); }
                });
            }

            // Restore individual item (trash view)
            const restoreBtn = document.getElementById('restoreBtn');
            if (restoreBtn) {
                restoreBtn.addEventListener('click', async () => {
                    const form = document.getElementById('mediaUpdateForm');
                    const id = form.action.match(/\/(\d+)$/)?.[1];
                    if (!id || !confirm('{{ __('Restore this item?') }}')) return;
                    try {
                        const response = await fetch(`/media/${id}/restore`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const result = await response.json();
                        if (result.success) location.reload();
                    } catch (error) { console.error('Restore failed', error); }
                });
            }

            // Bulk Restore (trash view)
            const bulkRestoreBtn = document.getElementById('bulkRestore');
            if (bulkRestoreBtn) {
                bulkRestoreBtn.addEventListener('click', async () => {
                    if (!confirm('{{ __('Restore selected items?') }}')) return;
                    try {
                        const response = await fetch('/media/bulk-action', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ action: 'restore', ids: selectedIds })
                        });
                        const result = await response.json();
                        if (result.success) location.reload();
                    } catch (error) { console.error('Bulk restore failed', error); }
                });
            }

            // Individual Force Delete (trash view)
            const forceDeleteBtn = document.getElementById('forceDeleteBtn');
            if (forceDeleteBtn) {
                forceDeleteBtn.addEventListener('click', async () => {
                    const form = document.getElementById('mediaUpdateForm');
                    const id = form.action.match(/\/(\d+)$/)?.[1];
                    if (!id || !confirm('{{ __('This will permanently delete the file from storage. This cannot be undone. Continue?') }}')) return;
                    try {
                        const response = await fetch(`/media/${id}/force`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const result = await response.json();
                        if (result.success) location.reload();
                    } catch (error) { console.error('Force delete failed', error); }
                });
            }

            // Bulk Force Delete (trash view)
            const bulkForceDeleteBtn = document.getElementById('bulkForceDelete');
            if (bulkForceDeleteBtn) {
                bulkForceDeleteBtn.addEventListener('click', async () => {
                    if (!confirm(`{{ __('Permanently delete') }} ${selectedIds.length} {{ __('items? Files will be removed from storage and cannot be recovered.') }}`)) return;
                    try {
                        const response = await fetch('/media/bulk-action', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ action: 'force_delete', ids: selectedIds })
                        });
                        const result = await response.json();
                        if (result.success) location.reload();
                    } catch (error) { console.error('Bulk force delete failed', error); }
                });
            }

            function hideDetails() {
                detailsContent.classList.add('d-none');
                detailsPlaceholder.classList.remove('d-none');
                if (window.innerWidth <= 1200) detailsPanel.classList.remove('open');
            }

            document.getElementById('closeDetails').addEventListener('click', () => {
                hideDetails();
                clearSelection();
            });

            document.getElementById('closeBulk').addEventListener('click', clearSelection);

            // Update Form Submit
            document.getElementById('mediaUpdateForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const form = e.target;
                const formData = new FormData(form);

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const result = await response.json();

                    if (result.success) {
                        // Update grid filename if needed (simplistic update)
                        const cardTitle = grid.querySelector(
                            `[data-id="${result.media.id}"] .media-item-name`);
                        if (cardTitle) cardTitle.textContent = result.media.filename;

                        // Show toast or something
                        alert('{{ __('Media updated successfully') }}');
                    }
                } catch (error) {
                    console.error('Update failed', error);
                }
            });

            // Bulk Delete
            document.getElementById('bulkDelete').addEventListener('click', async () => {
                if (!confirm('{{ __('Move selected items to trash?') }}')) return;

                try {
                    const response = await fetch('/media/bulk-action', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            ids: selectedIds
                        })
                    });
                    const result = await response.json();
                    if (result.success) {
                        location.reload();
                    }
                } catch (error) {
                    console.error('Bulk delete failed', error);
                }
            });

            // Bulk Move
            document.querySelectorAll('.bulk-actions .dropdown-item').forEach(item => {
                item.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const folderId = e.target.dataset.folderId;

                    try {
                        const response = await fetch('/media/bulk-action', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                action: 'move',
                                ids: selectedIds,
                                folder_id: folderId
                            })
                        });
                        const result = await response.json();
                        if (result.success) {
                            location.reload();
                        }
                    } catch (error) {
                        console.error('Bulk move failed', error);
                    }
                });
            });

            // View Toggles
            document.querySelectorAll('[data-view]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const view = btn.dataset.view;
                    const url = new URL(window.location);
                    url.searchParams.set('view', view);
                    window.location = url;
                });
            });
        });
    </script>
@endpush
