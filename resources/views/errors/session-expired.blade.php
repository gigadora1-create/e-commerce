<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sesión expirada</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --brand: #0d6efd;
            --danger: #dc3545;
        }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f0f4ff 0%, #ffffff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji";
            color: #333;
        }
        .card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
        }
        .card-header {
            background: #fff;
            border-bottom: none;
            padding: 24px 24px 0 24px;
        }
        .badge-danger {
            background: var(--danger);
        }
        .lead {
            color: #555;
        }
        .illustration {
            width: 140px;
            height: 140px;
            margin: 0 auto 12px;
            display: block;
        }
        .actions .btn {
            padding: 10px 16px;
        }
        .btn-login {
            background: var(--brand);
            border-color: var(--brand);
        }
        .btn-login:hover { filter: brightness(0.95); }
        .btn-home { color: var(--brand); }
        .footer-note { color: #888; }
        .countdown { font-weight: 600; color: var(--brand); }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8 col-xl-6">
                <div class="card shadow-lg">
                    <div class="card-header text-center">
                        <span class="badge badge-danger rounded-pill px-3 py-2"><i class="fa-solid fa-lock me-2"></i> Sesión expirada</span>
                    </div>
                    <div class="card-body p-4">
                        <img class="illustration" src="{{ asset('images/error.svg') }}" alt="Sesión expirada"/>
                        <h4 class="text-center mb-2">Necesita iniciar sesión nuevamente</h4>
                        <p class="lead text-center mb-3">
                            Por seguridad, su sesión ha finalizado o no tiene permisos para acceder a esta página.
                        </p>
                        <p class="text-center mb-4">
                            Será redirigido al inicio de sesión en <span class="countdown" id="countdown">5</span> segundos.
                        </p>
                        <div class="d-flex gap-2 justify-content-center actions">
                            <a href="{{ route('login') }}" class="btn btn-login text-white">
                                <i class="fa-solid fa-right-to-bracket me-2"></i> Iniciar sesión
                            </a>
                            <a href="{{ url('/') }}" class="btn btn-outline-primary">
                                <i class="fa-solid fa-house me-2"></i> Ir al inicio
                            </a>
                        </div>
                    </div>
                    <div class="card-footer bg-white text-center py-3">
                        <small class="footer-note">Si el problema persiste, contacte al administrador del sistema.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            var seconds = 5;
            var el = document.getElementById('countdown');
            var interval = setInterval(function() {
                seconds--;
                if (el) el.textContent = String(seconds);
                if (seconds <= 0) {
                    clearInterval(interval);
                    window.location.href = "{{ route('login') }}";
                }
            }, 1000);
        })();
    </script>
</body>
</html>