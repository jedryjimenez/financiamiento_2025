@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Historial de Recepciones</h2>

    <form method="GET" class="row g-3 mb-3">
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

    <div class="mb-3 d-flex gap-2">
        <a href="{{ route('recepciones.export.excel', request()->query()) }}" class="btn btn-success">Exportar Excel</a>
        <a href="{{ route('recepciones.export.pdf', request()->query()) }}" class="btn btn-danger">Exportar PDF</a>
    </div>

    <table class="table table-bordered table-striped">
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
            </tr>
        </thead>
        <tbody>
            @foreach($recepciones as $r)
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
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $recepciones->withQueryString()->links() }}
</div>
@endsection
