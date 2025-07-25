{{-- resources/views/recepciones/recibo.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo {{ str_pad($recepcion->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        /* Márgenes para DomPDF */
        @page { margin: 1cm; }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            padding: 0; 
            margin: 0 auto;
        }

        header {
            text-align: center;
            margin-bottom: 10px;
        }
        .company-name {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        .company-info {
            margin: 4px 0 8px;
            line-height: 1.2;
        }
        .divider {
            border-top: 1px solid #333;
            margin: 8px 0;
        }

        .meta, .details {
            width: 100%;
            margin-bottom: 10px;
        }
        .meta div {
            margin: 2px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            padding: 4px 6px;
            text-align: left;
        }
        .total-row th, .total-row td {
            border-top: 1px solid #333;
            font-weight: bold;
            padding-top: 6px;
        }

        .note {
            margin: 10px 0;
            font-size: 11px;
        }

        footer {
            text-align: center;
            margin-top: 12px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">

        <header>
            <h2 class="company-name">Raul Lazo</h2>
            <p class="company-info">
                Dirección: Contiguo a Farmacia El Ahorro<br>
                Tel: (505) 8888 - 8888
            </p>
            <div class="divider"></div>
        </header>

        <div class="meta">
            <div><strong>Fecha:</strong> {{ $recepcion->created_at->format('d/m/Y H:i') }}</div>
            <div><strong>Recibo #:</strong> {{ str_pad($recepcion->id, 6, '0', STR_PAD_LEFT) }}</div>
            <div><strong>Productor:</strong> {{ $recepcion->productor->nombre }}</div>
        </div>

        <div class="details">
            <table>
                <tr>
                    <th>Producto</th>
                    <td>{{ $recepcion->producto }}</td>
                </tr>
                <tr>
                    <th>Bruta (lbs)</th>
                    <td>{{ number_format($recepcion->cantidad_bruta,2) }}</td>
                </tr>
                <tr>
                    <th>Humedad (%)</th>
                    <td>{{ number_format($recepcion->humedad,2) }}</td>
                </tr>
                <tr>
                    <th>Neta (lbs)</th>
                    <td>{{ number_format($recepcion->cantidad_neta,2) }}</td>
                </tr>
                <tr>
                    <th>Precio/Lb</th>
                    <td>C$ {{ number_format($recepcion->precio_unitario,2) }}</td>
                </tr>
                <tr class="total-row">
                    <th>Total Valor</th>
                    <td>C$ {{ number_format($recepcion->total_valor,2) }}</td>
                </tr>
                <tr>
                    <th>Abono Crédito</th>
                    <td>C$ {{ number_format($recepcion->abonado_credito,2) }}</td>
                </tr>
                <tr>
                    <th>Efectivo Pagado</th>
                    <td>C$ {{ number_format($recepcion->efectivo_pagado,2) }}</td>
                </tr>
            </table>
        </div>

        @if($recepcion->comentario)
        <div class="note">
            <strong>Comentario:</strong><br>
            <em>{{ $recepcion->comentario }}</em>
        </div>
        @endif

        <footer>
            ¡Gracias por su preferencia!
        </footer>
    </div>
</body>
</html>
