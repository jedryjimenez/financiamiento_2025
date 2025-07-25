<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Detalles del Crédito #{{ $credito->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #222;
            padding: 5px;
            text-align: left;
        }

        th {
            background: #f0f0f0;
        }
    </style>
</head>

<body>
    <h2>Crédito #{{ $credito->id }}</h2>
    <p><strong>Productor:</strong> {{ $credito->productor->nombre }}</p>
    <p><strong>Fecha de Entrega:</strong> {{ \Carbon\Carbon::parse($credito->fecha_entrega)->format('d/m/Y') }}</p>
    <p><strong>Días transcurridos:</strong> {{ $dias }}</p>
    <hr>
    <h4>Detalles de Insumos</h4>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
                <th>Interés (%)</th>
                <th>Interés Acum.</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($credito->detalles as $d)
                @php
                    $tasaDia = ($d->interes / 30) / 100;
                    $intA = round($d->subtotal * $tasaDia * $dias, 2);
                    $saldoLn = round($d->subtotal + $intA, 2);
                @endphp
                <tr>
                    <td>{{ $d->insumo->nombre }}</td>
                    <td>{{ number_format($d->cantidad, 2) }}</td>
                    <td>C$ {{ number_format($d->precio_unitario, 2) }}</td>
                    <td>C$ {{ number_format($d->subtotal, 2) }}</td>
                    <td>{{ $d->interes }}</td>
                    <td>C$ {{ number_format($intA, 2) }}</td>
                    <td>C$ {{ number_format($saldoLn, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p><strong>Total Crédito:</strong> C$ {{ number_format($credito->total, 2) }}</p>
    <p><strong>Total Abonado:</strong> C$ {{ number_format($credito->abonado, 2) }}</p>
    <p><strong>Saldo Pendiente:</strong>
        @php
            $totalReal = $credito->detalles->reduce(function ($carry, $d) use ($dias) {
                $tasaDia = ($d->interes / 30) / 100;
                $intA = round($d->subtotal * $tasaDia * $dias, 2);
                return $carry + round($d->subtotal + $intA, 2);
            }, 0);
            $rawDifference = $totalReal - $credito->abonado;
            $realPend = max(0, round($rawDifference, 2));
        @endphp
        C$ {{ number_format($realPend, 2) }}
    </p>
    <hr>
    <h4>Abonos realizados</h4>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Monto</th>
                <th>Comentario</th>
            </tr>
        </thead>
        <tbody>
            @foreach($credito->abonos as $a)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($a->fecha)->format('d/m/Y') }}</td>
                    <td>C$ {{ number_format($a->monto, 2) }}</td>
                    <td>{{ $a->comentario }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>