<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'INTERVEREDANET - Sistema de Cobros')</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ time() }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ time() }}">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #0ea5e9;
            --primary-dark: #0284c7;
            --sidebar-width: 260px;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --accent-color: #38bdf8;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f1f5f9;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            padding-top: 0;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
        }
        
        .sidebar .brand {
            background: linear-gradient(135deg, #0c4a6e 0%, #0369a1 100%);
            padding: 1.25rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar .brand img {
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
        }
        
        .sidebar .nav-link {
            color: #94a3b8;
            padding: 0.875rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.875rem;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            font-size: 0.9rem;
        }
        
        .sidebar .nav-link:hover {
            color: #f1f5f9;
            background: var(--sidebar-hover);
            border-left-color: var(--accent-color);
        }
        
        .sidebar .nav-link.active {
            color: #fff;
            background: linear-gradient(90deg, var(--sidebar-hover) 0%, transparent 100%);
            border-left-color: var(--accent-color);
        }
        
        .sidebar .nav-link i {
            width: 22px;
            text-align: center;
            font-size: 1rem;
            color: var(--accent-color);
        }
        
        .sidebar .nav-link:hover i,
        .sidebar .nav-link.active i {
            color: #fff;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
            min-height: 100vh;
        }
        
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
            border-radius: 0.5rem;
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
        }
        
        .stat-card {
            border-left: 4px solid var(--primary-color);
        }
        
        .stat-card.success { border-left-color: #198754; }
        .stat-card.warning { border-left-color: #ffc107; }
        .stat-card.danger { border-left-color: #dc3545; }
        .stat-card.info { border-left-color: #0dcaf0; }
        
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
        }
        
        .stat-card .stat-label {
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        .table th {
            font-weight: 600;
            background: #f8f9fa;
        }
        
        .badge-estado {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="brand text-center">
            <img src="{{ asset('images/logo.png') }}?v={{ time() }}" alt="INTERVEREDANET" style="max-width: 210px; height: auto;">
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('proyectos.*') ? 'active' : '' }}" href="{{ route('proyectos.index') }}">
                    <i class="fas fa-project-diagram"></i> Proyectos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('clientes.*') ? 'active' : '' }}" href="{{ route('clientes.index') }}">
                    <i class="fas fa-users"></i> Clientes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('planes.*') ? 'active' : '' }}" href="{{ route('planes.index') }}">
                    <i class="fas fa-box"></i> Planes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('servicios.*') ? 'active' : '' }}" href="{{ route('servicios.index') }}">
                    <i class="fas fa-network-wired"></i> Servicios
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('facturas.*') ? 'active' : '' }}" href="{{ route('facturas.index') }}">
                    <i class="fas fa-file-invoice-dollar"></i> Facturas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('cobradores.*') ? 'active' : '' }}" href="{{ route('cobradores.index') }}">
                    <i class="fas fa-user-tie"></i> Cobradores
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('cobros.*') ? 'active' : '' }}" href="{{ route('cobros.index') }}">
                    <i class="fas fa-hand-holding-usd"></i> Cobros
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('pagos.*') ? 'active' : '' }}" href="{{ route('pagos.index') }}">
                    <i class="fas fa-money-bill-wave"></i> Pagos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('liquidaciones.*') ? 'active' : '' }}" href="{{ route('liquidaciones.index') }}">
                    <i class="fas fa-calculator"></i> Liquidaciones
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Configuración global de DataTables
        $.extend(true, $.fn.dataTable.defaults, {
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            pageLength: 25
        });

        // Formatear números como moneda
        function formatMoney(amount) {
            return new Intl.NumberFormat('es-CO', {
                style: 'currency',
                currency: 'COP',
                minimumFractionDigits: 0
            }).format(amount);
        }
    </script>
    @stack('scripts')
</body>
</html>
