<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1em;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 4px;
        }

        th {
            background: #eee;
        }

        h2,
        h4 {
            margin: 0;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>

<body>
    <h2>Recepción de Insumos</h2>
    <p>
        <strong>Factura:</strong> {{ $r->numero_factura }}<br>
        <strong>Fecha:</strong> {{ $r->fecha_factura->format('d/m/Y') }}<br>
        <strong>Proveedor:</strong> {{ $r->proveedor->nombre }}<br>
        <strong>Tipo Pago:</strong> {{ ucfirst($r->tipo_pago) }}
    </p>

    <h4>Detalle de Ítems</h4>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Insumo</th>
                <th>Cant.</th>
                <th>Precio C.</th>
                <th>Precio V.</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($r->items as $i => $it)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $it->insumo->nombre }}</td>
                    <td>{{ $it->cantidad }}</td>
                    <td class="text-right">C$ {{ number_format($it->precio_compra, 2) }}</td>
                    <td class="text-right">C$ {{ number_format($it->precio_venta, 2) }}</td>
                    <td class="text-right">C$ {{ number_format($it->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="text-right">Total Factura</th>
                <th class="text-right">C$ {{ number_format($r->total_factura, 2) }}</th>
            </tr>
            @if($r->tipo_pago === 'credito')
                <tr>
                    <th colspan="5" class="text-right">Total Abonado</th>
                    <th class="text-right">C$ {{ number_format($r->abonado, 2) }}</th>
                </tr>
                <tr>
                    <th colspan="5" class="text-right">Saldo Pendiente</th>
                    <th class="text-right">C$ {{ number_format($r->saldo, 2) }}</th>
                </tr>
            @endif
        </tfoot>
    </table>

    <h4 class="mt-4">Historial de Abonos</h4>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Fecha Abono</th>
                <th>Comprobante</th>
                <th class="text-right">Monto</th>
            </tr>
        </thead>
        <tbody>
            @forelse($r->abonos as $i => $ab)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $ab->fecha_abono->format('d/m/Y') }}</td>
                    <td>{{ $ab->comprobante ?? '-' }}</td>
                    <td class="text-right">C$ {{ number_format($ab->monto, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">Sin abonos registrados</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($r->comprobante)
        <p style="margin-top:1em">
            <strong>Comprobante Digital:</strong><br>
            <img src="{{ storage_path('app/' . $r->comprobante) }}" style="max-width:200px; max-height:200px">
        </p>
    @endif
</body>

</html>