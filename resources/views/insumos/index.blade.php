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

    <form id="filterForm" class="row g-2 mb-3" onsubmit="return false;">
        <div class="col-sm-4">
            <input type="text" id="search" class="form-control" placeholder="Buscar por nombre...">
        </div>
        <div class="col-sm-3">
            <select id="per_page" class="form-select">
                @foreach([10,25,50,100] as $n)
                    <option value="{{ $n }}">{{ $n }} por página</option>
                @endforeach
                <option value="all">Todos</option>
            </select>
        </div>
        <div class="col-sm-3">
            <a id="exportBtn" href="#" class="btn btn-success w-100">Exportar Excel</a>
        </div>
    </form>

    <div id="table-wrapper">
        <div class="text-center py-5 text-muted">Cargando...</div>
    </div>
</div>

{{-- Crear Insumo --}}
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form action="{{ route('insumos.store') }}" method="POST" class="modal-content">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Nuevo Insumo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="text" name="nombre" class="form-control mb-3" placeholder="Nombre *" required>
        <input type="text" name="unidad" class="form-control mb-3" placeholder="Unidad">
        <input type="number" step="0.01" name="precio_compra" class="form-control mb-3" placeholder="Precio Compra">
        <input type="number" step="0.01" name="precio_venta" class="form-control mb-3" placeholder="Precio Venta">
        <input type="number" name="stock" class="form-control mb-3" value="0" placeholder="Stock">
        <input type="number" name="stock_minimo" class="form-control" min="0" value="0" placeholder="Stock Mínimo">
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Guardar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

{{-- Editar Insumo --}}
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="editForm" method="POST" class="modal-content">
      @csrf @method('PUT')
      <div class="modal-header">
        <h5 class="modal-title">Editar Insumo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="text" name="nombre" id="edit_nombre" class="form-control mb-3" placeholder="Nombre *" required>
        <input type="text" name="unidad" id="edit_unidad" class="form-control mb-3" placeholder="Unidad">
        <input type="number" step="0.01" name="precio_compra" id="edit_precio_compra" class="form-control mb-3" placeholder="Precio Compra">
        <input type="number" step="0.01" name="precio_venta" id="edit_precio_venta" class="form-control mb-3" placeholder="Precio Venta">
        <input type="number" name="stock" id="edit_stock" class="form-control mb-3" placeholder="Stock">
        <input type="number" name="stock_minimo" id="edit_stock_minimo" class="form-control" min="0" placeholder="Stock Mínimo">
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Guardar cambios</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

{{-- Eliminar Insumo --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <form id="deleteForm" method="POST" class="modal-content">
      @csrf @method('DELETE')
      <div class="modal-header">
        <h5 class="modal-title">Eliminar Insumo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>¿Eliminar <strong id="delete_nombre"></strong>?</p>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-danger">Sí, eliminar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>
@endsection

@section('table')
    <div class="mb-3 d-flex justify-content-end gap-4">
        <div>
            <strong>Total Inventario (Costo de Compra):</strong>
            C$ {{ number_format($totalCompra  ?? 0, 2) }}
        </div>
        <div>
            <strong>Total Inventario (Precio de Venta):</strong>
            C$ {{ number_format($totalVenta   ?? 0, 2) }}
        </div>
    </div>

    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>Nombre</th>
                <th>Unidad</th>
                <th class="text-end">Precio Compra</th>
                <th class="text-end">Precio Venta</th>
                <th class="text-end">Stock</th>
                <th class="text-end">Stock Mínimo</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($insumos as $i)
                <tr class="{{ $i->needs_reorder ? 'table-warning' : '' }}">
                    <td>{{ $i->nombre }}</td>
                    <td>{{ $i->unidad }}</td>
                    <td class="text-end">C$ {{ number_format($i->precio_compra, 2) }}</td>
                    <td class="text-end">C$ {{ number_format($i->precio_venta, 2) }}</td>
                    <td class="text-end">{{ number_format($i->stock, 2) }}</td>
                    <td class="text-end">{{ number_format($i->stock_minimo, 2) }}</td>
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
                <tr><td colspan="7" class="text-center text-muted">No hay registros</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($insumos instanceof \Illuminate\Pagination\AbstractPaginator)
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <small class="text-muted">
                Mostrando {{ $insumos->firstItem() }}‑{{ $insumos->lastItem() }} de {{ $insumos->total() }} registros
            </small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    @if($insumos->onFirstPage())
                        <li class="page-item disabled"><span class="page-link">&laquo;</span></li>
                    @else
                        <li class="page-item">
                            <a class="page-link ajax-page" href="{{ $insumos->previousPageUrl() }}" rel="prev">&laquo;</a>
                        </li>
                    @endif

                    @foreach($insumos->getUrlRange(
                        max(1, $insumos->currentPage()-2),
                        min($insumos->lastPage(), $insumos->currentPage()+2)
                    ) as $page => $url)
                        <li class="page-item {{ $page == $insumos->currentPage() ? 'active' : '' }}">
                            <a class="page-link ajax-page" href="{{ $url }}">{{ $page }}</a>
                        </li>
                    @endforeach

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
const wrapper = document.getElementById('table-wrapper'),
      searchInp = document.getElementById('search'),
      perPage   = document.getElementById('per_page'),
      exportBtn = document.getElementById('exportBtn');
let typingTimer, delay = 300;

function buildQuery() {
    let params = new URLSearchParams();
    if (searchInp.value.trim()) params.append('search', searchInp.value.trim());
    if (perPage.value) params.append('per_page', perPage.value);
    return params.toString();
}

function loadTable(url = null) {
    let endpoint = url
        ? url
        : `{{ route('insumos.list') }}?${buildQuery()}`;
    wrapper.innerHTML = '<div class="text-center py-5 text-muted">Cargando...</div>';

    fetch(endpoint)
        .then(r => r.ok ? r.json() : Promise.reject(r.statusText))
        .then(({html}) => {
            wrapper.innerHTML = html;
            wrapper.querySelectorAll('.ajax-page').forEach(a =>
                a.addEventListener('click', e => {
                    e.preventDefault(); loadTable(a.href);
                })
            );
            exportBtn.href = '{{ route('insumos.export') }}' +
                (buildQuery() ? ('?' + buildQuery()) : '');
        })
        .catch(err => {
            console.error(err);
            wrapper.innerHTML = `<div class="text-danger text-center py-5">Error: ${err}</div>`;
        });
}

searchInp.addEventListener('keyup', () => {
    clearTimeout(typingTimer);
    typingTimer = setTimeout(loadTable, delay);
});
perPage.addEventListener('change', loadTable);

document.addEventListener('click', e => {
    if (e.target.classList.contains('btn-edit')) {
        let b = e.target;
        document.getElementById('edit_nombre').value        = b.dataset.nombre;
        document.getElementById('edit_unidad').value        = b.dataset.unidad;
        document.getElementById('edit_precio_compra').value = b.dataset.precio_compra;
        document.getElementById('edit_precio_venta').value  = b.dataset.precio_venta;
        document.getElementById('edit_stock').value         = b.dataset.stock;
        document.getElementById('edit_stock_minimo').value  = b.dataset.stock_minimo;
        document.getElementById('editForm').action = "{{ url('insumos') }}/" + b.dataset.id;
    }
    if (e.target.classList.contains('btn-delete')) {
        let b = e.target;
        document.getElementById('delete_nombre').textContent = b.dataset.nombre;
        document.getElementById('deleteForm').action = "{{ url('insumos') }}/" + b.dataset.id;
    }
});

loadTable();
</script>
@endpush
