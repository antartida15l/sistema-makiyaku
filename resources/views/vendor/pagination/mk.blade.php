@if ($paginator->hasPages() || $paginator->total() > 0)
<div class="pager">
    <span>Mostrando {{ $paginator->firstItem() ?? 0 }} a {{ $paginator->lastItem() ?? 0 }} de {{ $paginator->total() }}</span>
    <nav aria-label="Paginación">
        @if ($paginator->onFirstPage())
            <span class="pg off" aria-disabled="true">Anterior</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))<span class="pg off">{{ $element }}</span>@endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())<span class="pg" aria-current="page">{{ $page }}</span>
                    @else<a href="{{ $url }}" aria-label="Ir a la página {{ $page }}">{{ $page }}</a>@endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente</a>
        @else
            <span class="pg off" aria-disabled="true">Siguiente</span>
        @endif
    </nav>
</div>
@endif
