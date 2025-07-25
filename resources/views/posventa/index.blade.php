{{-- resources/views/posventa/index.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h2>Ventas</h2>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Modo</th>
                    <th>Estado</th>
                    <th style="width:120px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ventas as $v)
                    <tr>
                        <td>{{ $v->id }}</td>
                        <td>{{ $v->fecha->format('d/m/Y') }}</td>
                        <td>{{ $v->productor->nombre ?? 'Público' }}</td>
                        <td>C$ {{ number_format($v->total, 2) }}</td>
                        <td>
                            @if($v->es_credito)
                                <span class="badge bg-info">Crédito</span>
                            @else
                                <span class="badge bg-secondary">Contado</span>
                            @endif
                        </td>
                        <td>
                            @if($v->anulada)
                                <span class="badge bg-danger">Anulada</span>
                            @else
                                <span class="badge bg-success">OK</span>
                            @endif
                        </td>
                        <td>
                            @if(!$v->anulada && !$v->es_credito)
                                <form action="{{ route('posventa.anular', $v) }}" method="POST"
                                    onsubmit="return confirm('¿Anular esta venta?');">
                                    @csrf
                                    <button class="btn btn-sm btn-danger">Anular</button>
                                </form>
                            @elseif($v->es_credito)
                                <small class="text-muted">Gestionar en Créditos</small>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $ventas->links() }}
    </div>
@endsection