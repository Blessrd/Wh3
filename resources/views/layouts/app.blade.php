<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>WH3 Reportes</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet">

    <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    <link rel="icon" href="{{ asset('logo_wh3.png') }}">
</head>

<body>

<div class="dashboard-layout">

    <aside class="sidebar">

        <div class="sidebar-header">
            <div class="logo-box">
                <img src="{{ asset('logo_wh3.png') }}" alt="WH3">
            </div>
            <h3>Sistema WH3</h3>
        </div>

        @auth
        <div class="sidebar-user">
            <div class="sidebar-user-icon">
                <i class="fa-solid fa-user-tie"></i>
            </div>
            <div class="sidebar-user-info">
                <strong>{{ auth()->user()->name }}</strong>
                <small>{{ auth()->user()->role->name ?? 'Usuario' }}</small>
            </div>
        </div>
        @endauth

        <nav class="sidebar-nav">

            <a href="/resumen-paletas"
               class="nav-item {{ request()->is('resumen-paletas*') ? 'active':'' }}">
                <i class="fa-solid fa-boxes-stacked"></i>
                Resumen Paletas
            </a>

            <a href="/distribucion-lotes"
               class="nav-item {{ request()->is('distribucion-lotes*') ? 'active':'' }}">
                <i class="fa-solid fa-table-cells-large"></i>
                Distribución Lotes
            </a>

            <a href="/planificador"
               class="nav-item {{ request()->is('planificador*') ? 'active':'' }}">
                <i class="fa-solid fa-truck-loading"></i>
                Planificador Carga
            </a>

            <a href="/kpi-cumplimiento-plan"
               class="nav-item {{ request()->is('kpi-cumplimiento-plan*') ? 'active':'' }}">
                <i class="fa-solid fa-bullseye"></i>
                KPI Cumplimiento
            </a>

            <a href="/materiales-911"
               class="nav-item {{ request()->is('materiales-911*') ? 'active':'' }}">
                <i class="fa-solid fa-bell-concierge"></i>
                Materiales 911
            </a>

            <a href="/conciliacion"
               class="nav-item {{ request()->is('conciliacion') ? 'active':'' }}">
                <i class="fa-solid fa-scale-balanced"></i>
                Conciliación
            </a>

            <a href="/conciliacion-despachos"
               class="nav-item {{ request()->is('conciliacion-despachos*') ? 'active':'' }}">
                <i class="fa-solid fa-clipboard-check"></i>
                Conciliación Despachos
            </a>

            <a href="/horas-extras"
               class="nav-item {{ request()->is('horas-extras*') ? 'active':'' }}">
                <i class="fa-solid fa-clock"></i>
                Horas Extras
            </a>

        </nav>

        @auth

    <form
        method="POST"
        action="{{ route('logout') }}"
        class="sidebar-logout-form"
    >
        @csrf

        <button type="submit" class="sidebar-logout-button">

            <i class="fa-solid fa-right-from-bracket"></i>

            Cerrar Sesión

        </button>

    </form>

@endauth

    </aside>

    <main class="content-wrapper">

        <header class="hero-section">

    <div class="hero-content">

        <div>
            <h1>@yield('page-title', 'WH3 Reportes')</h1>
            <p>@yield('page-description', 'Procesamiento operativo y análisis inteligente')</p>
        </div>

        @auth
            <div class="hero-user-card">

                <div class="hero-user-icon">
                    <i class="fa-solid fa-user-tie"></i>
                </div>

                <div>
                    <strong>{{ auth()->user()->name }}</strong>
                    <small>
                        {{ auth()->user()->role->name ?? 'Usuario' }}
                    </small>
                </div>

            </div>
        @endauth

    </div>

</header>

        <section class="content-area">
            @yield('content')
        </section>

    </main>

</div>

@yield('scripts')

</body>
</html>
