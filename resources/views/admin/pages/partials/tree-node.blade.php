@php
    /** @var \App\Models\Page $page */
    $page = $node['page'];
    $children = $node['children'] ?? [];
    $hasChildren = count($children) > 0;
    $preferredTranslation = $page->translations->firstWhere('locale', $currentLocale);
    $fallbackTranslation = $page->translations->first();
    $templatePreferred = $page->templateRef?->translations?->firstWhere('locale', $currentLocale);
    $templateFallback = $page->templateRef?->translations?->first();
    $title = $preferredTranslation?->title ?? $fallbackTranslation?->title ?? '#'.$page->id;
    $slug = $preferredTranslation?->slug ?? $fallbackTranslation?->slug ?? '-';
    $templateName = $templatePreferred?->name ?? $templateFallback?->name ?? $page->template ?? '-';
    $searchTokens = mb_strtolower(trim($title.' '.$slug.' '.$templateName.' '.$page->id));
@endphp

<div class="page-tree-item {{ $hasChildren ? 'has-children' : '' }}" data-id="{{ $page->id }}" data-search="{{ $searchTokens }}">
    <div class="page-tree-card border rounded-3 p-3 mb-2">
        <div class="d-flex align-items-start justify-content-between gap-3">
            <div class="d-flex align-items-start gap-3 flex-grow-1">
                <button type="button"
                        class="btn btn-sm btn-light-soft tree-toggle {{ $hasChildren ? '' : 'is-disabled' }}"
                        data-target-id="{{ $page->id }}"
                        title="{{ __('Collapse / Expand children') }}"
                        @disabled(! $hasChildren)>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="drag-handle pt-1" title="{{ __('Drag to reorder / nest') }}">
                    <i class="bi bi-grip-vertical"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <h6 class="mb-0 fw-bold">{{ $title }}</h6>
                        <span class="badge-soft badge-secondary">{{ __('ID') }}: {{ $page->id }}</span>
                        <span class="badge-soft badge-secondary">{{ $templateName }}</span>
                        @if($page->published)
                            <span class="badge-soft badge-success">{{ __('Published') }}</span>
                        @else
                            <span class="badge-soft badge-secondary">{{ __('Draft') }}</span>
                        @endif
                    </div>
                    <div class="small text-muted d-flex flex-wrap align-items-center gap-2">
                        <code>{{ $slug }}</code>
                        <span>•</span>
                        <span>{{ __('Languages') }}:</span>
                        <span class="d-inline-flex flex-wrap gap-1">
                            @foreach($page->translations as $translation)
                                <span class="badge page-locale-badge px-2 py-1">{{ strtoupper($translation->locale) }}</span>
                            @endforeach
                        </span>
                    </div>
                </div>
            </div>
            <div class="table-actions-inline d-flex gap-2">
                <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-icon btn-light-soft" title="{{ __('Edit') }}">
                    <i class="bi bi-pencil"></i>
                </a>
                <form action="{{ route('admin.pages.destroy', $page) }}" method="POST" onsubmit="return confirm('{{ __('Are you sure you want to delete this page?') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-icon btn-light-soft text-danger" title="{{ __('Delete') }}">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="page-tree-list page-tree-children" data-parent-id="{{ $page->id }}" id="children-{{ $page->id }}">
        @foreach($children as $childNode)
            @include('admin.pages.partials.tree-node', ['node' => $childNode, 'currentLocale' => $currentLocale])
        @endforeach
    </div>
</div>
