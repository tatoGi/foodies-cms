@extends('admin.layouts.app')

@section('title', __('Pages'))
@section('page_title', __('Pages'))

@section('content')
    <div class="row align-items-center mb-5">
        <div class="col-md-8">
            <h2 class="welcome-title mb-1">{{ __('Pages Management') }}</h2>
            <p class="text-muted mb-0">{{ __('Drag and drop pages to reorder them or assign parent/child relations.') }}</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="{{ route('admin.pages.create') }}" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                <i class="bi bi-plus-lg"></i>
                <span>{{ __('Create New Page') }}</span>
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
        <div class="panel-body p-4">
            @if(($pageTree ?? []) === [])
                <div class="py-5 text-center text-muted">
                    <i class="bi bi-file-earmark-text h1 d-block mb-3 opacity-25"></i>
                    {{ __('No pages found.') }}
                </div>
            @else
                <div class="page-tree-toolbar d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div class="page-tree-search-wrap">
                        <input type="text" id="page-tree-search" class="form-control form-control-sm" placeholder="{{ __('Search pages by title, slug, template or ID...') }}">
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-light-soft" id="expand-all-pages">
                            <i class="bi bi-arrows-expand me-1"></i>{{ __('Expand all') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-light-soft" id="collapse-all-pages">
                            <i class="bi bi-arrows-collapse me-1"></i>{{ __('Collapse all') }}
                        </button>
                    </div>
                </div>
                <div class="page-tree-list" id="pages-tree-root" data-parent-id="">
                    @foreach($pageTree as $node)
                        @include('admin.pages.partials.tree-node', ['node' => $node, 'currentLocale' => $currentLocale])
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection

@push('styles')
<style>
.page-tree-list {
    min-height: 12px;
}
.page-tree-children {
    margin-left: 38px;
    padding-left: 14px;
    border-left: 2px dashed var(--admin-border);
}
.page-tree-item {
    margin-bottom: 2px;
}
.page-tree-toolbar .page-tree-search-wrap {
    min-width: 280px;
    flex: 1 1 340px;
}
.page-tree-toolbar #page-tree-search {
    max-width: 520px;
}
.page-tree-card {
    background: var(--admin-surface);
    border-color: var(--admin-border) !important;
    transition: box-shadow .15s ease, border-color .15s ease;
}
.page-tree-card:hover {
    border-color: var(--admin-border-strong);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
}
.drag-handle {
    cursor: grab;
    color: var(--admin-muted);
}
.drag-handle:active {
    cursor: grabbing;
}
.tree-toggle {
    width: 28px;
    min-width: 28px;
    height: 28px;
    padding: 0;
    border-radius: 8px;
}
.tree-toggle.is-disabled,
.tree-toggle:disabled {
    opacity: .45;
    pointer-events: none;
}
.page-tree-item.is-collapsed > .page-tree-card .tree-toggle i {
    transform: rotate(-90deg);
}
.page-tree-item.is-collapsed > .page-tree-children {
    display: none;
}
.badge-soft.badge-success {
    background: var(--success-soft);
    color: var(--success);
}
.badge-soft.badge-secondary {
    background: var(--admin-surface-2);
    color: var(--admin-muted);
}
.page-tree-search-wrap .form-control {
    background: var(--admin-surface);
    color: var(--admin-text);
    border-color: var(--admin-border);
}
.page-tree-search-wrap .form-control::placeholder {
    color: var(--admin-muted);
}
.page-tree-card h6,
.page-tree-card code {
    color: var(--admin-text);
}
.page-tree-card .text-muted {
    color: var(--admin-muted) !important;
}
.page-locale-badge {
    background: var(--admin-surface-2);
    color: var(--primary);
    border: 1px solid var(--admin-border-strong);
}
.sortable-ghost {
    opacity: .45;
}
.sortable-chosen .page-tree-card,
.page-tree-item.sortable-chosen .page-tree-card {
    background: var(--admin-surface-2);
}
body.theme-dark .page-tree-card:hover {
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.28);
}
body.theme-dark .page-tree-list.tree-drop-target {
    background: rgba(99, 102, 241, 0.14);
    outline-color: rgba(129, 140, 248, 0.55);
}
body.theme-dark .page-tree-item.drag-parent-candidate > .page-tree-card {
    border-color: rgba(129, 140, 248, 0.7);
    box-shadow: 0 0 0 2px rgba(129, 140, 248, 0.22);
}
.page-tree-list.tree-drop-target {
    background: rgba(37, 99, 235, 0.06);
    border-radius: 12px;
    outline: 2px dashed rgba(37, 99, 235, 0.45);
    outline-offset: 2px;
}
.page-tree-item.drag-parent-candidate > .page-tree-card {
    border-color: rgba(37, 99, 235, 0.55);
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.18);
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rootList = document.getElementById('pages-tree-root');
    if (!rootList) {
        return;
    }

    const feedback = document.getElementById('reorder-feedback');
    const reorderUrl = '{{ route('admin.pages.reorder') }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const searchInput = document.getElementById('page-tree-search');
    const expandAllButton = document.getElementById('expand-all-pages');
    const collapseAllButton = document.getElementById('collapse-all-pages');
    let saveTimeout = null;
    let activeDropList = null;
    let activeParentCandidate = null;

    const showFeedback = (type, message) => {
        feedback.className = `alert alert-${type} border-0 shadow-sm rounded-3 mb-3`;
        feedback.textContent = message;
        setTimeout(() => {
            feedback.className = 'd-none mb-3';
        }, 2600);
    };

    const directItems = (container) => {
        return Array.from(container.children).filter((child) => child.classList.contains('page-tree-item'));
    };

    const directChildrenContainer = (item) => {
        return item.querySelector(':scope > .page-tree-children');
    };

    const directChildCount = (item) => {
        const container = directChildrenContainer(item);
        return container ? directItems(container).length : 0;
    };

    const refreshTreeControls = () => {
        document.querySelectorAll('.page-tree-item').forEach((item) => {
            const hasChildren = directChildCount(item) > 0;
            item.classList.toggle('has-children', hasChildren);

            const toggle = item.querySelector(':scope > .page-tree-card .tree-toggle');
            if (!(toggle instanceof HTMLButtonElement)) {
                return;
            }

            toggle.disabled = !hasChildren;
            toggle.classList.toggle('is-disabled', !hasChildren);

            if (!hasChildren) {
                setCollapsed(item, false);
            }
        });
    };

    const serializeTree = (container) => {
        return directItems(container).map((item) => {
            const id = Number.parseInt(item.dataset.id || '0', 10);
            const childContainer = directChildrenContainer(item);

            return {
                id,
                children: childContainer ? serializeTree(childContainer) : [],
            };
        }).filter((node) => Number.isInteger(node.id) && node.id > 0);
    };

    const saveTree = () => {
        const tree = serializeTree(rootList);
        if (tree.length === 0) {
            return;
        }

        fetch(reorderUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ tree }),
        })
            .then(async (response) => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success) {
                    throw new Error(data.message || '{{ __('Failed to save order.') }}');
                }

                showFeedback('success', '{{ __('Order and hierarchy saved.') }}');
            })
            .catch((error) => {
                showFeedback('danger', error.message || '{{ __('Failed to save order.') }}');
            });
    };

    const queueSave = () => {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(saveTree, 120);
    };

    const setCollapsed = (item, collapsed) => {
        if (!(item instanceof HTMLElement)) {
            return;
        }
        item.classList.toggle('is-collapsed', collapsed);
    };

    const clearDropTargetState = () => {
        if (activeDropList) {
            activeDropList.classList.remove('tree-drop-target');
            activeDropList = null;
        }
        if (activeParentCandidate) {
            activeParentCandidate.classList.remove('drag-parent-candidate');
            activeParentCandidate = null;
        }
    };

    const applySearch = () => {
        const query = (searchInput?.value || '').trim().toLowerCase();
        const filterNode = (item) => {
            if (!(item instanceof HTMLElement)) {
                return false;
            }

            const childrenContainer = directChildrenContainer(item);
            const childItems = childrenContainer
                ? directItems(childrenContainer)
                : [];
            const childMatches = childItems.map(filterNode).some(Boolean);

            const text = String(item.dataset.search || '').toLowerCase();
            const selfMatches = query === '' || text.includes(query);
            const isVisible = selfMatches || childMatches;

            item.classList.toggle('d-none', !isVisible);

            if (query !== '' && childMatches) {
                setCollapsed(item, false);
            }

            return isVisible;
        };

        directItems(rootList).forEach(filterNode);
    };

    document.addEventListener('click', (event) => {
        const toggleButton = event.target.closest('.tree-toggle');
        if (!toggleButton) {
            return;
        }

        const treeItem = toggleButton.closest('.page-tree-item');
        if (!treeItem) {
            return;
        }

        if (directChildCount(treeItem) < 1) {
            return;
        }

        setCollapsed(treeItem, !treeItem.classList.contains('is-collapsed'));
    });

    searchInput?.addEventListener('input', applySearch);
    expandAllButton?.addEventListener('click', () => {
        document.querySelectorAll('.page-tree-item').forEach((item) => {
            if (directChildCount(item) > 0) {
                setCollapsed(item, false);
            }
        });
    });
    collapseAllButton?.addEventListener('click', () => {
        document.querySelectorAll('.page-tree-item').forEach((item) => {
            if (directChildCount(item) > 0) {
                setCollapsed(item, true);
            }
        });
    });

    refreshTreeControls();
    if (document.querySelectorAll('.page-tree-item').length > 25) {
        document.querySelectorAll('.page-tree-item').forEach((item) => {
            if (directChildCount(item) > 0) {
                setCollapsed(item, true);
            }
        });
    }

    document.querySelectorAll('.page-tree-list').forEach((list) => {
        Sortable.create(list, {
            group: 'pages-tree',
            handle: '.drag-handle',
            animation: 140,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            draggable: '.page-tree-item',
            fallbackOnBody: true,
            swapThreshold: 0.6,
            onStart: clearDropTargetState,
            onMove: (evt) => {
                clearDropTargetState();

                if (evt.to instanceof HTMLElement) {
                    activeDropList = evt.to;
                    activeDropList.classList.add('tree-drop-target');

                    const candidateParent = activeDropList.closest('.page-tree-item');
                    if (candidateParent instanceof HTMLElement) {
                        activeParentCandidate = candidateParent;
                        activeParentCandidate.classList.add('drag-parent-candidate');
                    }
                }
            },
            onEnd: () => {
                clearDropTargetState();
                refreshTreeControls();
                queueSave();
            },
        });
    });
});
</script>
@endpush
