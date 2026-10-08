@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="admin-pagination" aria-label="Table pagination">
        @if ($paginator->previousPageUrl())
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
        @else
            <span aria-disabled="true">Previous</span>
        @endif

        <span>Page {{ $paginator->currentPage() }}</span>

        @if ($paginator->nextPageUrl())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
        @else
            <span aria-disabled="true">Next</span>
        @endif
    </nav>
@endif