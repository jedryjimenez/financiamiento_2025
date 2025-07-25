{{-- resources/views/reportes/ficha_productor.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
  <h3>Ficha de Productores</h3>

  {{-- Buscador --}}
  <div class="mb-3">
    <input type="text"
           id="producerFilter"
           class="form-control"
           placeholder="Filtrar por nombre...">
  </div>

  {{-- Tabla de productores --}}
  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle" id="producerTable">
      <thead class="table-light">
        <tr>
          <th>Nombre</th>
          <th>Cédula</th>
          <th class="text-center">Total Créditos</th>
          <th class="text-center">Pendientes</th>
          <th class="text-end">Saldo Actual (C$)</th>
          <th class="text-center">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @foreach($productores as $p)
          @php
            $totalCreditos      = $p->creditos->count();
            $pendientes         = $p->creditos
                                    ->filter(fn($c) => \App\Support\CreditoHelper::saldoCreditoPorDias($c) > 0)
                                    ->count();
            $saldoActual        = $p->creditos
                                    ->sum(fn($c) => \App\Support\CreditoHelper::saldoCreditoPorDias($c));
          @endphp
          <tr data-name="{{ strtolower($p->nombre) }}">
            <td>{{ $p->nombre }}</td>
            <td>{{ $p->cedula ?: 'NO REGISTRADA' }}</td>
            <td class="text-center">{{ $totalCreditos }}</td>
            <td class="text-center">{{ $pendientes }}</td>
            <td class="text-end">C$ {{ number_format($saldoActual, 2) }}</td>
            <td class="text-center">
              <a href="{{ route('reportes.ficha_productor_pdf', $p->id) }}"
                 class="btn btn-sm btn-info"
                 target="_blank">
                Ver Ficha
              </a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('producerFilter');
    const rows  = document.querySelectorAll('#producerTable tbody tr');

    input.addEventListener('input', () => {
      const term = input.value.toLowerCase();
      rows.forEach(row => {
        const name = row.dataset.name;
        row.style.display = name.includes(term) ? '' : 'none';
      });
    });
  });
</script>
@endpush
