@if ($paginator->hasPages())
    <nav class="pager" role="navigation" aria-label="Halaman">
        @if ($paginator->onFirstPage())
            <span class="pager__item pager__item--mati">‹</span>
        @else
            <a class="pager__item" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹</a>
        @endif

        @if (isset($elements))
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pager__item pager__item--mati">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pager__item pager__item--on">{{ $page }}</span>
                        @else
                            <a class="pager__item" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        @endif

        @if ($paginator->hasMorePages())
            <a class="pager__item" href="{{ $paginator->nextPageUrl() }}" rel="next">›</a>
        @else
            <span class="pager__item pager__item--mati">›</span>
        @endif
    </nav>
@endif
