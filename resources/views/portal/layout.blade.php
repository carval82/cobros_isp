<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal Cliente') - InterVeredaNet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #0f172a;
            --secondary-color: #1e293b;
            --accent-color: #3b82f6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
        }
        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .navbar-portal {
            background: rgba(15, 23, 42, 0.95) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .navbar-brand {
            font-weight: 700;
            color: #fff !important;
        }
        .navbar-brand span {
            color: var(--accent-color);
        }
        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            margin: 0 0.25rem;
            transition: all 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            color: #fff !important;
            background: rgba(59, 130, 246, 0.2);
        }
        .card-portal {
            background: rgba(30, 41, 59, 0.9);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            backdrop-filter: blur(10px);
        }
        .card-portal .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            font-weight: 600;
        }
        .card-portal .card-body {
            color: #e2e8f0;
        }
        .stat-card {
            background: linear-gradient(135deg, var(--secondary-color) 0%, rgba(59, 130, 246, 0.2) 100%);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card .stat-icon {
            font-size: 2.5rem;
            color: var(--accent-color);
            margin-bottom: 0.5rem;
        }
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #fff;
        }
        .stat-card .stat-label {
            color: #94a3b8;
            font-size: 0.875rem;
        }
        .stat-card.danger {
            background: linear-gradient(135deg, var(--secondary-color) 0%, rgba(239, 68, 68, 0.2) 100%);
            border-color: rgba(239, 68, 68, 0.3);
        }
        .stat-card.danger .stat-icon, .stat-card.danger .stat-value {
            color: var(--danger-color);
        }
        .stat-card.success {
            background: linear-gradient(135deg, var(--secondary-color) 0%, rgba(16, 185, 129, 0.2) 100%);
            border-color: rgba(16, 185, 129, 0.3);
        }
        .stat-card.success .stat-icon, .stat-card.success .stat-value {
            color: var(--success-color);
        }
        .btn-portal {
            background: var(--accent-color);
            border: none;
            color: #fff;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-portal:hover {
            background: #2563eb;
            color: #fff;
            transform: translateY(-2px);
        }
        .table-portal {
            color: #e2e8f0;
        }
        .table-portal thead th {
            background: rgba(59, 130, 246, 0.1);
            color: #fff;
            border-bottom: 2px solid rgba(59, 130, 246, 0.3);
            font-weight: 600;
        }
        .table-portal tbody tr {
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .table-portal tbody tr:hover {
            background: rgba(59, 130, 246, 0.1);
        }
        .badge-estado {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        .footer-portal {
            background: rgba(15, 23, 42, 0.9);
            color: #64748b;
            padding: 1rem 0;
            margin-top: auto;
        }
        .user-info {
            color: #fff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .user-avatar {
            width: 35px;
            height: 35px;
            background: var(--accent-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
    </style>
    @stack('styles')
</head>
<body class="d-flex flex-column">
    <nav class="navbar navbar-expand-lg navbar-portal">
        <div class="container">
            <a class="navbar-brand" href="{{ route('portal.dashboard') }}">
                <i class="bi bi-wifi me-2"></i>Inter<span>Vereda</span>Net
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}" href="{{ route('portal.dashboard') }}">
                            <i class="bi bi-house-door me-1"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('portal.estado-cuenta') ? 'active' : '' }}" href="{{ route('portal.estado-cuenta') }}">
                            <i class="bi bi-receipt me-1"></i> Estado de Cuenta
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('portal.tickets*') ? 'active' : '' }}" href="{{ route('portal.tickets') }}">
                            <i class="bi bi-chat-dots me-1"></i> Reportes
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('portal.perfil') }}" class="user-info text-decoration-none">
                        <div class="user-avatar">{{ substr(session('cliente_nombre', 'C'), 0, 1) }}</div>
                        <span class="d-none d-md-inline">{{ session('cliente_nombre', 'Cliente') }}</span>
                    </a>
                    <form action="{{ route('portal.logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-box-arrow-right"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow-1 py-4">
        <div class="container">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <footer class="footer-portal text-center">
        <div class="container">
            <p class="mb-0">&copy; {{ date('Y') }} InterVeredaNet - Servicios de Internet</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
