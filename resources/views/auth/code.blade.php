<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="verification-container">
                <div class="verification-card">
                    <h2 class="card-header">{{ __('Verificación de dos factores') }}</h2>
                    <div class="card-body">
                        @if (session('error'))
                            <div class="alert alert-danger" role="alert">
                                {{ session('error') }}
                            </div>
                        @endif

                        @if (session('warning'))
                            <div class="alert alert-warning" role="alert">
                                {{ session('warning') }}
                            </div>
                        @endif

                        @if (session('success'))
                            <div class="alert alert-success" role="alert">
                                {{ session('success') }}
                            </div>
                        @endif

                        <div class="code-info">
                            <p>Se ha enviado un código de verificación a tu correo electrónico.</p>
                        </div>

                        <form method="POST" action="{{ route('two-factor.verify-code') }}">
                            @csrf
                            <div class="form-group">
                                <label for="code">{{ __('Código de verificación') }}</label>
                                <input id="code"
                                       type="text"
                                       class="form-control @error('code') is-invalid @enderror"
                                       name="code"
                                       placeholder="Ingresa el código de 6 caracteres"
                                       maxlength="6"
                                       required
                                       autocomplete="code"
                                       autofocus>

                                @error('code')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-block">
                                    {{ __('Verificar') }}
                                </button>
                            </div>
                        </form>

                        <div class="additional-actions">
                            <form method="POST" action="{{ route('two-factor.send-code') }}" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-link resend-btn">
                                    ¿No recibiste el código? Reenviar
                                </button>
                            </form>

                            <form method="POST" action="{{ route('logout') }}" style="display: inline; margin-left: 10px;">
                                @csrf
                                <button type="submit" class="btn btn-link cancel-btn">
                                    Cancelar y cerrar sesión
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Variables CSS para temas */
    :root {
        /* Modo claro */
        --bg-color: #f8f9fa;
        --card-bg: #ffffff;
        --text-color: #212529;
        --border-color: #dee2e6;
        --input-bg: #ffffff;
        --input-text: #495057;
        --shadow: rgba(0, 0, 0, 0.1);
        --hover-shadow: rgba(0, 0, 0, 0.15);
    }

    /* Modo oscuro (preferencia del sistema) */
    @media (prefers-color-scheme: dark) {
        :root {
            --bg-color: #000000;
            --card-bg: #333333;
            --text-color: #ffffff;
            --border-color: #444444;
            --input-bg: #555555;
            --input-text: #ffffff;
            --shadow: rgba(0, 0, 0, 0.3);
            --hover-shadow: rgba(0, 0, 0, 0.4);
        }
    }

    /* Estilos base */
    body {
        background-color: var(--bg-color);
        font-family: 'Arial', sans-serif;
        color: var(--text-color);
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    .verification-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 20px;
    }

    .verification-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 4px 20px var(--shadow);
        transition: transform 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease;
        max-width: 450px;
        width: 100%;
        animation: fadeIn 0.6s ease-out;
    }

    .verification-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 8px 30px var(--hover-shadow);
    }

    .card-header {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        text-align: center;
        padding: 25px 20px;
        font-size: 1.6rem;
        font-weight: 600;
        margin: 0;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    }

    .card-body {
        padding: 30px 25px;
    }

    .code-info {
        background: linear-gradient(135deg, #e3f2fd, #bbdefb);
        border: 1px solid #2196f3;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 25px;
        color: #1976d2;
        font-size: 0.95rem;
        text-align: center;
    }

    /* Modo oscuro para code-info */
    @media (prefers-color-scheme: dark) {
        .code-info {
            background: linear-gradient(135deg, #1a237e, #283593);
            border-color: #3f51b5;
            color: #e3f2fd;
        }
    }

    .form-group {
        margin-bottom: 25px;
    }

    label {
        font-weight: 600;
        margin-bottom: 12px;
        display: block;
        color: var(--text-color);
        font-size: 1.1rem;
    }

    .form-control {
        border: 2px solid var(--border-color);
        border-radius: 10px;
        padding: 15px;
        font-size: 1.1rem;
        background-color: var(--input-bg);
        color: var(--input-text);
        transition: all 0.3s ease;
        width: 100%;
        text-align: center;
        letter-spacing: 2px;
        font-weight: 600;
    }

    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.3rem rgba(0, 123, 255, 0.25);
        outline: none;
        transform: scale(1.02);
    }

    .form-control::placeholder {
        color: var(--text-color);
        opacity: 0.6;
    }

    .btn-primary {
        background: linear-gradient(135deg, #007bff, #0056b3);
        border: none;
        font-weight: 600;
        padding: 15px;
        font-size: 1.1rem;
        border-radius: 10px;
        transition: all 0.3s ease;
        width: 100%;
        text-transform: uppercase;
        letter-spacing: 1px;
        box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #0056b3, #004085);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
    }

    .additional-actions {
        margin-top: 25px;
        text-align: center;
        padding-top: 20px;
        border-top: 1px solid var(--border-color);
    }

    .btn-link {
        color: #007bff;
        text-decoration: none;
        font-weight: 500;
        padding: 8px 12px;
        border-radius: 6px;
        transition: all 0.3s ease;
        border: none;
        background: none;
        cursor: pointer;
    }

    .btn-link:hover {
        color: #0056b3;
        background-color: rgba(0, 123, 255, 0.1);
        text-decoration: none;
    }

    .resend-btn {
        color: #28a745;
    }

    .resend-btn:hover {
        color: #1e7e34;
        background-color: rgba(40, 167, 69, 0.1);
    }

    .cancel-btn {
        color: #dc3545;
    }

    .cancel-btn:hover {
        color: #c82333;
        background-color: rgba(220, 53, 69, 0.1);
    }

    .alert {
        border-radius: 10px;
        font-weight: 500;
        padding: 15px;
        margin-bottom: 20px;
        border: none;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .alert-danger {
        background: linear-gradient(135deg, #f8d7da, #f1aeb5);
        color: #721c24;
        border-left: 4px solid #dc3545;
    }

    .alert-warning {
        background: linear-gradient(135deg, #fff3cd, #ffeaa7);
        color: #856404;
        border-left: 4px solid #ffc107;
    }

    .alert-success {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        color: #155724;
        border-left: 4px solid #28a745;
    }

    /* Modo oscuro para alertas */
    @media (prefers-color-scheme: dark) {
        .alert-danger {
            background: linear-gradient(135deg, #d32f2f, #c62828);
            color: #ffebee;
        }
        .alert-warning {
            background: linear-gradient(135deg, #f57c00, #ef6c00);
            color: #fff8e1;
        }
        .alert-success {
            background: linear-gradient(135deg, #1b5e20, #2e7d32);
            color: #c8e6c9;
        }
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-30px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .verification-container {
            padding: 10px;
        }

        .card-body {
            padding: 20px 15px;
        }

        .card-header {
            font-size: 1.4rem;
            padding: 20px 15px;
        }
    }
</style>
