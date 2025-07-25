<?php

// resources/views/reportes/estado_cuenta_general.blade.php
?>
@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Estado de Cuenta General</h3>

    <form method="GET" class="row g-3 mb-3">
        <div class="col-md-3">
            <label>Desde</label>
            <input type="date" name="desde" value="{{ request('desde') }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label>Hasta</label>
            <input type="date" name="hasta" value="{{ request('hasta') }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label>Nombre del Productor</label>
            <input type="text" id="filtro-nombre" name="nombre" value="{{ request('nombre') }}" class="form-control" placeholder="Buscar por nombre">
        </div>
        <div class="col-md-3 align-self-end">
            <button class="btn btn-primary">Filtrar</button>
            <a href="{{ route('reportes.estado_cuenta_general.pdf', request()->all()) }}" class="btn btn-danger">PDF</a>
            <a href="{{ route('estadoCuentaGeneral.export.excel') }}" class="btn btn-success">Exportar a Excel</a>
        </div>
    </form>

    <table class="table table-bordered table-striped" id="tabla-productores">
        <thead class="table-light">
            <tr>
                <th>Productor</th>
                <th># Créditos Activos</th>
                <th>Total Créditos</th>
                <th>Abonado Efectivo</th>
                <th>Abonado Producto</th>
                <th>% Pagado</th>
                <th>Saldo</th>
                <th>Últ. Crédito</th>
                <th>Últ. Abono</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($productores as $p)
                <tr>
                    <td>{{ $p->nombre }}</td>
                    <td>{{ $p->creditos_activos ?? '-' }}</td>
                    <td>C$ {{ number_format($p->total_creditos ?? 0, 2) }}</td>
                    <td>C$ {{ number_format($p->total_efectivo ?? 0, 2) }}</td>
                    <td>C$ {{ number_format($p->total_producto ?? 0, 2) }}</td>
                    <td>{{ $p->porcentaje_pagado ?? 0 }}%</td>
                    <td class="{{ ($p->saldo ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                        C$ {{ number_format($p->saldo ?? 0, 2) }}
                    </td>
                    <td>{{ $p->ultimo_credito ? \Carbon\Carbon::parse($p->ultimo_credito)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $p->ultimo_abono ? \Carbon\Carbon::parse($p->ultimo_abono)->format('d/m/Y') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
    document.getElementById('filtro-nombre').addEventListener('keyup', function () {
        const filtro = this.value.toLowerCase();
        const filas = document.querySelectorAll('#tabla-productores tbody tr');

        filas.forEach(fila => {
            const nombre = fila.children[0].textContent.toLowerCase();
            fila.style.display = nombre.includes(filtro) ? '' : 'none';
        });
    });
</script>
@endsection
