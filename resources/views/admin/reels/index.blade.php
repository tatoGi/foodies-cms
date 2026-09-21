@extends('admin.layouts.app')

@section('title', __('Reels'))
@section('page_title', __('Reels'))

@section('content')
    <div class="row align-items-center mb-5">
        <div class="col-md-8">
            <h2 class="welcome-title mb-1">{{ __('Reels Management') }}</h2>
            <p class="text-muted mb-0">{{ __('Create and manage story-style reels shown on the website.') }}</p>
            <p class="text-muted small mb-0 mt-1">{{ __('Products appear in reels when "Show in Reels" is enabled on the product.') }}</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="{{ route('admin.reels.create') }}" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i>
                <span>{{ __('Create New Reel') }}</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4">{{ session('success') }}</div>
    @endif

    <div id="reorder-feedback" class="d-none mb-3"></div>

    <div class="dashboard-panel premium-shadow overflow-hidden">
        <div class="panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light-soft">
                        <tr>
                            <th class="ps-3 py-3 text-muted" style="width:40px;" title="{{ __('Drag to reorder') }}"><i class="bi bi-grip-vertical"></i></th>
                            <th class="py-3 text-muted small fw-bold">{{ __('Title') }}</th>
                            <th class="py-3 text-muted small fw-bold table-col-secondary">{{ __('Category') }}</th>
                            <th class="py-3 text-muted small fw-bold table-col-secondary">{{ __('Languages') }}</th>
                            <th class="py-3 text-muted small fw-bold text-center">{{ __('Active') }}</th>
                            <th class="pe-4 py-3 text-muted small fw-bold text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody id="reels-sortable-list">
                        @forelse($reels as $reel)
                            @php
                                $preferredTranslation = $reel->translations->firstWhere('locale', $currentLocale);
                                $fallbackTranslation  = $reel->translations->first();
                                $title = $preferredTranslation?->title ?? $fallbackTranslation?->title ?? '#'.$reel->id;
                                $categoryMap = [
                                    'sale'    => ['label' => '🔥 '.__('Sale'),    'class' => 'badge-warning'],
                                    'project' => ['label' => '🏗️ '.__('Project'), 'class' => 'badge-info'],
                                    'new'     => ['label' => '🆕 '.__('New'),     'class' => 'badge-success'],
                                ];
                                $cat = $categoryMap[$reel->category] ?? ['label' => $reel->category, 'class' => 'badge-secondary'];
                            @endphp
                            <tr data-id="{{ $reel->id }}">
                                <td class="ps-3 py-3 drag-handle" style="cursor:grab; color:var(--admin-muted);">
                                    <i class="bi bi-grip-vertical"></i>
                                </td>
                                <td class="py-3">
                                    <h6 class="mb-0 fw-bold">{{ $title }}</h6>
                                    <span class="small text-muted">{{ __('ID') }}: {{ $reel->id }}</span>
                                    @if($reel->video_url)
                                        <span class="badge bg-light-soft text-primary border ms-1"><i class="bi bi-camera-video"></i> {{ __('Video') }}</span>
                                    @endif
                                </td>
                                <td class="py-3 table-col-secondary">
                                    <span class="badge-soft {{ $cat['class'] }}">{{ $cat['label'] }}</span>
                                </td>
                                <td class="py-3 table-col-secondary">
                                    <div class="d-flex align-items-center gap-1">
                                        @foreach($reel->translations as $translation)
                                            <span class="badge bg-light-soft text-primary border px-2 py-1">{{ strtoupper($translation->locale) }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="py-3 text-center">
                                    @if($reel->is_active)
                                        <span class="badge-soft badge-success">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge-soft badge-secondary">{{ __('Hidden') }}</span>
                                    @endif
                                </td>
                                <td class="pe-4 py-3 text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('admin.reels.edit', $reel) }}" class="btn btn-icon btn-light-soft" title="{{ __('Edit') }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.reels.destroy', $reel) }}" method="POST"
                                              onsubmit="return confirm('{{ __('Delete this reel?') }}')">
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
                                <td colspan="6" class="py-5 text-center text-muted">
                                    <i class="bi bi-camera-video h1 d-block mb-3 opacity-25"></i>
                                    {{ __('No reels found.') }}
                                </td>
                            </tr>
                        @endforelse
                        @foreach($productReels ?? [] as $product)
                            @php
                                $preferredTranslation = $product->translations->firstWhere('locale', $currentLocale);
                                $fallbackTranslation  = $product->translations->first();
                                $title = $preferredTranslation?->title ?? $fallbackTranslation?->title ?? '#'.$product->id;
                                $isVisible = $product->published && $product->is_active;
                            @endphp
                            <tr class="bg-light-soft">
                                <td class="ps-3 py-3 text-muted">
                                    <i class="bi bi-box-seam"></i>
                                </td>
                                <td class="py-3">
                                    <h6 class="mb-0 fw-bold">{{ $title }}</h6>
                                    <span class="small text-muted">{{ __('Product') }} #{{ $product->id }}</span>
                                </td>
                                <td class="py-3 table-col-secondary">
                                    <span class="badge-soft badge-secondary">{{ __('reel_category_product') }}</span>
                                </td>
                                <td class="py-3 table-col-secondary">
                                    <div class="d-flex align-items-center gap-1">
                                        @foreach($product->translations as $translation)
                                            <span class="badge bg-light-soft text-primary border px-2 py-1">{{ strtoupper($translation->locale) }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="py-3 text-center">
                                    @if($isVisible)
                                        <span class="badge-soft badge-success">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge-soft badge-secondary">{{ __('Hidden') }}</span>
                                    @endif
                                </td>
                                <td class="pe-4 py-3 text-end">
                                    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-icon btn-light-soft" title="{{ __('Edit Product') }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 bg-light-soft border-top">
                {{ $reels->links() }}
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
.table thead th { font-size: 0.7rem; background: var(--admin-surface-2); border-bottom: 2px solid var(--admin-border); }
.badge-soft.badge-warning   { background: #fff3cd; color: #856404; }
.badge-soft.badge-info      { background: #cff4fc; color: #055160; }
.badge-soft.badge-success   { background: var(--success-soft); color: var(--success); }
.badge-soft.badge-secondary { background: var(--admin-surface-2); color: var(--admin-muted); }
.drag-handle:active { cursor: grabbing; }
.sortable-ghost  { opacity: 0.4; background: var(--admin-surface-2); }
.sortable-chosen { background: var(--admin-surface-2); }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const list     = document.getElementById('reels-sortable-list');
    const feedback = document.getElementById('reorder-feedback');
    if (!list) { return; }

    Sortable.create(list, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd: function () {
            const ids = Array.from(list.querySelectorAll('tr[data-id]')).map(r => parseInt(r.dataset.id, 10));
            fetch('{{ route('admin.reels.reorder') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ ordered_ids: ids }),
            })
            .then(r => r.json())
            .then(data => {
                feedback.className = data.success
                    ? 'alert alert-success border-0 shadow-sm rounded-3 mb-3'
                    : 'alert alert-danger border-0 shadow-sm rounded-3 mb-3';
                feedback.textContent = data.success
                    ? '{{ __('Order saved.') }}'
                    : '{{ __('Failed to save order.') }}';
                setTimeout(() => { feedback.className = 'd-none'; }, 2500);
            });
        },
    });
});
</script>
@endpush
