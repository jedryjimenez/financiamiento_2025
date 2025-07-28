{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    {{-- Iconos FontAwesome (o puedes usar Bootstrap Icons) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>
        .layout-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
        }
    </style>
</head>

<body>
    <body>
    <div class="layout-wrapper">
        {{-- Sidebar solo si el usuario está autenticado --}}
        @auth
            @include('partials.sidebar')
        @endauth

        {{-- Contenido principal --}}
        <div class="main-content">

            {{-- Mostrar alerta solo a usuarios autenticados --}}
            @auth
                @if(isset($alertasStockCritico) && $alertasStockCritico->isNotEmpty())
                    <div id="stockAlert" class="container-fluid mt-3 alert alert-warning alert-dismissible fade"
                        role="alert" style="display:none; opacity:0; transition: opacity .6s;">
                        <strong>¡Atención!</strong>
                        Hay {{ $alertasStockCritico->count() }} insumos con stock por debajo del mínimo.
                        <ul class="mt-2 mb-2">
                            @foreach($alertasStockCritico as $insumo)
                                <li>
                                    {{ $insumo->nombre }}: {{ $insumo->stock }} (mín. {{ $insumo->stock_minimo }})
                                    <a href="{{ route('insumos.stock_minimo') }}" class="alert-link">(Ver todos)</a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-2">
                            <a href="#" id="noShowStockAlert" class="small text-decoration-underline">
                                No volver a mostrar
                            </a>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                @endif
            @endauth

            <div class="container mt-4">
                @yield('content')
            </div>
        </div>
    </div>


    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Mostrar/Ocultar alerta --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const alertEl = document.getElementById('stockAlert');
            if (!alertEl || localStorage.getItem('noShowStockAlert')) return;
            alertEl.style.display = 'block';
            requestAnimationFrame(() => alertEl.style.opacity = '1');
            document.getElementById('noShowStockAlert').addEventListener('click', e => {
                e.preventDefault();
                localStorage.setItem('noShowStockAlert', 'true');
                alertEl.style.opacity = '0';
                setTimeout(() => alertEl.style.display = 'none', 600);
            });
        });
    </script>

    {{-- Toggle submenús --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.toggle-submenu').forEach(btn => {
                btn.addEventListener('click', e => {
                    e.preventDefault();
                    const target = document.querySelector(btn.dataset.target);
                    target.classList.toggle('show');
                });
            });
        });
    </script>

    @stack('scripts')
    @yield('scripts')
</body>

</html>