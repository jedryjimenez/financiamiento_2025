@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h2>Historial de Cajas</h2>

        <form class="row g-2 mb-3">
            <div class="col-auto">
                <label>Desde</label>
                <input type="date" name="start" value="{{ $start }}" class="form-control">
            </div>
            <div class="col-auto">
                <label>Hasta</label>
                <input type="date" name="end" value="{{ $end }}" class="form-control">
            </div>
            <div class="col-auto align-self-end">
                <button class="btn btn-primary">Filtrar</button>
            </div>
        </form>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Apertura</th>
                            <th>Cierre</th>
                            <th>Inicial</th>
                            <th>Final Sistema</th>
                            <th>Final Real</th>
                            <th>Diferencia</th>
                            <th>Obs.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cajas as $c)
                            <tr>
                                <td>{{ $c->user->name ?? 'N/A' }}</td>
                                <td>{{ $c->apertura_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $c->cierre_at->format('d/m/Y H:i') }}</td>
                                <td>C$ {{ number_format($c->monto_inicial, 2) }}</td>
                                <td>C$ {{ number_format($c->monto_final_sistema, 2) }}</td>
                                <td>C$ {{ number_format($c->monto_final_real, 2) }}</td>
                                <td class="{{ $c->diferencia < 0 ? 'text-danger' : 'text-success' }}">
                                    C$ {{ number_format($c->diferencia, 2) }}
                                </td>
                                <td>{{ Str::limit($c->observacion, 30) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center p-3">Sin registros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $cajas->links() }}
            </div>
        </div>
    </div>
@endsection