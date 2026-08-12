<ul class="pagination">
    <!-- Filtro de filas por página a la derecha -->
    <form method="GET" action="{{ request()->fullUrlWithQuery([]) }}" id="rows-per-page-form" class="form-inline">
        <div class="form-group">
            <label for="rows" class="me-2"><i class="fas fa-eye"></i> </label>
            <select name="rows" id="rows" class="form-control" onchange="document.getElementById('rows-per-page-form').submit();">
                <option value="10" {{ request('rows') == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ request('rows') == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ request('rows') == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ request('rows') == 100 ? 'selected' : '' }}>100</option>
            </select>
        </div>
    </form>
   
    {{-- Botón Anterior --}}
    @if ($paginator->onFirstPage())
        <li class="page-item disabled" aria-disabled="true">
            <span class="page-link" aria-hidden="true">&laquo;</span>
        </li>
    @else
        <li class="page-item">
            <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev"
                aria-label="Anterior">&laquo;</a>
        </li>
    @endif

    {{-- Conteo de Páginas hacia Atrás --}}
    @for ($i = max(1, $paginator->currentPage() - 2); $i < $paginator->currentPage(); $i++)
        <li class="page-item">
            <a class="page-link" href="{{ $paginator->url($i) }}">{{ $i }}</a>
        </li>
    @endfor

    {{-- Página Actual --}}
    <li class="page-item active" aria-current="page">
        <span class="page-link">{{ $paginator->currentPage() }}</span>
    </li>

    {{-- Conteo de Páginas hacia Adelante --}}
    @for ($i = $paginator->currentPage() + 1; $i <= min($paginator->lastPage(), $paginator->currentPage() + 5); $i++)
        <li class="page-item">
            <a class="page-link" href="{{ $paginator->url($i) }}">{{ $i }}</a>
        </li>
    @endfor

    {{-- Botón Siguiente --}}
    @if ($paginator->hasMorePages())
        <li class="page-item">
            <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next"
                aria-label="Siguiente">&raquo;</a>
        </li>
    @else
        <li class="page-item disabled" aria-disabled="true">
            <span class="page-link" aria-hidden="true">&raquo;</span>
        </li>
    @endif

    {{-- Mostrar el conteo de páginas y la página actual --}}
    <li class="page-item disabled" aria-disabled="true">
        <span class="page-link">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>
    </li>
    <div>
        <li>


        </li>
    </div>
</ul>
