<div class="pagination-wrap" data-pagination-summary>
    <div class="pagination-summary">
        @if (method_exists($paginator, 'total'))
            Showing {{ number_format($paginator->firstItem() ?? 0) }} to {{ number_format($paginator->lastItem() ?? 0) }} of {{ number_format($paginator->total()) }} entries
        @else
            Page {{ number_format($paginator->currentPage()) }}
        @endif
    </div>
    @if ($paginator->hasPages())
        <nav class="pagination-links" role="navigation" aria-label="Pagination Navigation">
            @if ($paginator->onFirstPage())
                <span class="page-link disabled" aria-disabled="true">Previous</span>
            @else
                <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
            @endif

            @foreach (($elements ?? []) as $element)
                @if (is_string($element))
                    <span class="page-link dots" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="page-link active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
            @else
                <span class="page-link disabled" aria-disabled="true">Next</span>
            @endif
    </nav>
    @endif
</div>
