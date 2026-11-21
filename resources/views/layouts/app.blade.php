<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - MAKIYAKU</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🌱</text></svg>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --primary: #28a745;
            --primary-dark: #1e7e34;
            --secondary: #20c997;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #007bff;
            --success: #28a745;
            --light: #f8f9fa;
            --dark: #212529;
            --muted: #6c757d;
            --white: #ffffff;
            --border-radius: 12px;
            --shadow-sm: 0 2px 10px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 20px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 8px 30px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --sidebar-width: 280px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--light);
            overflow-x: hidden;
            color: var(--dark);
            line-height: 1.6;
        }

        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* ═══════════════════════════════════════════════════════════
           SIDEBAR
           ═══════════════════════════════════════════════════════════ */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-dark) 0%, var(--primary) 50%, var(--secondary) 100%);
            color: var(--white);
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-lg);
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: var(--transition);
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .sidebar-header {
            padding: 30px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            position: relative;
            z-index: 1;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 12px;
            text-decoration: none;
            color: var(--white);
            transition: var(--transition);
        }

        .sidebar-logo:hover {
            transform: translateX(5px);
        }

        .sidebar-logo-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: var(--transition);
        }

        .sidebar-logo:hover .sidebar-logo-icon {
            transform: rotate(10deg) scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .sidebar-logo-text {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: 1px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .sidebar-subtitle {
            font-size: 11px;
            opacity: 0.85;
            padding-left: 65px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }

        .sidebar-nav {
            flex: 1;
            padding: 25px 0;
            overflow-y: auto;
            position: relative;
            z-index: 1;
        }

        .sidebar-nav::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-nav::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 3px;
            margin: 10px 0;
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
            transition: var(--transition);
        }

        .sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        .nav-section {
            margin-bottom: 25px;
        }

        .nav-section-title {
            padding: 10px 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            opacity: 0.6;
            letter-spacing: 1px;
        }

        .nav-item {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
            transition: var(--transition);
            border-left: 4px solid transparent;
            color: var(--white);
            text-decoration: none;
            position: relative;
            overflow: hidden;
            margin: 2px 0;
        }

        .nav-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 0;
            background: rgba(255, 255, 255, 0.1);
            transition: var(--transition);
        }

        .nav-item:hover::before {
            width: 100%;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
            border-left-color: var(--white);
            padding-left: 25px;
        }

        .nav-item.active {
            background: rgba(255, 255, 255, 0.2);
            border-left-color: var(--white);
            font-weight: 600;
            box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .nav-item.active::after {
            content: '●';
            position: absolute;
            right: 20px;
            font-size: 8px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .nav-icon {
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: var(--transition);
            position: relative;
            z-index: 1;
        }

        .nav-item:hover .nav-icon {
            transform: scale(1.2) rotate(5deg);
        }

        .nav-item.active .nav-icon {
            animation: bounce 0.5s;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .nav-badge {
            margin-left: auto;
            background: var(--danger);
            color: var(--white);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            position: relative;
            z-index: 1;
            animation: pulsoBadge 2s infinite;
        }

        @keyframes pulsoBadge {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            position: relative;
            z-index: 1;
            background: rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .user-info:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.1));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .user-details {
            flex: 1;
            overflow: hidden;
        }

        .user-name {
            font-weight: 700;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 2px;
        }

        .user-email {
            font-size: 11px;
            opacity: 0.8;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-status {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 10px;
            margin-top: 4px;
            opacity: 0.9;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: #4caf50;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .btn-logout {
            width: 100%;
            padding: 12px;
            background: rgba(220, 53, 69, 0.9);
            border: none;
            border-radius: 10px;
            color: var(--white);
            cursor: pointer;
            font-weight: 700;
            transition: var(--transition);
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);
        }

        .btn-logout:hover {
            background: var(--danger);
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(220, 53, 69, 0.4);
        }

        .btn-logout:active {
            transform: translateY(-1px);
        }

        /* ═══════════════════════════════════════════════════════════
           MAIN CONTENT
           ═══════════════════════════════════════════════════════════ */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            background: var(--light);
            min-height: 100vh;
            width: calc(100% - var(--sidebar-width));
            transition: var(--transition);
        }

        .topbar {
            background: var(--white);
            padding: 20px 35px;
            box-shadow: var(--shadow-sm);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #e9ecef;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .topbar-title {
            font-size: 26px;
            font-weight: 800;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar-title::before {
            content: '';
            width: 4px;
            height: 28px;
            background: linear-gradient(180deg, var(--primary), var(--secondary));
            border-radius: 2px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--muted);
        }

        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb a:hover {
            color: var(--primary-dark);
        }

        .mobile-menu-toggle {
            display: none;
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: var(--white);
            border: none;
            border-radius: 12px;
            font-size: 22px;
            cursor: pointer;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1100;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
        }

        .mobile-menu-toggle:hover {
            transform: scale(1.1);
            box-shadow: var(--shadow-lg);
        }

        .mobile-menu-toggle:active {
            transform: scale(0.95);
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 999;
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s;
        }

        .sidebar-overlay.active {
            display: block;
        }

        .content-area {
            padding: 35px;
            animation: fadeIn 0.5s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ═══════════════════════════════════════════════════════════
           ALERTS
           ═══════════════════════════════════════════════════════════ */
        .alert {
            padding: 16px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideInDown 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 4px solid;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
        }

        .alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transform: translateX(-100%);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            100% { transform: translateX(100%); }
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-icon {
            font-size: 22px;
            flex-shrink: 0;
        }

        .alert-content {
            flex: 1;
        }

        .alert-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            opacity: 0.5;
            transition: var(--transition);
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .alert-close:hover {
            opacity: 1;
            transform: rotate(90deg);
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left-color: var(--success);
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left-color: var(--danger);
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left-color: var(--warning);
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left-color: var(--info);
        }

        /* ═══════════════════════════════════════════════════════════
           TOAST NOTIFICATIONS
           ═══════════════════════════════════════════════════════════ */
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }

        .alerta-toast {
            position: fixed;
            top: 80px;
            right: 20px;
            width: 350px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            overflow: hidden;
            animation: slideInRight 0.4s ease;
            z-index: 9999;
            border-left: 5px solid;
        }

        .alerta-toast:hover {
            transform: translateX(-4px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.25);
        }

        .alerta-toast-critico { border-left-color: #dc3545; }
        .alerta-toast-alto { border-left-color: #ffc107; }
        .alerta-toast-medio { border-left-color: #17a2b8; }
        .alerta-toast-info { border-left-color: #007bff; }

        .toast-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .toast-icon {
            font-size: 24px;
        }

        .toast-header strong {
            flex: 1;
            font-size: 15px;
            color: #212529;
        }

        .toast-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #6c757d;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .toast-close:hover {
            color: #dc3545;
            transform: rotate(90deg);
        }

        .toast-body {
            padding: 15px;
        }

        .toast-mensaje {
            font-size: 14px;
            color: #495057;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .toast-parcela {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 10px;
        }

        .toast-link {
            display: inline-block;
            padding: 8px 16px;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .toast-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        /* ═══════════════════════════════════════════════════════════
           LOADING
           ═══════════════════════════════════════════════════════════ */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: var(--white);
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .page-loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(10px);
        }

        .page-loading-spinner {
            width: 60px;
            height: 60px;
            border: 5px solid rgba(40, 167, 69, 0.2);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s linear infinite;
        }

        /* ═══════════════════════════════════════════════════════════
           RESPONSIVE
           ═══════════════════════════════════════════════════════════ */
        @media (max-width: 768px) {
            :root {
                --sidebar-width: 100%;
            }

            .sidebar {
                transform: translateX(-100%);
                width: 100%;
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .mobile-menu-toggle {
                display: flex;
            }

            .topbar {
                padding: 15px 20px;
                padding-left: 75px;
            }

            .topbar-title {
                font-size: 18px;
            }

            .topbar-title::before {
                display: none;
            }

            .breadcrumb {
                display: none;
            }

            .content-area {
                padding: 20px;
            }

            .user-details {
                display: block !important;
            }

            .alerta-toast {
                width: calc(100% - 40px);
                right: 20px;
            }
        }

        @media (max-width: 480px) {
            .alert {
                padding: 12px 15px;
                font-size: 14px;
            }
        }

        /* ═══════════════════════════════════════════════════════════
           UTILITY CLASSES
           ═══════════════════════════════════════════════════════════ */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .mb-0 { margin-bottom: 0; }
        .mb-1 { margin-bottom: 10px; }
        .mb-2 { margin-bottom: 20px; }
        .mb-3 { margin-bottom: 30px; }
        .mt-1 { margin-top: 10px; }
        .mt-2 { margin-top: 20px; }
        .mt-3 { margin-top: 30px; }
    </style>

    @stack('styles')
</head>
<body>
    <div class="page-loading" id="pageLoading" style="display: none;">
        <div class="page-loading-spinner"></div>
    </div>

    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()" aria-label="Menú">
        <span id="menuIcon">☰</span>
    </button>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleMobileMenu()"></div>

    <div class="app-container">
        <!-- ═══════════════════════════════════════════════════════════
             SIDEBAR
             ═══════════════════════════════════════════════════════════ -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="{{ route('dashboard') }}" class="sidebar-logo">
                    <div class="sidebar-logo-icon">
                        <img src="{{ asset('images/portada.png') }}" alt="MAKIYAKU" style="width: 100%; height: 100%; object-fit: contain;">
                    </div>
                    <div class="sidebar-logo-text">MAKIYAKU</div>
                </a>
                <div class="sidebar-subtitle">Sistema de Riego Inteligente</div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Principal</div>
                    <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <span class="nav-icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('parcelas.index') }}" class="nav-item {{ request()->routeIs('parcelas.*') ? 'active' : '' }}">
                        <span class="nav-icon">🌾</span>
                        <span>Mis Parcelas</span>
                    </a>
                    <a href="{{ route('cultivos.index') }}" class="nav-item {{ request()->routeIs('cultivos.*') ? 'active' : '' }}">
                        <span class="nav-icon">🌿</span>
                        <span>Cultivos</span>
                    </a>
                    <a href="{{ route('clima.index') }}" class="nav-item {{ request()->routeIs('clima.*') ? 'active' : '' }}">
                        <span class="nav-icon">☁️</span>
                        <span>Clima</span>
                    </a>
                    <a href="{{ route('alertas.index') }}" class="nav-item {{ request()->routeIs('alertas.*') ? 'active' : '' }}">
                        <span class="nav-icon">🔔</span>
                        <span>Alertas</span>
                        @php
                            $alertasNoLeidas = 0;
                            try {
                                if (Auth::check() && class_exists('App\Models\Alerta')) {
                                    $alertasNoLeidas = \App\Models\Alerta::where('user_id', Auth::id())
                                        ->where('leida', false)
                                        ->count();
                                }
                            } catch (\Exception $e) {}
                        @endphp
                        @if($alertasNoLeidas > 0)
                            <span class="nav-badge">{{ $alertasNoLeidas }}</span>
                        @endif
                    </a>

                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Contacto y soporte</div>
                    <a href="https://wa.me/51963075106?text=Hola%20necesito%20ayuda%20con%20el%20sistema"
                       target="_blank"
                       class="nav-item">
                        <span class="nav-icon">
                            <i class="fab fa-whatsapp"></i>
                        </span>
                        <span>Comunícate conmigo</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Configuración</div>
                    <a href="#" class="nav-item" onclick="event.preventDefault(); toggleSonidoAlertas();">
                        <span class="nav-icon" id="iconoSonido">🔔</span>
                        <span id="textoSonido">Sonido de Alertas</span>
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">👤</div>
                    <div class="user-details">
                        <div class="user-name">{{ Auth::user()->name ?? 'Usuario' }}</div>
                        <div class="user-email">{{ Auth::user()->email ?? 'email@ejemplo.com' }}</div>
                        <div class="user-status">
                            <span class="status-dot"></span>
                            <span>En línea</span>
                        </div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" id="logoutForm">
                    @csrf
                    <button type="submit" class="btn-logout">
                        <span>🚪</span>
                        <span>Cerrar Sesión</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════
             MAIN CONTENT
             ═══════════════════════════════════════════════════════════ -->
        <div class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <div>
                        <div class="topbar-title">@yield('title', 'Dashboard')</div>
                        <div class="breadcrumb">
                            <a href="{{ route('dashboard') }}">Inicio</a>
                            <span>›</span>
                            <span>@yield('title', 'Dashboard')</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-area">
                {{-- Flash Messages --}}
                @if(session('success'))
                    <div class="alert alert-success" role="alert">
                        <span class="alert-icon">✅</span>
                        <div class="alert-content">{{ session('success') }}</div>
                        <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger" role="alert">
                        <span class="alert-icon">❌</span>
                        <div class="alert-content">{{ session('error') }}</div>
                        <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="alert alert-warning" role="alert">
                        <span class="alert-icon">⚠️</span>
                        <div class="alert-content">{{ session('warning') }}</div>
                        <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                    </div>
                @endif

                @if(session('info'))
                    <div class="alert alert-info" role="alert">
                        <span class="alert-icon">ℹ️</span>
                        <div class="alert-content">{{ session('info') }}</div>
                        <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <span class="alert-icon">❌</span>
                        <div class="alert-content">
                            <strong>Por favor corrige los siguientes errores:</strong>
                            <ul style="margin: 8px 0 0 20px; padding: 0;">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                    </div>
                @endif

                {{-- Contenido de la página --}}
                @yield('content')
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         SCRIPTS BASE
         ═══════════════════════════════════════════════════════════ -->
    <script>
        // ═══════════════════════════════════════════════════════════
        // FUNCIONES DE NAVEGACIÓN
        // ═══════════════════════════════════════════════════════════
        function toggleMobileMenu() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const menuIcon = document.getElementById('menuIcon');
            
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
            
            menuIcon.textContent = sidebar.classList.contains('mobile-open') ? '✕' : '☰';
        }

        function showLoading() {
            document.getElementById('pageLoading').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('pageLoading').style.display = 'none';
        }

        // ═══════════════════════════════════════════════════════════
        // MANEJO DE ALERTAS AUTO-DISMISS
        // ═══════════════════════════════════════════════════════════
        document.addEventListener('DOMContentLoaded', function() {
            hideLoading();
            
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.animation = 'slideInDown 0.4s reverse';
                    setTimeout(() => alert.remove(), 400);
                }, 5000);
            });
        });

        // ═══════════════════════════════════════════════════════════
        // LOADING EN FORMULARIO DE LOGOUT
        // ═══════════════════════════════════════════════════════════
        document.getElementById('logoutForm')?.addEventListener('submit', function() {
            showLoading();
        });

        window.addEventListener('beforeunload', function() {
            showLoading();
        });

        window.addEventListener("pageshow", function (event) {
            if (event.persisted) {
                hideLoading();
                window.location.reload();
            }
        });
    </script>

    <!-- ═══════════════════════════════════════════════════════════
         SISTEMA DE SONIDO PARA ALERTAS CON RECORDATORIOS
         ═══════════════════════════════════════════════════════════ -->
    <script>
        // ═══════════════════════════════════════════════════════════
        // VARIABLES GLOBALES
        // ═══════════════════════════════════════════════════════════
        let audioContext = null;
        let sonidosHabilitados = localStorage.getItem('alertas_sonido') !== 'false';
        let ultimasAlertas = [];
        let intervaloChequeo = null;
        let tiemposRecordatorio = {}; // Guarda cuándo sonó cada alerta

        // Inicializar Audio Context al primer click
        document.addEventListener('click', function initAudio() {
            if (!audioContext) {
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
                console.log('✅ Audio Context inicializado');
            }
            document.removeEventListener('click', initAudio);
        }, { once: true });

        // ═══════════════════════════════════════════════════════════
        // CONFIGURACIÓN DE RECORDATORIOS POR NIVEL
        // ═══════════════════════════════════════════════════════════
        const CONFIGURACION_RECORDATORIOS = {
            critico: {
                habilitado: true,
                intervalos: [0, 10, 30], // minutos: inmediato, 10min, 30min
                repetirHasta: 3 // máximo 3 recordatorios
            },
            alto: {
                habilitado: true,
                intervalos: [0, 30], // inmediato y a los 30min
                repetirHasta: 2
            },
            medio: {
                habilitado: true,
                intervalos: [0], // solo una vez
                repetirHasta: 1
            },
            info: {
                habilitado: true,
                intervalos: [0], // solo una vez
                repetirHasta: 1
            }
        };

        // ═══════════════════════════════════════════════════════════
        // GENERADOR DE SONIDOS WEB AUDIO API
        // ═══════════════════════════════════════════════════════════
        function reproducirSonidoAlerta(tipo = 'info', esRecordatorio = false) {
            if (!sonidosHabilitados || !audioContext) {
                console.log('🔇 Sonidos deshabilitados o AudioContext no disponible');
                return;
            }

            const configuraciones = {
                'critico': { 
                    frecuencia: esRecordatorio ? 800 : 880, 
                    duracion: esRecordatorio ? 0.15 : 0.2, 
                    repeticiones: esRecordatorio ? 2 : 3, 
                    volumen: esRecordatorio ? 0.25 : 0.3 
                },
                'alto': { 
                    frecuencia: 660, 
                    duracion: 0.15, 
                    repeticiones: esRecordatorio ? 1 : 2, 
                    volumen: 0.25 
                },
                'medio': { 
                    frecuencia: 440, 
                    duracion: 0.1, 
                    repeticiones: 1, 
                    volumen: 0.2 
                },
                'info': { 
                    frecuencia: 330, 
                    duracion: 0.1, 
                    repeticiones: 1, 
                    volumen: 0.15 
                }
            };

            const config = configuraciones[tipo] || configuraciones['info'];
            let tiempoActual = audioContext.currentTime;

            for (let i = 0; i < config.repeticiones; i++) {
                const oscilador = audioContext.createOscillator();
                const ganancia = audioContext.createGain();

                oscilador.connect(ganancia);
                ganancia.connect(audioContext.destination);

                oscilador.frequency.value = config.frecuencia;
                oscilador.type = 'sine';

                ganancia.gain.setValueAtTime(0, tiempoActual);
                ganancia.gain.linearRampToValueAtTime(config.volumen, tiempoActual + 0.01);
                ganancia.gain.exponentialRampToValueAtTime(0.01, tiempoActual + config.duracion);

                oscilador.start(tiempoActual);
                oscilador.stop(tiempoActual + config.duracion);

                tiempoActual += config.duracion + 0.1;
            }

            console.log(`🔊 Sonido ${esRecordatorio ? 'RECORDATORIO' : 'INICIAL'} - Nivel: ${tipo}`);
        }

        // ═══════════════════════════════════════════════════════════
        // GESTIÓN DE RECORDATORIOS
        // ═══════════════════════════════════════════════════════════
        function procesarRecordatorios(alertasActuales) {
            const ahora = Date.now();

            alertasActuales.forEach(alerta => {
                const config = CONFIGURACION_RECORDATORIOS[alerta.nivel];
                if (!config || !config.habilitado) return;

                // Inicializar tracking si no existe
                if (!tiemposRecordatorio[alerta.id]) {
                    tiemposRecordatorio[alerta.id] = {
                        primeraSonada: ahora,
                        vecesRepetido: 0,
                        ultimaVez: ahora
                    };
                    
                    // Sonido inicial
                    reproducirSonidoAlerta(alerta.nivel, false);
                    mostrarNotificacionToast(alerta, false);
                    return;
                }

                const tracking = tiemposRecordatorio[alerta.id];
                const minutosDesdeInicio = (ahora - tracking.primeraSonada) / (1000 * 60);

                // Verificar si debe sonar según intervalos configurados
                for (let i = tracking.vecesRepetido; i < config.intervalos.length; i++) {
                    const intervaloEsperado = config.intervalos[i];
                    
                    if (minutosDesdeInicio >= intervaloEsperado && tracking.vecesRepetido < config.repetirHasta) {
                        // Es momento de recordar
                        reproducirSonidoAlerta(alerta.nivel, true);
                        mostrarNotificacionToast(alerta, true);
                        
                        tracking.vecesRepetido++;
                        tracking.ultimaVez = ahora;
                        
                        console.log(`⏰ Recordatorio ${tracking.vecesRepetido} para alerta #${alerta.id} (${alerta.titulo})`);
                        break;
                    }
                }
            });

            // Limpiar tracking de alertas que ya no existen
            Object.keys(tiemposRecordatorio).forEach(alertaId => {
                const existe = alertasActuales.some(a => a.id == alertaId);
                if (!existe) {
                    console.log(`✅ Alerta #${alertaId} resuelta - eliminando recordatorios`);
                    delete tiemposRecordatorio[alertaId];
                }
            });
        }

        // ═══════════════════════════════════════════════════════════
        // MONITOREO DE ALERTAS (cada 30 segundos)
        // ═══════════════════════════════════════════════════════════
        function iniciarMonitoreoAlertas() {
            intervaloChequeo = setInterval(async () => {
                try {
                    const response = await fetch('/alertas/recientes', {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (!response.ok) return;

                    const data = await response.json();
                    
                    if (data.success && data.alertas) {
                        // Procesar recordatorios para alertas actuales
                        procesarRecordatorios(data.alertas);

                        // Actualizar lista
                        ultimasAlertas = data.alertas;
                        actualizarBadgeAlertas(data.alertas.length);
                    }
                } catch (error) {
                    console.error('❌ Error al verificar alertas:', error);
                }
            }, 30000); // 30 segundos
        }

        // ═══════════════════════════════════════════════════════════
        // NOTIFICACIÓN TOAST
        // ═══════════════════════════════════════════════════════════
        function mostrarNotificacionToast(alerta, esRecordatorio = false) {
            // Remover toasts anteriores
            const toastsAnteriores = document.querySelectorAll('.alerta-toast');
            toastsAnteriores.forEach(t => {
                if (t.dataset.alertaId == alerta.id) {
                    t.remove();
                }
            });

            const toast = document.createElement('div');
            toast.className = `alerta-toast alerta-toast-${alerta.nivel}`;
            toast.dataset.alertaId = alerta.id;
            
            const etiquetaRecordatorio = esRecordatorio 
                ? '<span style="background: #ffc107; color: #000; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; margin-left: 8px;">RECORDATORIO</span>' 
                : '';
            
            toast.innerHTML = `
                <div class="toast-header">
                    <span class="toast-icon">${alerta.icono || '🔔'}</span>
                    <strong>${alerta.titulo}</strong>
                    ${etiquetaRecordatorio}
                    <button class="toast-close" onclick="this.parentElement.parentElement.remove()">×</button>
                </div>
                <div class="toast-body">
                    <div class="toast-mensaje">${alerta.mensaje ? alerta.mensaje.substring(0, 100) : 'Nueva alerta'}...</div>
                    <div class="toast-parcela">🌾 ${alerta.parcela || 'Sistema'}</div>
                    <a href="${alerta.url || '/alertas'}" class="toast-link">Ver detalles →</a>
                </div>
            `;

            document.body.appendChild(toast);

            // Auto-remover después de un tiempo
            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, esRecordatorio ? 10000 : 8000);
        }

        // ═══════════════════════════════════════════════════════════
        // ACTUALIZAR BADGE DE ALERTAS
        // ═══════════════════════════════════════════════════════════
        function actualizarBadgeAlertas(cantidad) {
            const badges = document.querySelectorAll('.nav-badge');
            badges.forEach(badge => {
                if (cantidad > 0) {
                    badge.textContent = cantidad;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            });
        }

        // ═══════════════════════════════════════════════════════════
        // TOGGLE DE SONIDO
        // ═══════════════════════════════════════════════════════════
        function toggleSonidoAlertas() {
            sonidosHabilitados = !sonidosHabilitados;
            localStorage.setItem('alertas_sonido', sonidosHabilitados);
            
            const icono = sonidosHabilitados ? '🔔' : '🔕';
            const mensaje = sonidosHabilitados ? 'Sonidos activados ✅' : 'Sonidos desactivados 🔇';
            
            // Actualizar icono en el menú
            const iconoSonido = document.getElementById('iconoSonido');
            const textoSonido = document.getElementById('textoSonido');
            if (iconoSonido) iconoSonido.textContent = icono;
            if (textoSonido) textoSonido.textContent = sonidosHabilitados ? 'Sonido Activado' : 'Sonido Desactivado';
            
            mostrarMensajeTemporal(mensaje, icono);
            
            if (sonidosHabilitados && audioContext) {
                reproducirSonidoAlerta('info');
            }
        }

        function mostrarMensajeTemporal(mensaje, icono) {
            const div = document.createElement('div');
            div.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #28a745, #20c997);
                color: white;
                padding: 15px 25px;
                border-radius: 12px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                z-index: 10000;
                font-weight: 600;
                animation: slideInRight 0.3s ease;
            `;
            div.innerHTML = `${icono} ${mensaje}`;
            document.body.appendChild(div);
            
            setTimeout(() => {
                div.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => div.remove(), 300);
            }, 2000);
        }

        // ═══════════════════════════════════════════════════════════
        // FUNCIÓN GLOBAL PARA PRUEBAS
        // ═══════════════════════════════════════════════════════════
        function probarSonidoAlerta(nivel = 'info') {
            reproducirSonidoAlerta(nivel, false);
            mostrarNotificacionToast({
                id: Date.now(),
                nivel: nivel,
                icono: nivel === 'critico' ? '🚨' : nivel === 'alto' ? '⚠️' : 'ℹ️',
                titulo: `Prueba de sonido: ${nivel}`,
                mensaje: 'Esta es una notificación de prueba del sistema',
                parcela: 'Sistema de Prueba',
                url: '/alertas'
            }, false);
        }

        // ═══════════════════════════════════════════════════════════
        // INICIALIZACIÓN DEL SISTEMA
        // ═══════════════════════════════════════════════════════════
        document.addEventListener('DOMContentLoaded', function() {
            // Actualizar icono según estado guardado
            const iconoSonido = document.getElementById('iconoSonido');
            const textoSonido = document.getElementById('textoSonido');
            if (iconoSonido) iconoSonido.textContent = sonidosHabilitados ? '🔔' : '🔕';
            if (textoSonido) textoSonido.textContent = sonidosHabilitados ? 'Sonido Activado' : 'Sonido Desactivado';

            // Iniciar monitoreo
            iniciarMonitoreoAlertas();
            
            // Cargar alertas iniciales
            fetch('/alertas/recientes', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.alertas) {
                    ultimasAlertas = data.alertas;
                    procesarRecordatorios(data.alertas);
                    actualizarBadgeAlertas(data.alertas.length);
                    console.log(`📊 ${data.alertas.length} alertas activas cargadas`);
                }
            })
            .catch(err => console.error('❌ Error cargando alertas:', err));

            console.log('✅ Sistema de alertas CON RECORDATORIOS inicializado');
            console.log('🔊 Configuración de recordatorios:');
            console.log('   - Crítico: Inmediato, 10min, 30min (máx 3)');
            console.log('   - Alto: Inmediato, 30min (máx 2)');
            console.log('   - Medio/Info: Solo 1 vez');
            console.log(`🔊 Estado actual: ${sonidosHabilitados ? 'ACTIVADO' : 'DESACTIVADO'}`);
        });

        // Limpiar interval al salir
        window.addEventListener('beforeunload', () => {
            if (intervaloChequeo) clearInterval(intervaloChequeo);
        });
    </script>

    @stack('scripts')
</body>
</html>