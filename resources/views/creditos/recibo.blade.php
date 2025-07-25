{{-- resources/views/creditos/recibo.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">

  {{-- DEBUG: ver qué trae $credito
  @php
    dd($credito->load('productor','detalles.insumo'));
  @endphp
  --}}

  <h1 class="mb-4">Recibo de Crédito</h1>

  {{-- Datos del cliente --}}
  <table class="table table-borderless mb-5">
    <tr>
      <th width="15%">Factura:</th>
      <td width="35%">{{ $credito->id }}</td>
      <th width="15%">Fecha:</th>
      <td width="35%">
        {{-- Casteado en el modelo o con Carbon::parse --}}
        {{ \Carbon\Carbon::parse($credito->fecha_entrega)->format('d/m/Y') }}
      </td>
    </tr>
    <tr>
      <th>Nombres y Apellidos:</th>
      <td>{{ $credito->productor->nombre }}</td>
      <th>Cédula:</th>
      <td>{{ $credito->productor->cedula }}</td>
    </tr>
    <tr>
      <th>Teléfono:</th>
      <td>{{ $credito->productor->telefono }}</td>
      <th>Dirección:</th>
      <td>{{ $credito->productor->direccion }}</td>
    </tr>
  </table>

  {{-- Detalles del crédito --}}
  <table class="table table-striped">
    <thead class="table-light">
      <tr>
        <th>Producto</th>
        <th>Cantidad</th>
        <th>Precio unitario (C$)</th>
        <th>Subtotal (C$)</th>
        <th>% Interés</th>
        <th>Días</th>
        <th>Interés acumulado (C$)</th>
        <th>Saldo línea (C$)</th>
      </tr>
    </thead>
    <tbody>
      @php
        $diasHoy       = (int) \Carbon\Carbon::parse($credito->fecha_entrega)->diffInDays(now());
        $subtotalC     = 0;
        $interesC      = 0;
        $totalC        = 0;
        $tasaCambio    = $tasaCambio ?? 1; // si lo pasas desde el controlador
        $subtotalUS    = 0;
        $interesUS     = 0;
        $totalUS       = 0;
      @endphp

      @foreach($credito->detalles as $d)
        @php
          // tasa mensual almacenada en % (ej. 3), la convertimos a diaria:
          $tasaDiaria   = ($d->interes / 30) / 100;
          // interés acumulado de hoy:
          $intAcumC     = round($d->subtotal * $tasaDiaria * $diasHoy, 2);
          // saldo de la línea (principal + acumulado):
          $saldoLineaC  = round($d->subtotal + $intAcumC, 2);
          // conversion USD:
          $montoUS      = round($d->subtotal / $tasaCambio, 2);
          $intAcumUS    = round($intAcumC / $tasaCambio, 2);
        @endphp

        <tr>
          <td>{{ $d->insumo->nombre }}</td>
          <td>{{ number_format($d->cantidad, 2) }}</td>
          <td>{{ number_format($d->precio_unitario, 2) }}</td>
          <td>{{ number_format($d->subtotal, 2) }}</td>
          <td>{{ number_format($d->interes, 2) }}%</td>
          <td>{{ $diasHoy }}</td>
          <td>{{ number_format($intAcumC, 2) }}</td>
          <td>{{ number_format($saldoLineaC, 2) }}</td>
        </tr>

        @php
          $subtotalC  += $d->subtotal;
          $interesC   += $intAcumC;
          $totalC     += $saldoLineaC;
          $subtotalUS += $montoUS;
          $interesUS  += $intAcumUS;
          $totalUS    += ($montoUS + $intAcumUS);
        @endphp
      @endforeach
    </tbody>
  </table>

  {{-- Resumen en C$ y US$ --}}
  <div class="row justify-content-end">
    <div class="col-md-4">
      <table class="table table-borderless">
        <tr>
          <th>Subtotal (C$):</th>
          <td class="text-end">{{ number_format($subtotalC, 2) }}</td>
        </tr>
        <tr>
          <th>Interés acumulado (C$):</th>
          <td class="text-end">{{ number_format($interesC, 2) }}</td>
        </tr>
        <tr class="fw-bold">
          <th>Total (C$):</th>
          <td class="text-end">{{ number_format($totalC, 2) }}</td>
        </tr>
        <tr><td colspan="2"></td></tr>
        <tr>
          <th>Subtotal (US$):</th>
          <td class="text-end">{{ number_format($subtotalUS, 2) }}</td>
        </tr>
        <tr>
          <th>Interés acum. (US$):</th>
          <td class="text-end">{{ number_format($interesUS, 2) }}</td>
        </tr>
        <tr class="fw-bold">
          <th>Total (US$):</th>
          <td class="text-end">{{ number_format($totalUS, 2) }}</td>
        </tr>
      </table>
    </div>
  </div>

</div>
@endsection
