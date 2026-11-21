<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar Sesión - MAKIYAKU</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🌱</text></svg>">
    
    <style>
        :root {
            --primary: #28a745;
            --primary-dark: #1e7e34;
            --secondary: #20c997;
            --danger: #dc3545;
            --white: #ffffff;
            --light: #f8f9fa;
            --dark: #212529;
            --muted: #6c757d;
            --border-radius: 20px;
            --shadow-lg: 0 10px 40px rgba(0, 0, 0, 0.15);
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #28a745 0%, #20c997 50%, #17a2b8 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Animated Background Elements */
        body::before {
            content: '🌱';
            position: absolute;
            font-size: 200px;
            opacity: 0.05;
            top: -50px;
            left: -50px;
            animation: float 6s ease-in-out infinite;
        }

        body::after {
            content: '💧';
            position: absolute;
            font-size: 150px;
            opacity: 0.05;
            bottom: -30px;
            right: -30px;
            animation: float 8s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(10deg); }
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 50px 45px;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), var(--primary));
            background-size: 200% 100%;
            animation: gradientMove 3s linear infinite;
        }

        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            100% { background-position: 200% 50%; }
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 24px;
            font-size: 45px;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .login-title {
            font-size: 32px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .login-subtitle {
            color: var(--muted);
            font-size: 15px;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
            font-size: 14px;
            transition: var(--transition);
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            font-size: 20px;
            color: var(--muted);
            transition: var(--transition);
            pointer-events: none;
        }

        .form-input {
            width: 100%;
            padding: 16px 18px 16px 55px;
            border: 2px solid #e9ecef;
            border-radius: 14px;
            font-size: 15px;
            color: var(--dark);
            transition: var(--transition);
            outline: none;
            background: var(--white);
        }

        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.1);
        }

        .form-input:focus ~ .input-icon {
            color: var(--primary);
            transform: scale(1.1);
        }

        .form-input.error {
            border-color: var(--danger);
            background: #fff5f5;
        }

        .toggle-password {
            position: absolute;
            right: 18px;
            cursor: pointer;
            font-size: 20px;
            color: var(--muted);
            transition: var(--transition);
            user-select: none;
        }

        .toggle-password:hover {
            color: var(--primary);
            transform: scale(1.1);
        }

        .error-message {
            color: var(--danger);
            font-size: 13px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
            animation: shake 0.4s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            user-select: none;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        .remember-me label {
            font-size: 14px;
            color: var(--dark);
            cursor: pointer;
            font-weight: 500;
        }

        .forgot-password {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: var(--transition);
        }

        .forgot-password:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .btn-login {
            width: 100%;
            padding: 17px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 14px;
            color: var(--white);
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: var(--transition);
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.4);
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-login .loading {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: var(--white);
            animation: spin 0.8s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .btn-login.loading .btn-text {
            display: none;
        }

        .btn-login.loading .loading {
            display: block;
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 30px 0;
            color: var(--muted);
            font-size: 14px;
            font-weight: 500;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 2px solid #e9ecef;
        }

        .divider span {
            padding: 0 15px;
        }

        .register-link {
            text-align: center;
            margin-top: 30px;
            color: var(--muted);
            font-size: 14px;
        }

        .register-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
            transition: var(--transition);
        }

        .register-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideInDown 0.4s;
            border-left: 4px solid;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left-color: var(--danger);
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left-color: var(--primary);
        }

        .alert-icon {
            font-size: 20px;
            flex-shrink: 0;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .login-card {
                padding: 40px 30px;
            }

            .login-logo {
                width: 75px;
                height: 75px;
                font-size: 38px;
            }

            .login-title {
                font-size: 26px;
            }

            .form-input {
                padding: 14px 16px 14px 50px;
            }

            .form-options {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        /* Additional Features */
        .features {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            color: var(--muted);
            font-size: 14px;
        }

        .feature-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(32, 201, 151, 0.1));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <img src="{{ asset('images/portada.png') }}" alt="MAKIYAKU" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <h1 class="login-title">MAKIYAKU</h1>
                <p class="login-subtitle">Sistema de Riego Inteligente</p>
            </div>

            @if (session('status'))
                <div class="alert alert-success">
                    <span class="alert-icon">✅</span>
                    <div>{{ session('status') }}</div>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <span class="alert-icon">❌</span>
                    <div>
                        <strong>Error al iniciar sesión</strong>
                        <div style="margin-top: 4px;">{{ $errors->first() }}</div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="loginForm">
                @csrf

                <!-- Email -->
                <div class="form-group">
                    <label for="email" class="form-label">Correo Electrónico</label>
                    <div class="input-wrapper">
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input @error('email') error @enderror" 
                            value="{{ old('email') }}" 
                            required 
                            autofocus 
                            autocomplete="username"
                            placeholder="tu@email.com"
                        >
                        <span class="input-icon">📧</span>
                    </div>
                    @error('email')
                        <div class="error-message">
                            <span>⚠️</span>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input @error('password') error @enderror" 
                            required 
                            autocomplete="current-password"
                            placeholder="••••••••"
                        >
                        <span class="input-icon">🔒</span>
                        <span class="toggle-password" onclick="togglePassword()">👁️</span>
                    </div>
                    @error('password')
                        <div class="error-message">
                            <span>⚠️</span>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember_me" name="remember">
                        <label for="remember_me">Recordarme</label>
                    </div>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="forgot-password">
                            ¿Olvidaste tu contraseña?
                        </a>
                    @endif
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-login" id="loginBtn">
                    <span class="btn-text">Iniciar Sesión</span>
                    <div class="loading"></div>
                </button>
            </form>

            <div class="divider">
                <span>o</span>
            </div>

            <div class="register-link">
                ¿No tienes una cuenta? 
                <a href="{{ route('register') }}">Regístrate aquí</a>
            </div>

            <!-- Features -->
            <div class="features">
                <div class="feature-item">
                    <div class="feature-icon">💧</div>
                    <span>Monitoreo de humedad en tiempo real</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🌡️</div>
                    <span>Control de clima>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">📊</div>
                    <span>Alertas y recomendaciones</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle Password Visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }

        // Form Submit Loading State
        document.getElementById('loginForm').addEventListener('submit', function() {
            const loginBtn = document.getElementById('loginBtn');
            loginBtn.classList.add('loading');
            loginBtn.disabled = true;
        });

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.animation = 'slideInDown 0.4s reverse';
                    setTimeout(() => alert.remove(), 400);
                }, 5000);
            });
        });

        // Add floating animation to inputs on focus
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.parentElement.querySelector('.form-label').style.color = 'var(--primary)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.parentElement.querySelector('.form-label').style.color = 'var(--dark)';
            });
        });
    </script>
</body>
</html>