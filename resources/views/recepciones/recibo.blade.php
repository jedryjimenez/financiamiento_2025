{{-- resources/views/recepciones/recibo.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="voucher">
    <header class="voucher-header">
        {{-- Puedes reemplazar con tu logo --}}
        <h2 class="company-name">Raul Lazo</h2>
        <p class="company-info">
            Dirección: Contiguo a Farmacia El Ahorro<br>
            Tel: (505) 8888 - 8888
        </p>
        <div class="divider"></div>
    </header>

    <section class="voucher-meta">
        <div><strong>Fecha:</strong> {{ $recepcion->created_at->format('d/m/Y H:i') }}</div>
        <div><strong>Recibo #:</strong> {{ str_pad($recepcion->id, 6, '0', STR_PAD_LEFT) }}</div>
        <div><strong>Productor:</strong> {{ $recepcion->productor->nombre }}</div>
    </section>

    <section class="voucher-body">
        <table class="voucher-table">
            <tr>
                <th align="left">Producto</th>
                <td align="right">{{ $recepcion->producto }}</td>
            </tr>
            <tr>
                <th align="left">Bruta (lbs)</th>
                <td align="right">{{ number_format($recepcion->cantidad_bruta,2) }}</td>
            </tr>
            <tr>
                <th align="left">Humedad (%)</th>
                <td align="right">{{ number_format($recepcion->humedad,2) }}</td>
            </tr>
            <tr>
                <th align="left">Neta (lbs)</th>
                <td align="right">{{ number_format($recepcion->cantidad_neta,2) }}</td>
            </tr>
            <tr>
                <th align="left">Precio/Lb</th>
                <td align="right">C$ {{ number_format($recepcion->precio_unitario,2) }}</td>
            </tr>
            <tr class="total-row">
                <th align="left">Total Valor</th>
                <td align="right">C$ {{ number_format($recepcion->total_valor,2) }}</td>
            </tr>
            <tr>
                <th align="left">Abono Crédito</th>
                <td align="right">C$ {{ number_format($recepcion->abonado_credito,2) }}</td>
            </tr>
            <tr>
                <th align="left">Efectivo Pagado</th>
                <td align="right">C$ {{ number_format($recepcion->efectivo_pagado,2) }}</td>
            </tr>
        </table>
    </section>

    @if($recepcion->comentario)
    <section class="voucher-note">
        <strong>Comentario:</strong><br>
        <em>{{ $recepcion->comentario }}</em>
    </section>
    @endif

    <footer class="voucher-footer">
        <div class="divider"></div>
        <p class="thanks">¡Gracias por su preferencia!</p>
    </footer>
</div>
@endsection

@section('styles')
<style>
    /* Base del voucher */
    .voucher {
        width: 80mm;
        margin: auto;
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #000;
        padding: 8px;
    }
    /* Header */
    .voucher-header { text-align: center; }
    .company-name {
        margin: 0;
        font-size: 16px;
        font-weight: bold;
    }
    .company-info {
        margin: 4px 0 8px;
        line-height: 1.2;
    }
    .divider {
        border-top: 1px dashed #333;
        margin: 8px 0;
    }
    /* Metadatos */
    .voucher-meta div {
        margin: 2px 0;
    }
    /* Tabla */
    .voucher-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
    }
    .voucher-table th,
    .voucher-table td {
        padding: 4px 0;
    }
    .voucher-table .total-row th,
    .voucher-table .total-row td {
        font-weight: bold;
        border-top: 1px solid #333;
        margin-top: 4px;
        padding-top: 6px;
    }
    /* Nota */
    .voucher-note {
        margin: 8px 0;
        font-size: 11px;
    }
    /* Footer */
    .voucher-footer {
        text-align: center;
        margin-top: 12px;
    }
    .thanks {
        margin: 4px 0 0;
        font-style: italic;
        font-size: 12px;
    }
    /* Ajustes de impresión */
    @media print {
        body, .voucher {
            margin: 0;
            padding: 0;
            box-shadow: none;
        }
        .voucher {
            font-size: 11px;
        }
    }
</style>
@endsection
