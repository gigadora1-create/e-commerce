<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'GLE COLOMBIA') }}</title>

    <script>
        (function () {
            const media = window.matchMedia('(prefers-color-scheme: dark)');
            const root = document.documentElement;

            const applyTheme = (isDark) => {
                root.setAttribute('data-bs-theme', isDark ? 'dark' : 'light');
                root.classList.toggle('dark-mode', isDark);

                if (document.body) {
                    document.body.classList.toggle('dark-mode', isDark);
                }
            };

            applyTheme(media.matches);

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => applyTheme(media.matches), { once: true });
            }

            if (typeof media.addEventListener === 'function') {
                media.addEventListener('change', () => {
                    applyTheme(media.matches);
                });
            }
        })();
    </script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --auth-bg: #f8fafc;
            --auth-bg-alt: #ffffff;
            --auth-card-bg: #ffffff;
            --auth-card-border: #e5e7eb;
            --auth-text: #111827;
            --auth-text-muted: #6b7280;
            --auth-input-bg: #ffffff;
            --auth-input-border: #d1d5db;
            --auth-input-text: #111827;
            --auth-shadow: 0 20px 55px rgba(15, 23, 42, 0.10);
            --auth-primary: #dc2626;
            --auth-primary-hover: #b91c1c;
            --auth-primary-soft: #fee2e2;
            --auth-success-border: #bbf7d0;
            --auth-success-bg: #ecfdf5;
            --auth-success-text: #047857;
            --auth-danger-border: #fecaca;
            --auth-danger-bg: #fef2f2;
            --auth-danger-text: #b91c1c;
            --auth-warning-border: #fde68a;
            --auth-warning-bg: #fffbeb;
            --auth-warning-text: #b45309;
            --auth-info-border: #bfdbfe;
            --auth-info-bg: #eff6ff;
            --auth-info-text: #1d4ed8;
        }

        html[data-bs-theme="dark"] {
            --auth-bg: #080808;
            --auth-bg-alt: #111111;
            --auth-card-bg: #151515;
            --auth-card-border: #2f2f2f;
            --auth-text: #f5f5f5;
            --auth-text-muted: #a1a1aa;
            --auth-input-bg: #1d1d1d;
            --auth-input-border: #3a3a3a;
            --auth-input-text: #f5f5f5;
            --auth-shadow: 0 24px 70px rgba(0, 0, 0, 0.48);
            --auth-primary: #ef4444;
            --auth-primary-hover: #dc2626;
            --auth-primary-soft: #7f1d1d;
            --auth-success-border: #14532d;
            --auth-success-bg: #052e16;
            --auth-success-text: #86efac;
            --auth-danger-border: #7f1d1d;
            --auth-danger-bg: #4c1d1d;
            --auth-danger-text: #fecaca;
            --auth-warning-border: #78350f;
            --auth-warning-bg: #451a03;
            --auth-warning-text: #fdba74;
            --auth-info-border: #1e3a8a;
            --auth-info-bg: #172554;
            --auth-info-text: #bfdbfe;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            margin: 0;
            color: var(--auth-text);
            background:
                radial-gradient(circle at top left, rgba(220, 38, 38, 0.08), transparent 30%),
                radial-gradient(circle at bottom right, rgba(15, 23, 42, 0.05), transparent 35%),
                var(--auth-bg);
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .auth-shell {
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .auth-card {
            background: var(--auth-card-bg);
            border: 1px solid var(--auth-card-border);
            border-radius: 1.25rem;
            box-shadow: var(--auth-shadow);
            color: var(--auth-text);
            overflow: hidden;
        }

        .auth-card .card-body {
            color: var(--auth-text);
            padding: 2rem;
        }

        .card {
            background: var(--auth-card-bg);
            border-color: var(--auth-card-border);
            color: var(--auth-text);
        }

        .card-body {
            color: var(--auth-text);
        }

        .form-control,
        .form-select {
            background-color: var(--auth-input-bg);
            border-color: var(--auth-input-border);
            color: var(--auth-input-text);
        }

        .form-control:focus,
        .form-select:focus {
            background-color: var(--auth-input-bg);
            border-color: var(--auth-primary);
            color: var(--auth-input-text);
            box-shadow: 0 0 0 0.2rem rgba(220, 38, 38, 0.16);
        }

        .form-control::placeholder {
            color: var(--auth-text-muted);
        }

        .btn-primary {
            background-color: var(--auth-primary) !important;
            border-color: var(--auth-primary) !important;
            color: #ffffff !important;
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background-color: var(--auth-primary-hover) !important;
            border-color: var(--auth-primary-hover) !important;
        }

        .btn-outline-primary {
            color: var(--auth-primary);
            border-color: var(--auth-primary);
        }

        .btn-outline-primary:hover {
            background: var(--auth-primary);
            border-color: var(--auth-primary);
            color: #ffffff;
        }

        .btn-outline-secondary {
            color: var(--auth-text);
            border-color: var(--auth-card-border);
        }

        .btn-outline-secondary:hover {
            background: var(--auth-primary-soft);
            color: var(--auth-primary);
            border-color: var(--auth-primary-soft);
        }

        .text-muted {
            color: var(--auth-text-muted) !important;
        }

        .badge.bg-primary-subtle {
            background-color: var(--auth-primary-soft) !important;
            color: var(--auth-primary) !important;
        }

        .text-primary-emphasis {
            color: var(--auth-primary) !important;
        }

        .alert {
            border-color: var(--auth-card-border);
        }

        .alert-success {
            background-color: var(--auth-success-bg);
            border-color: var(--auth-success-border);
            color: var(--auth-success-text);
        }

        .alert-danger {
            background-color: var(--auth-danger-bg);
            border-color: var(--auth-danger-border);
            color: var(--auth-danger-text);
        }

        .alert-warning {
            background-color: var(--auth-warning-bg);
            border-color: var(--auth-warning-border);
            color: var(--auth-warning-text);
        }

        .alert-info {
            background-color: var(--auth-info-bg);
            border-color: var(--auth-info-border);
            color: var(--auth-info-text);
        }

        .bg-blue-100 {
            background-color: var(--auth-primary-soft) !important;
        }

        .text-blue-800 {
            color: var(--auth-primary) !important;
        }

        .focus\:border-blue-300:focus {
            border-color: var(--auth-primary) !important;
        }

        .focus\:ring-blue-200:focus {
            box-shadow: 0 0 0 0.2rem rgba(220, 38, 38, 0.16) !important;
        }

        .bg-blue-600 {
            background-color: var(--auth-primary) !important;
        }

        .hover\:bg-blue-700:hover {
            background-color: var(--auth-primary-hover) !important;
        }

        .focus\:ring-blue-500:focus {
            box-shadow: 0 0 0 0.2rem rgba(220, 38, 38, 0.24) !important;
        }

        .auth-shell .card.shadow {
            box-shadow: var(--auth-shadow) !important;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container auth-shell">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="card auth-card">
                    <div class="card-body">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
