@if ($paginator->hasPages())
    <nav aria-label="Page navigation">
        <ul class="pagination mb-0 gap-1">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled">
                    <span class="page-link rounded-2">
                        <i class="bi bi-chevron-left small"></i>
                    </span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link rounded-2" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('Previous') }}">
                        <i class="bi bi-chevron-left small"></i>
                    </a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="page-item disabled">
                        <span class="page-link rounded-2">{{ $element }}</span>
                    </li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active">
                                <span class="page-link rounded-2" aria-current="page">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link rounded-2" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link rounded-2" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('Next') }}">
                        <i class="bi bi-chevron-right small"></i>
                    </a>
                </li>
            @else
                <li class="page-item disabled">
                    <span class="page-link rounded-2">
                        <i class="bi bi-chevron-right small"></i>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
