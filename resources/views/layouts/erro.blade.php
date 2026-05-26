{{-- resources/views/layouts/erro.blade.php --}}

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title') - {{ config('app.name') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --primary-color: #005e82;
            --secondary-color: #0088b8;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --bg-light: #f8fafc;
            --bg-card: #ffffff;
            --border-color: #e2e8f0;
        }

        [data-theme="dark"] {
            --primary-color: #0088b8;
            --secondary-color: #00a8e0;
            --text-dark: #f1f5f9;
            --text-muted: #94a3b8;
            --bg-light: #0f172a;
            --bg-card: #1e293b;
            --border-color: #334155;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
            background: linear-gradient(135deg, #0a192f 0%, #003d56 50%, #005e82 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            -webkit-font-smoothing: antialiased;
        }

        .error-container {
            max-width: 700px;
            width: 100%;
            background: var(--bg-card);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .error-header {
            background: linear-gradient(135deg, @yield('gradient-start', '#005e82')

                0%, @yield('gradient-end', '#0088b8')

                100%);
            padding: 40px 25px;
            text-align: center;
            color: white;
        }

        .error-icon {
            width: 140px;
            height: 140px;
            margin: 0 auto 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
        }

        .error-code {
            font-size: 72px;
            font-weight: 900;

            color: @yield('error-color', '#005e82')

            ;
            letter-spacing: -2px;
        }

        .error-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .error-subtitle {
            font-size: 17px;
            opacity: 0.95;
            max-width: 500px;
            margin: 0 auto;
        }

        .error-body {
            padding: 35px 25px;
        }

        .btn-custom-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            color: white;
            padding: 12px 28px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(0, 94, 130, 0.3);
            transition: all 0.3s ease;
        }

        .btn-custom-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 94, 130, 0.4);
            color: white;
        }

        .btn-custom-secondary {
            background: var(--bg-card);
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 12px 28px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .btn-custom-secondary:hover {
            background: var(--bg-light);
            transform: translateY(-2px);
            color: var(--primary-color);
        }

        .info-card {
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.08) 0%, rgba(0, 136, 184, 0.04) 100%);
            border: 2px solid rgba(0, 94, 130, 0.2);
            border-left: 5px solid var(--primary-color);
            border-radius: 12px;
            padding: 18px;
        }

        .info-card i {
            color: var(--primary-color);
            font-size: 22px;
            margin-right: 10px;
        }

        .warning-card {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.08) 0%, rgba(245, 158, 11, 0.04) 100%);
            border: 2px solid rgba(245, 158, 11, 0.25);
            border-left: 5px solid #f59e0b;
            border-radius: 12px;
            padding: 18px;
        }

        .warning-card i {
            color: #f59e0b;
            font-size: 22px;
            margin-right: 10px;
        }

        .danger-card {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.08) 0%, rgba(239, 68, 68, 0.04) 100%);
            border: 2px solid rgba(239, 68, 68, 0.25);
            border-left: 5px solid #ef4444;
            border-radius: 12px;
            padding: 18px;
        }

        .danger-card i {
            color: #ef4444;
            font-size: 22px;
            margin-right: 10px;
        }

        .error-footer {
            background: var(--bg-light);
            padding: 30px 25px;
            text-align: center;
            border-top: 1px solid var(--border-color);
        }

        .footer-brand {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 12px;
        }

        .footer-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            margin: 0 12px;
            transition: color 0.3s ease;
        }

        .footer-link:hover {
            color: var(--secondary-color);
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .error-header {
                padding: 30px 20px;
            }

            .error-icon {
                width: 110px;
                height: 110px;
            }

            .error-code {
                font-size: 56px;
            }

            .error-title {
                font-size: 26px;
            }

            .error-subtitle {
                font-size: 15px;
            }

            .error-body {
                padding: 25px 20px;
            }

            .btn-custom-primary,
            .btn-custom-secondary {
                width: 100%;
                margin-bottom: 8px;
            }

            .footer-link {
                display: block;
                margin: 8px 0;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: 10px;
            }

            .error-container {
                border-radius: 16px;
            }

            .error-header {
                padding: 25px 15px;
            }

            .error-body {
                padding: 20px 15px;
            }

            .error-footer {
                padding: 25px 15px;
            }
        }

        [data-theme="dark"] body {
            background: linear-gradient(135deg, #020617 0%, #0a1929 50%, #0f2942 100%);
        }

        [data-theme="dark"] .error-container {
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
        }

        [data-theme="dark"] .error-icon {
            background: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .error-code {
            color: var(--primary-color);
        }

        [data-theme="dark"] .error-body {
            color: var(--text-dark);
        }

        [data-theme="dark"] .error-body p,
        [data-theme="dark"] .error-body li {
            color: var(--text-muted);
        }

        [data-theme="dark"] .btn-custom-secondary:hover {
            background: var(--bg-light);
        }

        [data-theme="dark"] .text-muted {
            color: var(--text-muted) !important;
        }

        [data-theme="dark"] .warning-card {
            border-left-color: #fbbf24;
        }

        [data-theme="dark"] .warning-card i {
            color: #fbbf24;
        }

        [data-theme="dark"] .danger-card {
            border-left-color: #f87171;
        }

        [data-theme="dark"] .danger-card i {
            color: #f87171;
        }
    </style>
    @yield('extra-styles')
    <script>
        (function(){var t=localStorage.getItem('theme');if(t==='dark'||((!t)&&window.matchMedia('(prefers-color-scheme:dark)').matches)){document.documentElement.setAttribute('data-theme','dark')}})();
    </script>
</head>

<body>
    <div class="error-container">
        <div class="error-header">
            <div class="error-icon">
                <div class="error-code">@yield('code')</div>
            </div>
            <h1 class="error-title">@yield('message')</h1>
            <p class="error-subtitle">@yield('subtitle')</p>
        </div>

        <div class="error-body">
            @yield('content')

            <div class="d-flex flex-column flex-md-row gap-2 justify-content-center mt-3 mb-3">
                @yield('actions')
            </div>

            @yield('extra-content')
        </div>

        <div class="error-footer">
            <div class="footer-brand">{{ config('app.name') }}</div>
            <p class="text-muted mb-3 small">Se você acredita que isso é um erro, entre em contato com o suporte.</p>

            <p class="text-muted small mb-0">
                © {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
            </p>
        </div>
    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
