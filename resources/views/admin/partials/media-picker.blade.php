<div class="modal fade" id="mediaPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Media Library') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">{{ __('Folder') }}</label>
                        <select id="mediaPickerFolder" class="form-select form-select-sm">
                            <option value="">{{ __('All folders') }}</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-muted">{{ __('Search') }}</label>
                        <input id="mediaPickerSearch" type="search" class="form-control form-control-sm"
                            placeholder="{{ __('Search media...') }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary w-100"
                            id="mediaPickerSearchBtn">{{ __('Search') }}</button>
                        <button type="button" class="btn btn-sm btn-outline-primary w-100"
                            id="mediaPickerReloadBtn">{{ __('Reload') }}</button>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-7">
                        <label class="form-label small fw-bold text-muted">{{ __('Upload Files') }}</label>
                        <input id="mediaPickerUploadInput" type="file" class="form-control form-control-sm" multiple>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-primary w-100"
                            id="mediaPickerUploadBtn">{{ __('Upload') }}</button>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">{{ __('New Folder') }}</label>
                        <div class="input-group input-group-sm">
                            <input id="mediaPickerNewFolderName" type="text" class="form-control"
                                placeholder="{{ __('Folder name') }}">
                            <button type="button" class="btn btn-outline-secondary"
                                id="mediaPickerCreateFolderBtn">{{ __('Create') }}</button>
                        </div>
                    </div>
                </div>

                <div id="mediaPickerFeedback" class="small text-muted mb-2"></div>
                <div class="row g-2" id="mediaPickerGrid"></div>
                <div class="text-center mt-3" id="mediaPickerLoadMoreWrap" style="display:none!important;">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-4" id="mediaPickerLoadMoreBtn">
                        {{ __('Load More') }}
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modalEl = document.getElementById('mediaPickerModal');
            if (!modalEl) {
                return;
            }

            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            const folderSelect = document.getElementById('mediaPickerFolder');
            const searchInput = document.getElementById('mediaPickerSearch');
            const searchBtn = document.getElementById('mediaPickerSearchBtn');
            const reloadBtn = document.getElementById('mediaPickerReloadBtn');
            const uploadInput = document.getElementById('mediaPickerUploadInput');
            const uploadBtn = document.getElementById('mediaPickerUploadBtn');
            const createFolderInput = document.getElementById('mediaPickerNewFolderName');
            const createFolderBtn = document.getElementById('mediaPickerCreateFolderBtn');
            const grid = document.getElementById('mediaPickerGrid');
            const feedback = document.getElementById('mediaPickerFeedback');

            const browseUrl = @json(route('admin.media.browse'));
            const uploadUrl = @json(route('admin.media.upload'));
            const foldersUrl = @json(route('admin.media-folders.index'));
            const createFolderUrl = @json(route('admin.media-folders.store'));

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                document.querySelector('input[name="_token"]')?.value ||
                '';

            const loadMoreWrap = document.getElementById('mediaPickerLoadMoreWrap');
            const loadMoreBtn = document.getElementById('mediaPickerLoadMoreBtn');

            let targetField = null;
            let mode = 'image';
            let sourceContext = 'general';
            let currentPage = 1;
            let lastPage = 1;
            let selectionCallback = null;

            const setFeedback = (message, isError = false) => {
                feedback.textContent = message || '';
                feedback.className = isError
                    ? 'small mb-2 text-danger fw-semibold'
                    : 'small text-muted mb-2';
            };

            const inferSourceContext = (trigger) => {
                const explicit = trigger?.dataset?.sourceContext;
                if (explicit && explicit.trim() !== '') {
                    return explicit.trim();
                }

                const path = window.location.pathname || '';
                let base = 'general';
                if (path.includes('/pages')) {
                    base = 'pages';
                } else if (path.includes('/posts')) {
                    base = 'posts';
                } else if (path.includes('/blocks')) {
                    base = 'blocks';
                } else if (path.includes('/menus')) {
                    base = 'menus';
                }

                const blockCard = trigger?.closest('.page-block-card');
                const blockKey = blockCard?.dataset?.blockKey || '';
                if (blockKey !== '') {
                    base += `/blocks/${blockKey}`;
                }

                const localePane = trigger?.closest('[data-locale-code]');
                const localeCode = localePane?.dataset?.localeCode || '';
                if (localeCode !== '') {
                    base += `/locale/${localeCode}`;
                }

                return base;
            };

            const flattenFolders = (folders, level = 0) => {
                return folders.flatMap((folder) => {
                    const row = [{
                        id: folder.id,
                        name: `${'-- '.repeat(level)}${folder.name}`
                    }];
                    const children = Array.isArray(folder.children) ? flattenFolders(folder.children,
                        level + 1) : [];
                    return row.concat(children);
                });
            };

            const loadFolders = async () => {
                const response = await fetch(foldersUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await response.json();
                const list = flattenFolders(Array.isArray(data.folders) ? data.folders : []);
                folderSelect.innerHTML = `<option value="">{{ __('All folders') }}</option>`;
                list.forEach((folder) => {
                    const option = document.createElement('option');
                    option.value = String(folder.id);
                    option.textContent = folder.name;
                    folderSelect.appendChild(option);
                });
            };

            const appendToGallery = (path) => {
                const lines = targetField.value
                    .split(/\r?\n|,/)
                    .map((item) => item.trim())
                    .filter((item) => item !== '');
                if (!lines.includes(path)) {
                    lines.push(path);
                }
                targetField.value = lines.join('\n');
                targetField.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
                targetField.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            };

            const applySelection = (item) => {
                if (typeof selectionCallback === 'function') {
                    selectionCallback(item);
                    setFeedback(`{{ __('Selected:') }} ${item.filename}`);
                    modal.hide();

                    return;
                }

                if (!targetField || !item?.path) {
                    return;
                }

                if (mode === 'gallery') {
                    appendToGallery(item.path);
                    setFeedback(`{{ __('Added:') }} ${item.filename}`);

                    return;
                }

                targetField.value = item.path;
                targetField.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
                targetField.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
                setFeedback(`{{ __('Selected:') }} ${item.filename}`);
                modal.hide();
            };

            const renderItems = (items, append = false) => {
                if (!append) {
                    grid.innerHTML = '';
                }
                if (!Array.isArray(items) || items.length === 0) {
                    if (!append) {
                        grid.innerHTML = '<div class="col-12 text-muted small">{{ __('No media found.') }}</div>';
                    }
                    return;
                }

                items.forEach((item) => {
                    const col = document.createElement('div');
                    col.className = 'col-6 col-md-3';
                    const thumb = item.thumbnail_url || item.url || item.full_url || '';
                    const isImage = (item.type || '') === 'image';
                    const previewHtml = isImage && thumb ?
                        `<img src="${thumb}" alt="${item.filename}" style="object-fit:cover;">` :
                        '<div class="d-flex align-items-center justify-content-center text-muted small"><i class="bi bi-file-earmark fs-4"></i></div>';
                    col.innerHTML = `
                <div class="card h-100">
                    <div class="card-body p-2">
                        <div class="ratio ratio-4x3 bg-light rounded overflow-hidden mb-2">
                            ${previewHtml}
                        </div>
                        <div class="small text-truncate mb-2" title="${item.filename}">${item.filename}</div>
                        <button type="button" class="btn btn-sm btn-outline-primary w-100 media-picker-select">{{ __('Select') }}</button>
                    </div>
                </div>
            `;
                    col.querySelector('.media-picker-select')?.addEventListener('click', () =>
                        applySelection(item));
                    grid.appendChild(col);
                });
            };

            const buildParams = (page = 1) => {
                const params = new URLSearchParams();
                params.set('type', mode === 'image' || mode === 'gallery' ? 'image' : (mode === 'video' ? 'video' : 'all'));
                params.set('page', String(page));
                if (folderSelect.value !== '') params.set('folder_id', folderSelect.value);
                if (searchInput.value.trim() !== '') params.set('q', searchInput.value.trim());
                return params;
            };

            const loadMedia = async () => {
                currentPage = 1;
                setFeedback('{{ __('Loading...') }}');
                const response = await fetch(`${browseUrl}?${buildParams(1).toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const payload = await response.json();
                lastPage = payload.meta?.last_page ?? 1;
                renderItems(Array.isArray(payload.data) ? payload.data : [], false);
                loadMoreWrap.style.setProperty('display', lastPage > 1 ? 'block' : 'none', 'important');
                setFeedback('');
            };

            const loadMoreMedia = async () => {
                if (currentPage >= lastPage) return;
                currentPage++;
                loadMoreBtn.disabled = true;
                loadMoreBtn.textContent = '{{ __('Loading...') }}';
                const response = await fetch(`${browseUrl}?${buildParams(currentPage).toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const payload = await response.json();
                lastPage = payload.meta?.last_page ?? lastPage;
                renderItems(Array.isArray(payload.data) ? payload.data : [], true);
                loadMoreBtn.disabled = false;
                loadMoreBtn.textContent = '{{ __('Load More') }}';
                if (currentPage >= lastPage) {
                    loadMoreWrap.style.setProperty('display', 'none', 'important');
                }
            };

            const uploadSelectedFiles = async () => {
                if (!uploadInput.files || uploadInput.files.length === 0) {
                    return;
                }

                const formData = new FormData();
                Array.from(uploadInput.files).forEach((file) => formData.append('files[]', file));
                if (folderSelect.value !== '') {
                    formData.append('folder_id', folderSelect.value);
                }
                formData.append('source_context', sourceContext);
                formData.append('_token', csrfToken);

                setFeedback('{{ __('Uploading...') }}');
                let response, payload;
                try {
                    response = await fetch(uploadUrl, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData,
                    });
                    payload = await response.json();
                } catch (e) {
                    setFeedback('ფაილის ატვირთვა ვერ მოხერხდა. შეამოწმეთ ინტერნეტ კავშირი.', true);
                    return;
                }

                if (!response.ok || !payload.success) {
                    const errMsg = payload?.message || payload?.errors
                        ? Object.values(payload.errors ?? {}).flat().join(' ')
                        : 'ფაილის ატვირთვა ვერ მოხერხდა.';
                    setFeedback(payload?.message || errMsg, true);
                    return;
                }

                const uploadedItems = Array.isArray(payload.media) ? payload.media : [];
                if (uploadedItems.length > 0) {
                    if (mode === 'gallery') {
                        uploadedItems.forEach((item) => applySelection(item));
                    } else {
                        applySelection(uploadedItems[0]);
                    }
                }

                uploadInput.value = '';
                await loadMedia();
                setFeedback('{{ __('Upload completed.') }}');
            };

            const createFolder = async () => {
                const name = createFolderInput.value.trim();
                if (name === '') {
                    return;
                }

                const body = new FormData();
                body.append('_token', csrfToken);
                body.append('name', name);
                if (folderSelect.value !== '') {
                    body.append('parent_id', folderSelect.value);
                }

                const response = await fetch(createFolderUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body,
                });
                const payload = await response.json();
                if (payload.success) {
                    createFolderInput.value = '';
                    await loadFolders();
                    setFeedback('{{ __('Folder created.') }}');
                }
            };

            const openPicker = async (options = {}) => {
                const trigger = options.trigger || null;
                const triggerMode = (options.mode || trigger?.dataset?.pickerMode || '').trim();
                mode = ['gallery', 'image', 'video', 'file'].includes(triggerMode) ? triggerMode : 'image';
                sourceContext = (options.sourceContext || '').trim() || inferSourceContext(trigger);
                selectionCallback = typeof options.onSelect === 'function' ? options.onSelect : null;

                const targetInputId = (options.targetInputId || trigger?.dataset?.targetInput || '').trim();
                const fieldWrapper = trigger?.closest('[data-media-field-wrapper], .mb-3, .mb-4, .col-md-6, .col-md-12, .col-lg-6, .col-lg-12, .card-body, .panel-body');

                targetField = options.targetField || (targetInputId !== ''
                    ? document.getElementById(targetInputId)
                    : (mode === 'gallery'
                        ? fieldWrapper?.querySelector('textarea')
                        : fieldWrapper?.querySelector('input[type="text"], input[type="url"]')));

                if (!targetField) {
                    const selector = options.targetSelector || trigger?.dataset?.targetSelector || '';
                    if (selector !== '') {
                        targetField = document.querySelector(selector);
                    }
                }

                if (!targetField && !selectionCallback) {
                    return;
                }

                uploadInput.setAttribute('accept', mode === 'image' || mode === 'gallery' ? 'image/*' :
                    (mode === 'video' ? 'video/*' : '*/*'));

                await loadFolders();
                await loadMedia();
                modal.show();
            };

            window.AdminMediaPicker = {
                open: openPicker,
            };

            document.addEventListener('click', async (event) => {
                const trigger = event.target.closest('.open-media-picker');
                if (!trigger) {
                    return;
                }

                await openPicker({ trigger });
            });

            searchBtn?.addEventListener('click', loadMedia);
            reloadBtn?.addEventListener('click', loadMedia);
            folderSelect?.addEventListener('change', loadMedia);
            uploadBtn?.addEventListener('click', uploadSelectedFiles);
            createFolderBtn?.addEventListener('click', createFolder);
            loadMoreBtn?.addEventListener('click', loadMoreMedia);
        });
    </script>
@endpush
