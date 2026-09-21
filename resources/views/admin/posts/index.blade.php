@extends('admin.layouts.app')

@section('title', __('Posts'))
@section('page_title', __('Posts'))

@section('content')
    <div class="row align-items-center mb-5">
        <div class="col-md-8">
            <h2 class="welcome-title mb-1">{{ __('Posts Management') }}</h2>
            <p class="text-muted mb-0">{{ __('Create and manage your posts here.') }}</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="{{ route('admin.posts.create') }}" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i>
                <span>{{ __('Create New Post') }}</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div id="reorder-feedback" class="d-none mb-3"></div>

    <div class="dashboard-panel premium-shadow overflow-hidden">
        <div class="panel-body p-0">
            <div class="table-responsive" data-mobile-columns="true">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light-soft">
                        <tr>
                            <th class="ps-3 py-3 text-muted" style="width:40px;" title="{{ __('Drag to reorder') }}"><i class="bi bi-grip-vertical"></i></th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1">{{ __('Title') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1 table-col-secondary">{{ __('Slug') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1 table-col-secondary">{{ __('Category') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1 table-col-secondary">{{ __('Pages') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1 text-center">{{ __('Status') }}</th>
                            <th class="py-3 text-muted small fw-bold uppercase letter-spacing-1 text-center table-col-secondary">{{ __('Language') }}</th>
                            <th class="pe-4 py-3 text-muted small fw-bold uppercase letter-spacing-1 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody id="posts-sortable-list">
                        @forelse($posts as $post)
                            @php
                                $preferredTranslation = $post->translations->firstWhere('locale', $currentLocale);
                                $fallbackTranslation = $post->translations->first();
                                $title = $preferredTranslation?->title ?? $fallbackTranslation?->title ?? '#'.$post->id;
                                $slug = $preferredTranslation?->slug ?? $fallbackTranslation?->slug ?? '-';
                            @endphp
                            <tr data-id="{{ $post->id }}">
                                <td class="ps-3 py-4 drag-handle" style="cursor:grab; color:var(--admin-muted);">
                                    <i class="bi bi-grip-vertical"></i>
                                </td>
                                <td class="py-4">
                                    <div>
                                        <h6 class="mb-0 fw-bold">{{ $title }}</h6>
                                        <span class="small text-muted">{{ __('ID') }}: {{ $post->id }}</span>
                                    </div>
                                </td>
                                <td class="py-4 table-col-secondary"><code>{{ $slug }}</code></td>
                                <td class="py-4 table-col-secondary">{{ $post->category ?: '-' }}</td>
                                <td class="py-4 table-col-secondary">
                                    <div class="d-flex flex-wrap gap-1">
                                        @forelse($post->pages as $linkedPage)
                                            @php
                                                $pageTitle = $linkedPage->translations->firstWhere('locale', $currentLocale)?->title
                                                    ?? $linkedPage->translations->first()?->title
                                                    ?? '#'.$linkedPage->id;
                                            @endphp
                                            <span class="badge bg-light-soft text-secondary border px-2 py-1 small">{{ $pageTitle }}</span>
                                        @empty
                                            <span class="text-muted small">-</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="py-4 text-center">
                                    @if($post->published)
                                        <span class="badge-soft badge-success">{{ __('Published') }}</span>
                                    @else
                                        <span class="badge-soft badge-secondary">{{ __('Draft') }}</span>
                                    @endif
                                </td>
                                <td class="py-4 text-center table-col-secondary">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        @foreach($post->translations as $translation)
                                            <span class="badge bg-light-soft text-primary border px-2 py-1">{{ strtoupper($translation->locale) }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="pe-4 py-4 text-end">
                                    <div class="table-actions-inline d-flex justify-content-end gap-2">
                                        <a href="{{ route('admin.posts.edit', $post) }}" class="btn btn-icon btn-light-soft" title="{{ __('Edit') }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.posts.destroy', $post) }}" method="POST" onsubmit="return confirm('{{ __('Are you sure you want to delete this post?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-icon btn-light-soft text-danger" title="{{ __('Delete') }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-5 text-center text-muted">
                                    <i class="bi bi-file-earmark-text h1 d-block mb-3 opacity-25"></i>
                                    {{ __('No posts found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 bg-light-soft border-top">
                {{ $posts->links() }}
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
.table thead th {
    font-size: 0.7rem;
    background: var(--admin-surface-2);
    border-bottom: 2px solid var(--admin-border);
}
.letter-spacing-1 { letter-spacing: 0.05em; }
.badge-soft.badge-success { background: var(--success-soft); color: var(--success); }
.badge-soft.badge-secondary { background: var(--admin-surface-2); color: var(--admin-muted); }
.drag-handle:active { cursor: grabbing; }
.sortable-ghost { opacity: 0.4; background: var(--admin-surface-2); }
.sortable-chosen { background: var(--admin-surface-2); }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const list = document.getElementById('posts-sortable-list');
    if (!list) return;

    const feedback = document.getElementById('reorder-feedback');
    const reorderUrl = '{{ route('admin.posts.reorder') }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    Sortable.create(list, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd: function () {
            const rows = Array.from(list.querySelectorAll('tr[data-id]'));
            const orderedIds = rows.map(row => parseInt(row.dataset.id, 10));

            fetch(reorderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ ordered_ids: orderedIds }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    feedback.className = 'alert alert-success border-0 shadow-sm rounded-3 mb-3';
                    feedback.textContent = '{{ __('Order saved.') }}';
                } else {
                    feedback.className = 'alert alert-danger border-0 shadow-sm rounded-3 mb-3';
                    feedback.textContent = data.message || '{{ __('Failed to save order.') }}';
                }
                setTimeout(() => { feedback.className = 'd-none'; }, 2500);
            })
            .catch(() => {
                feedback.className = 'alert alert-danger border-0 shadow-sm rounded-3 mb-3';
                feedback.textContent = '{{ __('Failed to save order.') }}';
                setTimeout(() => { feedback.className = 'd-none'; }, 2500);
            });
        },
    });
});
</script>
@endpush
