<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">{{ __('Upload Media') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" id="uploadForm">
                    @csrf
                    <div class="upload-area mt-2 mb-4 p-5 border border-2 border-dashed rounded-4 text-center" id="dropArea">
                        <i class="bi bi-cloud-arrow-up display-4 text-primary opacity-50"></i>
                        <h6 class="mt-3 fw-bold">{{ __('Click or drag and drop files here') }}</h6>
                        <p class="text-muted small">{{ __('Allowed extensions') }}: {{ implode(', ', $allowedExtensions) }}</p>
                        <input type="file" name="files[]" id="fileInput" class="d-none" multiple required>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">{{ __('Destination Folder') }}</label>
                            <select name="folder_id" class="form-select rounded-3">
                                <option value="">{{ __('Root Library') }}</option>
                                @foreach($flatFolders as $folder)
                                    <option value="{{ $folder->id }}" {{ $currentFolder && (int) $currentFolder->id === (int) $folder->id ? 'selected' : '' }}>
                                        {{ $folder->path ?: $folder->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">{{ __('Common Title') }}</label>
                            <input type="text" name="title" class="form-control rounded-3" placeholder="{{ __('Optional prefix for all files') }}">
                        </div>
                    </div>

                    <div id="uploadError" class="alert alert-danger d-none mb-3" role="alert"></div>

                    <div class="mt-4 d-grid">
                        <button type="submit" class="btn btn-primary py-2 rounded-3" id="uploadSubmitBtn">
                            {{ __('Start Uploading') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Create Folder Modal -->
<div class="modal fade" id="createFolderModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">{{ __('New Folder') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST" action="{{ route('admin.media-folders.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-bold">{{ __('Name') }}</label>
                        <input type="text" name="name" class="form-control rounded-3" placeholder="{{ __('Enter folder name') }}" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">{{ __('Parent Folder') }}</label>
                        <select name="parent_id" class="form-select rounded-3">
                            <option value="">{{ __('Root') }}</option>
                            @foreach($flatFolders as $folder)
                                <option value="{{ $folder->id }}" {{ $currentFolder && (int) $currentFolder->id === (int) $folder->id ? 'selected' : '' }}>
                                    {{ $folder->path ?: $folder->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary py-2 rounded-3">
                            {{ __('Create Folder') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.upload-area {
    cursor: pointer;
    transition: all 0.2s;
    background: #f8fafc;
}
.upload-area:hover, .upload-area.drag-over {
    background: var(--primary-soft);
    border-color: var(--primary) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const dropArea  = document.getElementById('dropArea');
    const fileInput = document.getElementById('fileInput');
    const uploadForm = document.getElementById('uploadForm');
    const uploadError = document.getElementById('uploadError');
    const uploadSubmitBtn = document.getElementById('uploadSubmitBtn');

    if (dropArea) {
        dropArea.addEventListener('click', () => fileInput.click());

        dropArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropArea.classList.add('drag-over');
        });

        dropArea.addEventListener('dragleave', () => dropArea.classList.remove('drag-over'));

        dropArea.addEventListener('drop', (e) => {
            e.preventDefault();
            dropArea.classList.remove('drag-over');
            fileInput.files = e.dataTransfer.files;
            const count = e.dataTransfer.files.length;
            dropArea.querySelector('h6').textContent = `${count} ${count === 1 ? 'ფაილი' : 'ფაილი'} მზადაა ასატვირთად`;
        });

        fileInput.addEventListener('change', () => {
            const count = fileInput.files.length;
            dropArea.querySelector('h6').textContent = `${count} ფაილი მზადაა ასატვირთად`;
        });
    }

    if (uploadForm) {
        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            uploadError.classList.add('d-none');
            uploadError.textContent = '';
            uploadSubmitBtn.disabled = true;
            uploadSubmitBtn.textContent = '{{ __('Uploading...') }}';

            const formData = new FormData(uploadForm);
            let response, payload;
            try {
                response = await fetch(uploadForm.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData,
                });
                payload = await response.json();
            } catch (err) {
                uploadError.textContent = 'ფაილის ატვირთვა ვერ მოხერხდა. შეამოწმეთ ინტერნეტ კავშირი.';
                uploadError.classList.remove('d-none');
                uploadSubmitBtn.disabled = false;
                uploadSubmitBtn.textContent = '{{ __('Start Uploading') }}';
                return;
            }

            if (!response.ok || !payload.success) {
                const msg = payload?.message
                    || (payload?.errors ? Object.values(payload.errors).flat().join(' ') : null)
                    || 'ფაილის ატვირთვა ვერ მოხერხდა.';
                uploadError.textContent = msg;
                uploadError.classList.remove('d-none');
                uploadSubmitBtn.disabled = false;
                uploadSubmitBtn.textContent = '{{ __('Start Uploading') }}';
                return;
            }

            window.location.reload();
        });
    }
});
</script>
