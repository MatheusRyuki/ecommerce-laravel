@if ($paginator->hasPages())
    <nav aria-label="Navegação das páginas">
        <ul class="pagination justify-content-center">
            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                @if ($paginator->onFirstPage())
                    <a class="page-link" aria-disabled="true" tabindex="-1" aria-label="Anterior">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                @else
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Anterior">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true">
                        <a class="page-link" aria-disabled="true" tabindex="-1">{{ $element }}</a>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li class="page-item {{ $page == $paginator->currentPage() ? 'active' : '' }}">
                            @if ($page == $paginator->currentPage())
                                <a class="page-link active" aria-current="page">{{ $page }}</a>
                            @else
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                @if ($paginator->hasMorePages())
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Próxima">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                @else
                    <a class="page-link" aria-disabled="true" tabindex="-1" aria-label="Próxima">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                @endif
            </li>
        </ul>
    </nav>
@endif
