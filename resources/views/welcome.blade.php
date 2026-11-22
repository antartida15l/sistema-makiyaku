<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MAKIYAKU - Sistema de Riego Inteligente</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Ccircle cx='32' cy='32' r='30' fill='%2328a745'/%3E%3Cpath d='M20 44c2-14 10-22 26-24-1 16-9 24-24 26' fill='%23fff'/%3E%3C/svg%3E">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />

    <style>
        :root {
            --primary: #28a745;
            --primary-dark: #1e7e34;
            --primary-light: #34ce57;
            --secondary: #20c997;
            --accent: #17a2b8;
            --white: #ffffff;
            --light: #f8f9fa;
            --dark: #212529;
            --muted: #6c757d;
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Instrument Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #28a745 0%, #20c997 50%, #17a2b8 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background Elements */
        body::before,
        body::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
            animation: float 20s ease-in-out infinite;
        }

        body::before {
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
            top: -200px;
            left: -200px;
            animation-delay: -5s;
        }

        body::after {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            bottom: -100px;
            right: -100px;
            animation-delay: -10s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(50px, -50px) scale(1.1); }
            66% { transform: translate(-30px, 30px) scale(0.9); }
        }

        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: 20px 40px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 15px;
            z-index: 1000;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.1);
            animation: slideDown 0.6s ease-out;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .nav-link {
            padding: 10px 24px;
            background: rgba(255, 255, 255, 0.95);
            color: var(--dark);
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .nav-link:hover {
            background: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .nav-link.primary {
            background: var(--primary);
            color: var(--white);
        }

        .nav-link.primary:hover {
            background: var(--primary-dark);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 120px 20px 60px;
            position: relative;
            z-index: 1;
        }

        .logo-container {
            margin-bottom: 50px;
            animation: bounceIn 1s ease-out;
        }

        @keyframes bounceIn {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .logo {
            width: 300px;
            height: 300px;

            /* QUITAMOS EL CUADRO */
            background: transparent;
            box-shadow: none;
            border-radius: 0;

            display: flex;
            align-items: center;
            justify-content: center;

            animation: pulse 3s ease-in-out infinite;
            margin: 0 auto;
        }


        .hero-title {
            font-size: 80px;
            font-weight: 800;
            color: var(--white);
            margin-bottom: 20px;
            letter-spacing: 2px;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }

        .hero-subtitle {
            font-size: 28px;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.95);
            margin-bottom: 50px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center;
            animation: fadeInUp 0.8s ease-out 0.6s both;
        }

        .btn {
            padding: 18px 45px;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 700;
            font-size: 16px;
            transition: var(--transition);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: var(--transition);
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: var(--white);
            color: var(--primary);
        }

        .btn-primary:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.3);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: var(--white);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-4px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.3);
        }

        /* Features Section */
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 80px auto 0;
            padding: 0 20px;
            animation: fadeInUp 0.8s ease-out 0.8s both;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 35px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            transition: var(--transition);
            text-align: center;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.25);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(32, 201, 151, 0.1));
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 20px;
            transition: var(--transition);
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .feature-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 12px;
        }

        .feature-description {
            font-size: 14px;
            color: var(--muted);
            line-height: 1.6;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 40px 20px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            margin-top: 80px;
            animation: fadeIn 1s ease-out 1s both;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
            }

            .hero-title {
                font-size: 48px;
            }

            .hero-subtitle {
                font-size: 20px;
            }

            .logo {
                width: 100px;
                height: 100px;
                font-size: 50px;
            }

            .cta-buttons {
                flex-direction: column;
                width: 100%;
                max-width: 300px;
            }

            .btn {
                width: 100%;
            }

            .features {
                grid-template-columns: 1fr;
                margin-top: 60px;
            }
        }

        @media (max-width: 480px) {
            .hero-title {
                font-size: 36px;
            }

            .hero-subtitle {
                font-size: 18px;
            }

            .nav-link {
                padding: 8px 18px;
                font-size: 13px;
            }
        }

        /* Scroll indicator */
        .scroll-indicator {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            animation: bounce 2s infinite;
        }

        .scroll-indicator span {
            display: block;
            width: 30px;
            height: 50px;
            border: 2px solid rgba(255, 255, 255, 0.5);
            border-radius: 25px;
            position: relative;
        }

        .scroll-indicator span::before {
            content: '';
            position: absolute;
            top: 8px;
            left: 50%;
            transform: translateX(-50%);
            width: 6px;
            height: 6px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            animation: scroll 2s infinite;
        }

        @keyframes scroll {
            0% {
                opacity: 1;
                top: 8px;
            }
            100% {
                opacity: 0;
                top: 30px;
            }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateX(-50%) translateY(0);
            }
            40% {
                transform: translateX(-50%) translateY(-10px);
            }
            60% {
                transform: translateX(-50%) translateY(-5px);
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        @if (Route::has('login'))
            @auth
                <a href="{{ url('/dashboard') }}" class="nav-link primary">
                    Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="nav-link">
                    Iniciar Sesión
                </a>

                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="nav-link primary">
                        Registrarse
                    </a>
                @endif
            @endauth
        @endif
    </nav>

    <!-- Hero Section -->
    <section class="hero">
       <div class="logo">
            <img src="{{ asset('images/portada.png') }}" alt="MAKIYAKU" style="width: 100%; height: 100%; object-fit: contain;">
         </div>

        <h1 class="hero-title">MAKIYAKU</h1>
        <p class="hero-subtitle">Sistema de Riego Inteligente</p>

        <div class="cta-buttons">
            @guest
                <a href="{{ route('register') }}" class="btn btn-primary">
                    Comenzar Ahora
                </a>
                <a href="{{ route('login') }}" class="btn btn-secondary">
                    Iniciar Sesión
                </a>
            @else
                <a href="{{ url('/dashboard') }}" class="btn btn-primary">
                    Ir al Dashboard
                </a>
            @endguest
        </div>

        <!-- Features -->
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon"><x-icono name="agua" :size="20" /></div>
                <h3 class="feature-title">Monitoreo en Tiempo Real</h3>
                <p class="feature-description">
                    Supervisa la humedad y las condiciones ambientales en tiempo real desde cualquier lugar
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon"><x-icono name="calor" :size="20" /></div>
                <h3 class="feature-title">Control</h3>
                <p class="feature-description">
                    Sistema inteligente que ajusta el riego según las necesidades específicas de cada parcela
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon"><x-icono name="ciclo" :size="20" /></div>
                <h3 class="feature-title">Reportes Detallados</h3>
                <p class="feature-description">
                    Accede a estadísticas completas y análisis históricos para optimizar el rendimiento de tus cultivos
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon"><x-icono name="alerta" :size="20" /></div>
                <h3 class="feature-title">Alertas Inteligentes</h3>
                <p class="feature-description">
                    Recibe notificaciones instantáneas sobre eventos importantes y condiciones críticas en tus parcelas
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon"></div>
                <h3 class="feature-title">Ahorro de Recursos</h3>
                <p class="feature-description">
                    Optimiza el uso de agua con decisiones basadas en datos precisos y análisis predictivo
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon"></div>
                <h3 class="feature-title">Acceso completo</h3>
                <p class="feature-description">
                    Gestiona tus parcelas con un catalogo amplio de cultivos a nivel nacional, y si no lo encuentras simplemente añadelo
                </p>
            </div>
        </div>

        <div class="scroll-indicator">
            
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; {{ date('Y') }} MAKIYAKU. Sistema de Riego Inteligente. Todos los derechos reservados.</p>
        <p style="margin-top: 10px; opacity: 0.8;">Innovación tecnológica para la agricultura sostenible</p>
    </footer>
</body>
</html>