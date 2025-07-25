<?php

// resources/views/reportes/productores_creditos_activos.blade.php
?>
@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">Productores con más de un Crédito Activo</h3>

    <div class="mb-3 d-flex justify-content-between">
        <form method="GET" class="d-flex">
            <input type="text" name="nombre" value="{{ request('nombre') }}" placeholder="Buscar por nombre" class="form-control me-2">
            <button type="submit" class="btn btn-primary">Buscar</button>
        </form>
        <div>
            <a href="{{ route('reportes.productores_creditos_activos.pdf') }}" class="btn btn-danger">Exportar PDF</a>
            <a href="{{ route('reportes.productores_creditos_activos.excel') }}" class="btn btn-success">Exportar Excel</a>
        </div>
    </div>

    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>Nombre</th>
                <th>Cédula</th>
                <th>Teléfono</th>
                <th># Créditos Activos</th>
                <th>Total Deuda (C$)</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($productores as $p)
                <tr>
                    <td>{{ $p->nombre }}</td>
                    <td>{{ $p->cedula ?? 'NO REGISTRADA' }}</td>
                    <td>{{ $p->telefono ?? 'NO REGISTRADO' }}</td>
                    <td>{{ $p->creditos_activos }}</td>
                    <td>{{ number_format($p->total_deuda, 2) }}</td>
                    <td>
                        <a href="{{ route('reportes.ficha_productor_pdf', ['productor_id' => $p->id]) }}" class="btn btn-sm btn-info" target="_blank">Ver Ficha</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No se encontraron productores con más de un crédito activo.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection