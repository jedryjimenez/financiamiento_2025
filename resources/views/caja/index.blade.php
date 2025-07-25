@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h2>Caja</h2>

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div> @endif

        @if(!$caja)
            {{-- Formulario de apertura --}}
            <div class="card">
                <div class="card-body">
                    <h5>Abrir nueva caja</h5>
                    <form action="{{ route('caja.abrir') }}" method="POST" class="row g-3">
                        @csrf
                        <div class="col-md-4">
                            <label>Monto inicial</label>
                            <input type="number" step="0.01" name="monto_inicial" class="form-control" required>
                        </div>
                        <div class="col-md-2 align-self-end">
                            <button class="btn btn-primary">Abrir</button>
                        </div>
                    </form>
                </div>
            </div>
        @else
            {{-- Caja abierta --}}
            <div class="mb-3">
                <div class="card mb-3">
                    <div class="card-body d-flex justify-content-between">
                        <div>
                            <p class="mb-1"><strong>Apertura:</strong> {{ $caja->apertura_at->format('d/m/Y H:i') }}</p>
                            <p class="mb-1"><strong>Monto inicial:</strong> C$ {{ number_format($caja->monto_inicial, 2) }}</p>
                            <p class="mb-1"><strong>Ingresos:</strong> C$ {{ number_format($ingresos, 2) }}</p>
                            <p class="mb-0"><strong>Egresos:</strong> C$ {{ number_format($egresos, 2) }}</p>
                        </div>
                        <div class="text-end">
                            <p class="fs-5"><strong>Saldo Teórico:</strong><br> C$ {{ number_format($teorico, 2) }}</p>
                            <a href="{{ route('caja.cierre.form', $caja) }}" class="btn btn-danger">Cerrar caja</a>
                        </div>
                    </div>
                </div>

                {{-- Tabla movimientos --}}
                {{-- Tabla movimientos --}}
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Movimientos</span>

                        <form method="GET" class="d-flex align-items-center">
                            {{-- Mantener otros parámetros si lo deseas --}}
                            <label class="me-2 mb-0">Mostrar</label>
                            <select name="per_page" class="form-select form-select-sm me-2" onchange="this.form.submit()">
                                @foreach([10, 25, 50, 100] as $n)
                                    <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                                @endforeach
                            </select>
                            <span>registros</span>
                        </form>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Concepto</th>
                                        <th class="text-end">Monto</th>
                                        <th>Automático</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($movimientos as $m)
                                        <tr>
                                            <td>{{ $m->fecha->format('d/m/Y') }}</td>
                                            <td class="text-capitalize">{{ $m->tipo }}</td>
                                            <td>{{ $m->concepto }}</td>
                                            <td class="text-end">C$ {{ number_format($m->monto, 2) }}</td>
                                            <td>{{ $m->automatico ? 'Sí' : 'No' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center p-3">Sin movimientos.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="p-2">
                            {{ $movimientos->links() }}
                        </div>
                    </div>
                </div>

            </div>
        @endif
    </div>
@endsection