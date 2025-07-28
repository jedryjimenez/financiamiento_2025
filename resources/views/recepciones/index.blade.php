{{-- resources/views/recepciones/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-4">

    <h2 class="mb-4">Recepción de Productos como Pago de Crédito</h2>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Botón principal --}}
    <div class="d-flex gap-2 mb-4">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalRecepcion">
            + Nueva Recepción
        </button>
    </div>

    {{-- ================== HISTORIAL (VISIBLE) ================== --}}
    <h4 class="mb-3">Historial de Recepciones</h4>

    {{-- Filtros --}}
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="productor_id" class="form-select">
                <option value="">— Productor —</option>
                @foreach($productores as $p)
                    <option value="{{ $p->id }}" {{ request('productor_id') == $p->id ? 'selected' : '' }}>
                        {{ $p->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <input type="date" name="fecha_inicio" class="form-control" value="{{ request('fecha_inicio') }}">
        </div>
        <div class="col-md-3">
            <input type="date" name="fecha_fin" class="form-control" value="{{ request('fecha_fin') }}">
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Filtrar</button>
            <a href="{{ route('recepciones.index') }}" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>

    {{-- Exportar --}}
    <div class="mb-3 d-flex gap-2">
        <a href="{{ route('recepciones.export.excel', request()->query()) }}" class="btn btn-success">
            Exportar Excel
        </a>
        <a href="{{ route('recepciones.export.pdf', request()->query()) }}" class="btn btn-danger">
            Exportar PDF
        </a>
    </div>

    {{-- Tabla --}}
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
                <tr>
                    <th>Fecha</th>
                    <th>Productor</th>
                    <th>Producto</th>
                    <th>Cant. Bruta</th>
                    <th>Humedad</th>
                    <th>Cant. Neta</th>
                    <th>Total (C$)</th>
                    <th>Abono</th>
                    <th>Efectivo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recepciones as $r)
                    <tr>
                        <td>{{ $r->created_at->format('d/m/Y') }}</td>
                        <td>{{ $r->productor->nombre }}</td>
                        <td>{{ $r->producto }}</td>
                        <td>{{ number_format($r->cantidad_bruta, 2) }}</td>
                        <td>{{ $r->humedad }}%</td>
                        <td>{{ number_format($r->cantidad_neta, 2) }}</td>
                        <td>C$ {{ number_format($r->total_valor, 2) }}</td>
                        <td>C$ {{ number_format($r->abonado_credito, 2) }}</td>
                        <td>C$ {{ number_format($r->efectivo_pagado, 2) }}</td>
                        <td class="text-center">
            <a href="{{ route('recepciones.recibo', $r) }}"
               class="btn btn-sm btn-info mb-1">
                Ver Recibo
            </a>
        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center">Sin registros</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-2">
        {{ $recepciones->withQueryString()->links() }}
    </div>

</div>

<div class="modal fade" id="modalRecepcion" tabindex="-1" aria-labelledby="modalRecepcionLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" action="{{ route('recepciones.store') }}" id="formRecepcion">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="modalRecepcionLabel">Registrar Recepción</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <div class="modal-body">

          <div class="row g-3 mb-2">
            <div class="col-md-6">
              <label class="form-label">Productor</label>
              <select name="productor_id" id="productorSelect" class="form-select" required>
                <option value="">Seleccione</option>
                  @foreach($productores as $p)
                    <option value="{{ $p->id }}"
                      data-saldo="{{ number_format($p->saldo_actual,2,'.','') }}">
                        {{ $p->nombre }}
                    </option>
                  @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Saldo Pendiente (C$)</label>
              <input type="text" id="saldo" class="form-control" readonly>
            </div>
          </div>

          <div class="row g-3 mb-2">
            <div class="col-md-6">
              <label for="insumo_id" class="form-label">Producto</label>
              <select name="insumo_id" id="insumo_id" class="form-select" required>
                <option value="">-- Seleccione --</option>
                @foreach($insumos as $id => $nombre)
                  <option value="{{ $id }}" @selected(old('insumo_id') == $id)>{{ $nombre }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Precio por libra (C$)</label>
              <input type="number" step="0.01" name="precio_unitario" id="precio_unitario" class="form-control" required>
            </div>
          </div>

          <div class="row g-3 mb-2">
            <div class="col-md-6">
              <label class="form-label">Cantidad Bruta (lbs)</label>
              <input type="number" step="0.01" name="cantidad_bruta" id="cantidad_bruta" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Humedad (%)</label>
              <input type="number" step="0.01" name="humedad" id="humedad" class="form-control" value="0" required>
            </div>
          </div>

          {{-- Calculados --}}
          <div class="row g-3 mb-2">
            <div class="col-md-6">
              <label class="form-label">Cantidad Neta (lbs)</label>
              <input type="text" id="cantidad_neta_preview" class="form-control" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label">Total Valor (C$)</label>
              <input type="text" id="total_valor_preview" class="form-control" readonly>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Comentario (opcional)</label>
            <textarea name="comentario" class="form-control" rows="2"></textarea>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success">Registrar Recepción</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    // === Saldo del productor ===
    const productorSelect = document.getElementById('productorSelect');
    const saldoInput      = document.getElementById('saldo');

    if (productorSelect) {
        productorSelect.addEventListener('change', function () {
    const id = this.value;
    if (!id) { saldoInput.value = ''; return; }

    // fallback instantáneo
    const opt = this.options[this.selectedIndex];
    if (opt && opt.dataset.saldo) {
        saldoInput.value = `C$ ${parseFloat(opt.dataset.saldo).toFixed(2)}`;
    }

    // luego, confirma con el endpoint
    const url = "{{ route('productor.saldo', ':id') }}".replace(':id', id);
    fetch(url)
        .then(r => r.json())
        .then(d => saldoInput.value = `C$ ${parseFloat(d.saldo ?? 0).toFixed(2)}`)
        .catch(() => saldoInput.value = 'Error');
});
    }

    // === Cálculos en tiempo real ===
    const bruta   = document.getElementById('cantidad_bruta');
    const hum     = document.getElementById('humedad');
    const precio  = document.getElementById('precio_unitario');
    const netaP   = document.getElementById('cantidad_neta_preview');
    const totalP  = document.getElementById('total_valor_preview');

    function recalcular() {
        const b = parseFloat(bruta?.value)   || 0;
        const h = parseFloat(hum?.value)     || 0;
        const p = parseFloat(precio?.value)  || 0;

        const neta  = b * (1 - (h / 100));
        const total = neta * p;

        if (netaP)  netaP.value  = neta.toFixed(2);
        if (totalP) totalP.value = total.toFixed(2);
    }

    [bruta, hum, precio].forEach(el => el && el.addEventListener('input', recalcular));

    // Reabrir modal si hubo errores de validación
    @if ($errors->any())
        new bootstrap.Modal(document.getElementById('modalRecepcion')).show();
    @endif
});
</script>
@endpush
