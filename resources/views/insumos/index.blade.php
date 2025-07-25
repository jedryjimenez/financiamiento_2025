{{-- resources/views/insumos/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Lista de Insumos</h2>
        <div>
            <a href="{{ route('insumos.stock_minimo') }}" class="btn btn-outline-warning me-2">Stock Mínimo</a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">Nuevo Insumo</button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Filtros --}}
    <form id="filterForm" class="row g-2 mb-3" onsubmit="return false;">
        <div class="col-sm-4">
            <input type="text" id="search" name="search" class="form-control" placeholder="Buscar por nombre...">
        </div>
        <div class="col-sm-3">
            <select name="per_page" id="per_page" class="form-select">
                @foreach([10,25,50,100] as $n)
                    <option value="{{ $n }}">{{ $n }} por Página</option>
                @endforeach
                <option value="all">Todos</option>
            </select>
        </div>
        <div class="col-sm-3">
            <a id="exportBtn" href="#" class="btn btn-success w-100">Exportar Excel</a>
        </div>
    </form>

    {{-- Contenedor de la tabla; se inyecta con AJAX usando esta MISMA vista --}}
    <div id="table-wrapper">
        <div class="text-center py-5 text-muted">Cargando...</div>
    </div>
</div>

{{-- MODAL: Crear --}}
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('insumos.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="createLabel">Nuevo Insumo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Unidad</label>
                        <input type="text" name="unidad" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Precio Compra</label>
                        <input type="number" step="0.01" name="precio_compra" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Precio Venta</label>
                        <input type="number" step="0.01" name="precio_venta" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Stock</label>
                        <input type="number" name="stock" class="form-control" value="0">
                    </div>
                    <div class="mb-3">
                        <label>Stock Mínimo</label>
                        <input type="number" name="stock_minimo" class="form-control" min="0" value="{{ old('stock_minimo', 0) }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL: Editar --}}
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editLabel">Editar Insumo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nombre *</label>
                        <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Unidad</label>
                        <input type="text" name="unidad" id="edit_unidad" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Precio Compra</label>
                        <input type="number" step="0.01" name="precio_compra" id="edit_precio_compra" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Precio Venta</label>
                        <input type="number" step="0.01" name="precio_venta" id="edit_precio_venta" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Stock</label>
                        <input type="number" name="stock" id="edit_stock" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Stock Mínimo</label>
                        <input type="number" name="stock_minimo" id="edit_stock_minimo" class="form-control" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Guardar cambios</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL: Eliminar --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form id="deleteForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteLabel">¿Eliminar insumo?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de eliminar a <strong id="delete_nombre"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">Sí, eliminar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

{{-- SECCIÓN SOLO PARA LA TABLA: Laravel la extrae en AJAX con renderSections --}}
@section('table')
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Unidad</th>
            <th>Precio Compra</th>
            <th>Precio Venta</th>
            <th>Stock</th>
            <th>Stock Mínimo</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($insumos as $i)
            <tr class="{{ $i->needs_reorder ? 'table-warning' : '' }}">
                <td>{{ $i->nombre }}</td>
                <td>{{ $i->unidad }}</td>
                <td>C$ {{ number_format($i->precio_compra, 2) }}</td>
                <td>C$ {{ number_format($i->precio_venta, 2) }}</td>
                <td>{{ $i->stock }}</td>
                <td>{{ $i->stock_minimo }}</td>
                <td>
                    <button class="btn btn-sm btn-warning btn-edit"
                            data-id="{{ $i->id }}"
                            data-nombre="{{ $i->nombre }}"
                            data-unidad="{{ $i->unidad }}"
                            data-precio_compra="{{ $i->precio_compra }}"
                            data-precio_venta="{{ $i->precio_venta }}"
                            data-stock="{{ $i->stock }}"
                            data-stock_minimo="{{ $i->stock_minimo }}"
                            data-bs-toggle="modal"
                            data-bs-target="#editModal">
                        Editar
                    </button>
                    <button class="btn btn-sm btn-danger btn-delete"
                            data-id="{{ $i->id }}"
                            data-nombre="{{ $i->nombre }}"
                            data-bs-toggle="modal"
                            data-bs-target="#deleteModal">
                        Eliminar
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center text-muted">No hay registros</td>
            </tr>
        @endforelse
    </tbody>
</table>

@if($insumos instanceof \Illuminate\Pagination\AbstractPaginator)
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted">
            Mostrando {{ $insumos->firstItem() }} a {{ $insumos->lastItem() }} de {{ $insumos->total() }} registros
        </small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                {{-- Prev --}}
                @if($insumos->onFirstPage())
                    <li class="page-item disabled"><span class="page-link">&laquo;</span></li>
                @else
                    <li class="page-item">
                        <a class="page-link ajax-page" href="{{ $insumos->previousPageUrl() }}" rel="prev">&laquo;</a>
                    </li>
                @endif

                {{-- Números alrededor --}}
                @foreach($insumos->getUrlRange(max(1, $insumos->currentPage()-2), min($insumos->lastPage(), $insumos->currentPage()+2)) as $page => $url)
                    <li class="page-item {{ $page == $insumos->currentPage() ? 'active' : '' }}">
                        <a class="page-link ajax-page" href="{{ $url }}">{{ $page }}</a>
                    </li>
                @endforeach

                {{-- Next --}}
                @if($insumos->hasMorePages())
                    <li class="page-item">
                        <a class="page-link ajax-page" href="{{ $insumos->nextPageUrl() }}" rel="next">&raquo;</a>
                    </li>
                @else
                    <li class="page-item disabled"><span class="page-link">&raquo;</span></li>
                @endif
            </ul>
        </nav>
    </div>
@endif
@endsection

@push('scripts')
<script>
    const wrapper   = document.getElementById('table-wrapper');
    const searchInp = document.getElementById('search');
    const perPage   = document.getElementById('per_page');
    const exportBtn = document.getElementById('exportBtn');

    let typingTimer;
    const delay = 300;

    function buildQuery() {
        const params = new URLSearchParams();
        const s = searchInp.value.trim();
        const p = perPage.value;

        if (s) params.append('search', s);
        if (p) params.append('per_page', p);

        return params.toString();
    }

    function loadTable(url = null) {
        const endpoint = url ?? ('{{ route('insumos.list') }}' + '?' + buildQuery());
        wrapper.innerHTML = '<div class="text-center py-5 text-muted">Cargando...</div>';

        fetch(endpoint)
            .then(r => {
                if(!r.ok) throw new Error('HTTP '+r.status);
                return r.json();
            })
            .then(({html}) => {
                wrapper.innerHTML = html;
                hookPaginationLinks();
                updateExportUrl();
            })
            .catch(err => {
                console.error(err);
                wrapper.innerHTML = '<div class="text-danger text-center py-5">Error al cargar datos ('+err.message+')</div>';
            });
    }

    function hookPaginationLinks() {
        wrapper.querySelectorAll('.ajax-page').forEach(a => {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                loadTable(this.getAttribute('href'));
            });
        });
    }

    function updateExportUrl() {
        const qs = buildQuery();
        exportBtn.href = '{{ route('insumos.export') }}' + (qs ? '?' + qs : '');
    }

    // Delegación para botones editar/eliminar
    document.addEventListener('click', function(e){
        if(e.target.classList.contains('btn-edit')){
            const b = e.target;
            const id = b.dataset.id;
            document.getElementById('edit_nombre').value        = b.dataset.nombre;
            document.getElementById('edit_unidad').value        = b.dataset.unidad ?? '';
            document.getElementById('edit_precio_compra').value = b.dataset.precio_compra ?? '';
            document.getElementById('edit_precio_venta').value  = b.dataset.precio_venta ?? '';
            document.getElementById('edit_stock').value         = b.dataset.stock ?? '';
            document.getElementById('edit_stock_minimo').value  = b.dataset.stock_minimo ?? '';
            document.getElementById('editForm').action = "{{ url('insumos') }}/" + id;
        }

        if(e.target.classList.contains('btn-delete')){
            const b = e.target;
            const id = b.dataset.id;
            document.getElementById('delete_nombre').textContent = b.dataset.nombre;
            document.getElementById('deleteForm').action = "{{ url('insumos') }}/" + id;
        }
    });

    // Eventos de búsqueda y per_page
    searchInp.addEventListener('keyup', function () {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => loadTable(), delay);
    });

    perPage.addEventListener('change', () => loadTable());

    // Arranque
    loadTable();
</script>
@endpush
