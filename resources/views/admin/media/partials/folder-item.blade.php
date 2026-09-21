@php
    $isActive = $currentFolder && (int) $currentFolder->id === (int) $folder->id;
    $hasChildren = $folder->children && $folder->children->count() > 0;
@endphp

<div class="folder-tree-item" id="folder-{{ $folder->id }}">
    <div class="d-flex align-items-center">
        @if($hasChildren)
            <button class="btn btn-link p-0 me-1 btn-sm text-muted" type="button" data-bs-toggle="collapse" data-bs-target="#children-{{ $folder->id }}">
                <i class="bi bi-chevron-right small"></i>
            </button>
        @else
            <span class="ps-3 me-1"></span>
        @endif
        <a href="{{ route('admin.media.index', ['folder_id' => $folder->id]) }}" class="folder-link flex-grow-1 {{ $isActive ? 'active' : '' }}">
            <i class="bi {{ $isActive ? 'bi-folder2-open' : 'bi-folder2' }}"></i>
            <span class="text-truncate">{{ $folder->name }}</span>
        </a>
    </div>
    
    @if($hasChildren)
        <div class="collapse ps-3 {{ $currentFolder && in_array($folder->id, $breadcrumbs ? array_column($breadcrumbs, 'id') : []) ? 'show' : '' }}" id="children-{{ $folder->id }}">
            @foreach($folder->children as $child)
                @include('admin.media.partials.folder-item', ['folder' => $child, 'currentFolder' => $currentFolder])
            @endforeach
        </div>
    @endif
</div>
