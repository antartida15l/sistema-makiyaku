<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Registrarse - MAKIYAKU</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Ccircle cx='32' cy='32' r='30' fill='%2328a745'/%3E%3Cpath d='M20 44c2-14 10-22 26-24-1 16-9 24-24 26' fill='%23fff'/%3E%3C/svg%3E">
    
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
            content: '';
            position: absolute;
            font-size: 200px;
            opacity: 0.05;
            top: -50px;
            left: -50px;
            animation: float 6s ease-in-out infinite;
        }

        body::after {
            content: '';
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

        .register-container {
            width: 100%;
            max-width: 520px;
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

        .register-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 45px 40px;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .register-card::before {
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

        .register-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .register-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 22px;
            font-size: 40px;
            margin-bottom: 18px;
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .register-title {
            font-size: 28px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }

        .register-subtitle {
            color: var(--muted);
            font-size: 14px;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 22px;
            position: relative;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
            font-size: 13px;
            transition: var(--transition);
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            font-size: 18px;
            color: var(--muted);
            transition: var(--transition);
            pointer-events: none;
        }

        .form-input {
            width: 100%;
            padding: 14px 16px 14px 50px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 14px;
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
            right: 16px;
            cursor: pointer;
            font-size: 18px;
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
            font-size: 12px;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
            animation: shake 0.4s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
            display: none;
        }

        .password-strength.active {
            display: block;
        }

        .password-strength-bar {
            height: 100%;
            transition: var(--transition);
            border-radius: 2px;
        }

        .password-strength-bar.weak {
            width: 33%;
            background: var(--danger);
        }

        .password-strength-bar.medium {
            width: 66%;
            background: #ffc107;
        }

        .password-strength-bar.strong {
            width: 100%;
            background: var(--primary);
        }

        .password-hint {
            font-size: 11px;
            color: var(--muted);
            margin-top: 6px;
        }

        .btn-register {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 12px;
            color: var(--white);
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
            position: relative;
            overflow: hidden;
            margin-top: 10px;
        }

        .btn-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: var(--transition);
        }

        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.4);
        }

        .btn-register:hover::before {
            left: 100%;
        }

        .btn-register:active {
            transform: translateY(-1px);
        }

        .btn-register:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-register .loading {
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

        .btn-register.loading .btn-text {
            display: none;
        }

        .btn-register.loading .loading {
            display: block;
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 25px 0;
            color: var(--muted);
            font-size: 13px;
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

        .login-link {
            text-align: center;
            margin-top: 25px;
            color: var(--muted);
            font-size: 14px;
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
            transition: var(--transition);
        }

        .login-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInDown 0.4s;
            border-left: 4px solid;
            font-size: 13px;
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
            font-size: 18px;
            flex-shrink: 0;
        }

        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 20px 0;
            font-size: 13px;
            color: var(--dark);
        }

        .terms-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary);
            margin-top: 2px;
            flex-shrink: 0;
        }

        .terms-checkbox label {
            cursor: pointer;
            user-select: none;
        }

        .terms-checkbox a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .terms-checkbox a:hover {
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 580px) {
            .register-card {
                padding: 35px 28px;
            }

            .register-logo {
                width: 70px;
                height: 70px;
                font-size: 35px;
            }

            .register-title {
                font-size: 24px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .form-input {
                padding: 13px 14px 13px 45px;
            }
        }

        /* Success Animation */
        .success-checkmark {
            display: none;
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
        }

        .success-checkmark.show {
            display: block;
            animation: scaleIn 0.5s ease-out;
        }

        @keyframes scaleIn {
            0% {
                transform: scale(0);
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <div class="register-logo">
                     <img src="{{ asset('images/portada.png') }}" alt="MAKIYAKU" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <h1 class="register-title">Crear Cuenta</h1>
                <p class="register-subtitle">Únete a MAKIYAKU y gestiona tu sistema de riego</p>
            </div>

            @if (session('status'))
                <div class="alert alert-success">
                    <span class="alert-icon"><x-icono name="ok" :size="20" /></span>
                    <div>{{ session('status') }}</div>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    
                    <div>
                        <strong>Error en el registro</strong>
                        <div style="margin-top: 4px;">Por favor, verifica los campos marcados</div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" id="registerForm">
                @csrf

                <!-- Name -->
                <div class="form-group">
                    <label for="name" class="form-label">Nombre Completo</label>
                    <div class="input-wrapper">
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            class="form-input @error('name') error @enderror" 
                            value="{{ old('name') }}" 
                            required 
                            autofocus 
                            autocomplete="name"
                            placeholder="Juan Pérez"
                        >
                        
                    </div>
                    @error('name')
                        <div class="error-message">
                            <span><x-icono name="alerta" :size="20" /></span>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>

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
                            autocomplete="username"
                            placeholder="tu@email.com"
                        >
                        
                    </div>
                    @error('email')
                        <div class="error-message">
                            <span><x-icono name="alerta" :size="20" /></span>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>

                <!-- Password Fields in Row -->
                <div class="form-row">
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
                                autocomplete="new-password"
                                placeholder="••••••••"
                            >
                            
                            <span class="toggle-password" onclick="togglePassword('password', this)"></span>
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="password-strength-bar"></div>
                        </div>
                        <div class="password-hint">Mínimo 8 caracteres</div>
                        @error('password')
                            <div class="error-message">
                                <span><x-icono name="alerta" :size="20" /></span>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">Confirmar Contraseña</label>
                        <div class="input-wrapper">
                            <input 
                                type="password" 
                                id="password_confirmation" 
                                name="password_confirmation" 
                                class="form-input" 
                                required 
                                autocomplete="new-password"
                                placeholder="••••••••"
                            >
                            
                            <span class="toggle-password" onclick="togglePassword('password_confirmation', this)"></span>
                        </div>
                    </div>
                </div>

                <!-- Terms and Conditions -->
                <div class="terms-checkbox">
                    <input type="checkbox" id="terms" required>
                    <label for="terms">
                        Acepto los <a href="#" onclick="event.preventDefault()">términos y condiciones</a> 
                        y la <a href="#" onclick="event.preventDefault()">política de privacidad</a> de MAKIYAKU
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-register" id="registerBtn">
                    <span class="btn-text">Crear Mi Cuenta</span>
                    <div class="loading"></div>
                </button>
            </form>

            <div class="divider">
                <span>o</span>
            </div>

            <div class="login-link">
                ¿Ya tienes una cuenta? 
                <a href="{{ route('login') }}">Inicia sesión aquí</a>
            </div>
        </div>
    </div>

    <script>
        // Toggle Password Visibility
        function togglePassword(fieldId, element) {
            const passwordInput = document.getElementById(fieldId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                element.textContent = '';
            } else {
                passwordInput.type = 'password';
                element.textContent = '';
            }
        }

        // Password Strength Checker
        const passwordInput = document.getElementById('password');
        const strengthIndicator = document.getElementById('passwordStrength');
        const strengthBar = strengthIndicator.querySelector('.password-strength-bar');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            
            if (password.length === 0) {
                strengthIndicator.classList.remove('active');
                return;
            }
            
            strengthIndicator.classList.add('active');
            
            let strength = 0;
            
            // Check password strength
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            // Remove all classes
            strengthBar.className = 'password-strength-bar';
            
            // Add appropriate class
            if (strength <= 2) {
                strengthBar.classList.add('weak');
            } else if (strength === 3) {
                strengthBar.classList.add('medium');
            } else {
                strengthBar.classList.add('strong');
            }
        });

        // Form Submit Loading State
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const registerBtn = document.getElementById('registerBtn');
            const termsCheckbox = document.getElementById('terms');
            
            if (!termsCheckbox.checked) {
                e.preventDefault();
                alert('Debes aceptar los términos y condiciones para continuar');
                return;
            }
            
            registerBtn.classList.add('loading');
            registerBtn.disabled = true;
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
                const label = this.parentElement.parentElement.querySelector('.form-label');
                if (label) {
                    label.style.color = 'var(--primary)';
                }
            });
            
            input.addEventListener('blur', function() {
                const label = this.parentElement.parentElement.querySelector('.form-label');
                if (label) {
                    label.style.color = 'var(--dark)';
                }
            });
        });

        // Password Confirmation Match Validation
        const passwordConfirmInput = document.getElementById('password_confirmation');
        
        passwordConfirmInput.addEventListener('input', function() {
            if (this.value && this.value !== passwordInput.value) {
                this.classList.add('error');
            } else {
                this.classList.remove('error');
            }
        });
    </script>
</body>
</html>