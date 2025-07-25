{{-- resources/views/reportes/ficha_productor_pdf.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        /* Márgenes para DomPDF */
        @page { margin: 2cm; }

        /* Reset y tipografía */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #333;
            font-size: 12px;
        }

        /* Encabezado corporativo */
        header {
            background-color: #2E86C1;
            color: #fff;
            padding: 10px 20px;
            text-align: left;
        }
        header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: normal;
        }
        header p {
            margin: 4px 0 0;
            font-size: 10px;
            opacity: 0.8;
        }

        /* División */
        .divider {
            height: 2px;
            background-color: #ccc;
            margin: 15px 0;
        }

        /* Información del productor */
        .info {
            margin: 10px 0;
        }
        .info p {
            margin: 2px 0;
        }
        .info strong {
            width: 100px;
            display: inline-block;
        }

        /* Tablas */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        th, td {
            padding: 8px 10px;
            border: 1px solid #ccc;
        }
        th {
            background-color: #F2F4F4;
            color: #2E4053;
            font-weight: normal;
            text-align: left;
        }
        td.text-right {
            text-align: right;
        }
        tbody tr:nth-child(even) {
            background-color: #FBFCFC;
        }

        /* Títulos de sección */
        h4 {
            margin: 20px 0 6px;
            color: #1B4F72;
            font-size: 14px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 4px;
        }

        /* Pie de página */
        footer {
            position: fixed;
            bottom: 1cm;
            left: 2cm;
            right: 2cm;
            text-align: center;
            font-size: 10px;
            color: #888;
            border-top: 1px solid #ccc;
            padding-top: 4px;
        }
    </style>
</head>
<body>

    <header>
        <h1>Empresa S.A.</h1>
        <p>Dirección · Teléfono · www.empresa.com</p>
    </header>

    <div class="info">
        <p><strong>Productor:</strong> {{ $productor->nombre }}</p>
        <p><strong>Cédula:</strong> {{ $productor->cedula ?? 'NO REGISTRADA' }}</p>
        <p><strong>Periodo:</strong> --- al ---</p>
        <p><strong>Tipo Movimiento:</strong> Todos</p>
    </div>

    <div class="divider"></div>

    <h4>Resumen Financiero</h4>
    <table>
        <thead>
            <tr>
                <th>Concepto</th>
                <th class="text-right">Monto (C$)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Principal</td>
                <td class="text-right">{{ number_format($principal, 2) }}</td>
            </tr>
            <tr>
                <td>Interés Acumulado</td>
                <td class="text-right">{{ number_format($interes, 2) }}</td>
            </tr>
            <tr>
                <td>Total</td>
                <td class="text-right">{{ number_format($total, 2) }}</td>
            </tr>
            <tr>
                <td>Abono</td>
                <td class="text-right">{{ number_format($abono, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Pendiente</strong></td>
                <td class="text-right">{{ number_format($pendiente, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <h4>Historial Financiero</h4>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Descripción</th>
                <th class="text-right">Monto (C$)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($historial as $item)
                <tr>
                    <td>{{ $item['fecha'] }}</td>
                    <td>{{ $item['tipo'] }}</td>
                    <td>{{ $item['descripcion'] }}</td>
                    <td class="text-right">{{ number_format($item['monto'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <footer>
        Página <script type="text/php">
            if (isset($pdf)) { echo $pdf->get_page_number().' / '.$pdf->get_page_count(); }
        </script>
    </footer>

</body>
</html>
