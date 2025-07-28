@extends('layouts.app')
@section('content')
<div class="container">
    <h3>Kardex de Insumos</h3>

    {{-- Filtros --}}
    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto">
            <select name="insumo_id" class="form-select">
                <option value="">-- Todas las partidas --</option>
                @foreach($insumos as $id => $nombre)
                    <option value="{{ $id }}" @selected(request('insumo_id')==$id)>{{ $nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <input type="date" name="desde" value="{{ request('desde') }}" class="form-control">
        </div>
        <div class="col-auto">
            <input type="date" name="hasta" value="{{ request('hasta') }}" class="form-control">
        </div>
        <div class="col-auto">
            <button class="btn btn-primary">Filtrar</button>
        </div>
    </form>

    {{-- 📦 Stock disponible --}}
    @if(! is_null($stockActual))
        <div class="alert alert-info">
            <strong>Stock disponible:</strong> {{ number_format($stockActual, 2) }}
        </div>
    @endif

    {{-- Tabla de Movimientos --}}
    <table class="table table-sm table-bordered">
        <thead class="table-light">
            <tr>
                <th>Fecha</th>
                <th>Insumo</th>
                <th>Tipo</th>
                <th class="text-end">Cantidad</th>
                <th class="text-end">Precio Unit.</th>
                <th class="text-end">Total</th>
                <th>Ref.</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movimientos as $m)
                <tr>
                    <td>{{ $m->fecha->format('Y-m-d H:i') }}</td>
                    <td>{{ $m->insumo->nombre }}</td>
                    <td>{{ $m->tipo }}</td>
                    <td class="text-end">{{ number_format($m->cantidad,2) }}</td>
                    <td class="text-end">{{ number_format($m->precio_unitario,2) }}</td>
                    <td class="text-end">{{ number_format($m->total,2) }}</td>
                    <td>{{ $m->referencia }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No hay movimientos.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $movimientos->withQueryString()->links() }}
</div>
@endsection
