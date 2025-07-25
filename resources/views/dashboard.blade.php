@extends('layouts.app')

@section('styles')
    <style>
        .kpi-card {
            border: none;
            border-radius: 4px;
        }

        .kpi-card .display-5 {
            font-weight: 400;
            margin: 0;
        }

        .kpi-card h5 {
            margin-bottom: .25rem;
            font-size: 1rem;
        }

        .module-card {
            transition: .2s
        }

        .module-card:hover {
            background: #f8f9fa;
            transform: translateY(-4px)
        }

        #creditChart {
            max-height: 260px
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid py-4 bg-light">

        {{-- KPIs --}}
        <div class="row g-4 mb-4">
            <x-kpi-card title="Créditos Activos" :value="$totalCreditos" icon="<i class='bi bi-currency-dollar fs-1'></i>"
                route="{{ route('creditos.index') }}" bgClass="bg-primary" />

            <x-kpi-card title="Total por Cobrar" :value="number_format($montoTotal, 2)"
                icon="<i class='bi bi-cash-stack fs-1'></i>" route="{{ route('creditos.index') }}" bgClass="bg-danger" />

            <x-kpi-card title="Productores" :value="$productoresActivos" icon="<i class='bi bi-people fs-1'></i>"
                route="{{ route('productores.index') }}" bgClass="bg-success" />

            <x-kpi-card title="Stock Crítico" :value="$insumosCriticos" icon="<i class='bi bi-box-seam fs-1'></i>"
                route="{{ route('insumos.index') }}" bgClass="bg-warning" />

            <x-kpi-card title="Ctas. por Pagar" :value="number_format($cuentasPorPagar, 2)"
                icon="<i class='bi bi-journal-arrow-down fs-1'></i>" route="{{ route('recepcion.index') }}"
                bgClass="bg-dark" />
        </div>
        {{-- Gráfico créditos por mes --}}
        <div class="card mb-5">
            <div class="card-header">Créditos Otorgados ({{ $year }})</div>
            <div class="card-body">
                <canvas id="creditChart" height="80"></canvas>
            </div>
        </div>

        {{-- Últimos créditos --}}
        <div class="card mb-5">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Últimos Créditos</span>
                <a href="{{ route('creditos.index') }}" class="btn btn-sm btn-primary">
                    Ver todos
                </a>
            </div>  
@endsection

        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                const ctx = document.getElementById('creditChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: {!! json_encode(array_map(fn($m) => DateTime::createFromFormat('!m', $m)->format('M'), $meses)) !!},
                        datasets: [{
                            label: 'Créditos',
                            data: {!! json_encode($datosCred) !!},
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13,110,253,.15)',
                            fill: true,
                            tension: .35,
                            pointRadius: 4,
                            pointBackgroundColor: '#0d6efd'
                        }]
                    },
                    options: {
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: '#eee' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            </script>
        @endpush