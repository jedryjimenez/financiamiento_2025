<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Estado de Cuenta General</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 4px; text-align: left; }
        th { background-color: #f0f0f0; }
    </style>
</head>
<body>
    <h2>Estado de Cuenta General</h2>
    <p>Desde: {{ $desde ? \Carbon\Carbon::parse($desde)->format('d/m/Y') : '---' }} | 
       Hasta: {{ $hasta ? \Carbon\Carbon::parse($hasta)->format('d/m/Y') : '---' }}</p>

    <table>
        <thead>
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
            @forelse ($productores as $p)
                <tr>
                    <td>{{ $p->nombre }}</td>
                    <td>{{ $p->creditos_activos }}</td>
                    <td>C$ {{ number_format($p->total_creditos, 2) }}</td>
                    <td>C$ {{ number_format($p->total_efectivo, 2) }}</td>
                    <td>C$ {{ number_format($p->total_producto, 2) }}</td>
                    <td>{{ $p->porcentaje_pagado }}%</td>
                    <td>C$ {{ number_format($p->saldo, 2) }}</td>
                    <td>{{ $p->ultimo_credito ? \Carbon\Carbon::parse($p->ultimo_credito)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $p->ultimo_abono ? \Carbon\Carbon::parse($p->ultimo_abono)->format('d/m/Y') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">No se encontraron resultados para el rango de fechas seleccionado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
