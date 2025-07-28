<style>
    .sidebar {
        width: 250px;
        background: var(--color-primary);
        color: #fff;
        padding-top: .5rem;
        flex-shrink: 0;
    }

    .sidebar a,
    .sidebar button {
        color: #fff;
        text-decoration: none;
    }

    .sidebar .menu-item {
        display: block;
        padding: .55rem 1rem;
        font-size: .9rem;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
    }

    .sidebar .menu-item:hover,
    .sidebar .active {
        background: rgba(255, 255, 255, .15);
    }

    .sidebar .submenu {
        display: none;
        padding-left: .5rem;
    }

    .sidebar .submenu.show {
        display: block;
    }

    .sidebar .submenu a {
        font-size: .8rem;
        padding: .4rem 1.5rem;
        display: block;
    }
</style>

<aside class="sidebar d-flex flex-column">
    <div class="px-3 mb-3 fw-bold">
        <i class="fa fa-building me-2"></i> {{ config('app.name', 'Laravel') }}
    </div>

    <a href="{{ route('dashboard') }}" class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <i class="fa fa-gauge me-2"></i> Panel de control
    </a>

    {{-- Gestión --}}
    <a href="{{ route('productores.index') }}" class="menu-item {{ request()->routeIs('productores.*') ? 'active' : '' }}">
        <i class="fa fa-people-group me-2"></i> Productores
    </a>
    <a href="{{ route('insumos.index') }}" class="menu-item {{ request()->routeIs('insumos.*') ? 'active' : '' }}">
        <i class="fa fa-boxes-stacked me-2"></i> Insumos
    </a>
    <a href="{{ route('creditos.index') }}" class="menu-item {{ request()->routeIs('creditos.*') ? 'active' : '' }}">
        <i class="fa fa-hand-holding-dollar me-2"></i> Créditos
    </a>
    <a href="{{ route('proveedores.index') }}" class="menu-item {{ request()->routeIs('proveedores.*') ? 'active' : '' }}">
        <i class="fa fa-truck me-2"></i> Proveedores
    </a>

    {{-- Recepciones --}}
    <button class="menu-item toggle-submenu" data-target="#menuRecepciones">
        <i class="fa fa-handshake-angle me-2"></i> Recepciones
        <i class="fa fa-chevron-down float-end small"></i>
    </button>
    <div id="menuRecepciones" class="submenu {{ request()->is('recepcion*') || request()->is('recepciones*') ? 'show' : '' }}">
        <a href="{{ route('recepcion.index') }}">Recepción Insumos</a>
        <a href="{{ route('recepciones.index') }}">Pago con Productos</a>
    </div>

    {{-- Caja --}}
    <button class="menu-item toggle-submenu" data-target="#menuCaja">
        <i class="fa fa-cash-register me-2"></i> Caja
        <i class="fa fa-chevron-down float-end small"></i>
    </button>
    <div id="menuCaja" class="submenu {{ request()->is('caja*') ? 'show' : '' }}">
        <a href="{{ route('caja.index') }}">Apertura / Corte de caja</a>
        <a href="{{ route('caja.historial') }}">Historial de caja</a>
    </div>

    {{-- Ventas --}}
    <button class="menu-item toggle-submenu" data-target="#menuVentas">
        <i class="fa fa-cart-shopping me-2"></i> Ventas
        <i class="fa fa-chevron-down float-end small"></i>
    </button>
    <div id="menuVentas" class="submenu {{ request()->is('posventa*') ? 'show' : '' }}">
        <a href="{{ route('posventa.create') }}">Punto de Venta</a>
        <a href="{{ route('posventa.index') }}">Listado Ventas</a>
    </div>

    {{-- Kardex --}}
    <button class="menu-item toggle-submenu" data-target="#menuKardex">
        <i class="fa fa-book me-2"></i> Kardex
        <i class="fa fa-chevron-down float-end small"></i>
    </button>
    <div id="menuKardex" class="submenu {{ request()->is('kardex*') ? 'show' : '' }}">
        <a href="{{ route('kardex.index') }}">Kardex de Productos</a>
    </div>

    {{-- Reportes --}}
    <button class="menu-item toggle-submenu" data-target="#menuReportes">
        <i class="fa fa-chart-line me-2"></i> Reportes
        <i class="fa fa-chevron-down float-end small"></i>
    </button>
    <div id="menuReportes" class="submenu {{ request()->is('estado-cuenta-general*') || request()->is('ficha-productor*') || request()->is('reportes/productores-creditos-activos*') ? 'show' : '' }}">
        <a href="{{ route('reportes.estado_cuenta_general') }}">Estado de Cuenta General</a>
        <a href="{{ route('reportes.ficha_productor') }}">Ficha Productor</a>
        <a href="{{ route('reportes.productores_creditos_activos') }}">Créditos Activos</a>
    </div>
</aside>
