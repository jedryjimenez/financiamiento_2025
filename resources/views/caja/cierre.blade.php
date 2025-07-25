@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h2>Cerrar Caja</h2>

        <div class="card mb-4">
            <div class="card-body">
                <p><strong>Apertura:</strong> {{ $caja->apertura_at->format('d/m/Y H:i') }}</p>
                <p><strong>Monto inicial:</strong> C$ {{ number_format($caja->monto_inicial, 2) }}</p>
                <p><strong>Ingresos:</strong> C$ {{ number_format($ingresos, 2) }}</p>
                <p><strong>Egresos:</strong> C$ {{ number_format($egresos, 2) }}</p>
                <p class="fs-5"><strong>Saldo:</strong> C$ {{ number_format($teorico, 2) }}</p>
            </div>
        </div>

        <form action="{{ route('caja.cerrar', $caja) }}" method="POST" class="card">
            @csrf
            <div class="card-header">Datos de cierre</div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label>Monto contado (real)</label>
                    <input type="number" step="0.01" name="monto_final_real" class="form-control" required>
                </div>
                <div class="col-md-8">
                    <label>Observación (opcional)</label>
                    <textarea name="observacion" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('caja.index') }}" class="btn btn-secondary">Cancelar</a>
                <button class="btn btn-danger">Cerrar Caja</button>
            </div>
        </form>
    </div>
@endsection