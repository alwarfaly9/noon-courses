@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="pagination">
        @if ($paginator->hasMorePages() || ! $paginator->onFirstPage())
            <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
                <p class="text-sm text-gray-500">
                    عرض
                    @if ($paginator->firstItem())
                        <span class="font-semibold text-gray-700">{{ $paginator->firstItem() }}</span>
                        إلى
                        <span class="font-semibold text-gray-700">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    من أصل <span class="font-semibold text-gray-700">{{ $paginator->total() }}</span>
                </p>

                <div class="flex items-center gap-1.5">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span class="pagination-link pagination-link-disabled" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <i class="fas fa-chevron-right text-xs"></i>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination-link" aria-label="{{ __('pagination.previous') }}">
                            <i class="fas fa-chevron-right text-xs"></i>
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span class="pagination-link pagination-link-disabled cursor-default">{{ $element }}</span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span class="pagination-link pagination-link-active" aria-current="page">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="pagination-link" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination-link" aria-label="{{ __('pagination.next') }}">
                            <i class="fas fa-chevron-left text-xs"></i>
                        </a>
                    @else
                        <span class="pagination-link pagination-link-disabled" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <i class="fas fa-chevron-left text-xs"></i>
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </nav>
@endif
