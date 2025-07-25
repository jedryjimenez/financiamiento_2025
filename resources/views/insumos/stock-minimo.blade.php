@extends('layouts.app')

@section('content')
    <div class="container py-5">
        <h2>Insumos con Stock Crítico</h2>
        <table class="table table-striped mt-4">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Stock Actual</th>
                    <th>Stock Mínimo</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                @forelse($insumos as $i)
                    <tr class="table-warning">
                        <td>{{ $i->nombre }}</td>
                        <td>{{ $i->stock }}</td>
                        <td>{{ $i->stock_minimo }}</td>
                        <td>
                            <a href="{{ route('insumos.edit', $i) }}" class="btn btn-sm btn-outline-primary">
                                Ajustar stock
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No hay insumos críticos.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection