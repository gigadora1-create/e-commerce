<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Portal de acceso al sistema de GRUPO LOGÍSTICO ESPECIALIZADO">
    <meta name="theme-color" content="#cc0000">
    <title>GRUPO LOGÍSTICO ESPECIALIZADO</title>
    <link rel="icon" href="/images/logogle.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/css/login.css" rel="stylesheet">
</head>
<body>

    <!-- FONDO GRADIENTE -->
    <div class="page-bg"></div>

    <!-- LED PULSANTE -->
    <div class="led-ring"></div>

    <!-- LOGIN -->
    <div class="login-wrapper">
        <div class="login-container" role="main">
            <img src="/images/logogle.png" alt="Logo de GRUPO LOGÍSTICO ESPECIALIZADO" class="logo" aria-label="Logo de la empresa">
            <h1 class="welcome-text">Bienvenido</h1>
            
            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <strong>Error:</strong> Credenciales incorrectas. Por favor, inténtalo de nuevo.
                </div>
            @endif
            
            <div class="device-message" aria-live="polite"></div>

            <form method="POST" action="{{ route('login.action') }}" id="loginForm" novalidate>
                @csrf

                <div class="form-group">
                    <input type="email" name="email" id="email" class="form-control" placeholder=" " 
                           required autofocus aria-required="true" aria-describedby="email-error">
                    <label for="email" class="form-label">Correo Electrónico</label>
                    <div id="email-error" class="error-message" aria-live="polite"></div>
                </div>

                <div class="form-group">
                    <input type="password" name="password" id="password" class="form-control" placeholder=" " 
                           required aria-required="true" aria-describedby="password-error" minlength="6">
                    <label for="password" class="form-label">Contraseña</label>
                    <span class="toggle-password" id="togglePassword" tabindex="0" role="button" aria-label="Mostrar contraseña">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                    </span>
                    <div id="password-error" class="error-message" aria-live="polite"></div>
                </div>

                <div class="custom-control">
                    <input type="checkbox" name="remember" id="remember" class="custom-control-input">
                    <label for="remember" class="custom-control-label">Recordar sesión</label>
                </div>

                <button type="submit" class="btn-login">Entrar</button>
            </form>

            <a href="{{ route('password.request') }}" class="forgot-password">
                ¿Olvidaste tu contraseña?
            </a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.login-container').style.opacity = '1';
            const togglePassword = document.querySelector('.toggle-password');
            const passwordInput = document.querySelector('#password');
            const deviceMessage = document.querySelector('.device-message');
            
            // Detectar dispositivo móvil y mostrar mensaje apropiado
            function detectDevice() {
                const isMobile = window.innerWidth < 768 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
                const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
                
                if (isMobile) {
                    // Ajustar elementos para mejor experiencia táctil
                    document.querySelectorAll('.form-control, .btn-login').forEach(el => {
                        el.style.padding = '20px 20px 12px';
                    });
                    
                    if (isTouchDevice) {
                        deviceMessage.textContent = 'Optimizado para dispositivos táctiles';
                        deviceMessage.style.display = 'block';
                        setTimeout(() => {
                            deviceMessage.style.opacity = '0';
                            setTimeout(() => deviceMessage.style.display = 'none', 500);
                        }, 3000);
                    }
                }
            }
            
            // Ejecutar detección de dispositivo
            detectDevice();
            
            // Ajustar en cambio de orientación
            window.addEventListener('resize', detectDevice);

            // Toggle password visibility
            togglePassword.addEventListener('click', function () {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' 
                    ? '<i class="fas fa-eye" aria-hidden="true"></i>' 
                    : '<i class="fas fa-eye-slash" aria-hidden="true"></i>';
                this.setAttribute('aria-label', type === 'password' ? 'Mostrar contraseña' : 'Ocultar contraseña');
            });

            // Keyboard support for password toggle
            togglePassword.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });

            // Client-side validation
            const loginForm = document.getElementById('loginForm');
            const emailInput = document.getElementById('email');
            const emailError = document.getElementById('email-error');
            const passwordError = document.getElementById('password-error');
            
            loginForm.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Reset error messages
                emailError.textContent = '';
                passwordError.textContent = '';
                
                // Email validation
                if (!emailInput.value.trim()) {
                    emailError.textContent = 'El correo electrónico es obligatorio';
                    emailInput.setAttribute('aria-invalid', 'true');
                    isValid = false;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value)) {
                    emailError.textContent = 'Ingrese un correo electrónico válido';
                    emailInput.setAttribute('aria-invalid', 'true');
                    isValid = false;
                } else {
                    emailInput.setAttribute('aria-invalid', 'false');
                }
                
                // Password validation
                if (!passwordInput.value) {
                    passwordError.textContent = 'La contraseña es obligatoria';
                    passwordInput.setAttribute('aria-invalid', 'true');
                    isValid = false;
                } else if (passwordInput.value.length < 6) {
                    passwordError.textContent = 'La contraseña debe tener al menos 6 caracteres';
                    passwordInput.setAttribute('aria-invalid', 'true');
                    isValid = false;
                } else {
                    passwordInput.setAttribute('aria-invalid', 'false');
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>