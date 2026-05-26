<!DOCTYPE html>
<html lang="pt-BR">

{{-- resources/views/auth/forgot-password.blade.php --}}


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css" rel="stylesheet">

    <link rel="icon" href="{{ asset('img/favicons/favicon.svg') }}" type="image/svg+xml">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('img/favicons/favicon-16.png') }}"
        media="(prefers-color-scheme: light)" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('img/favicons/favicon-16.png') }}"
        media="(prefers-color-scheme: light)">
    <link rel="icon" sizes="32x32" href="{{ asset('img/favicons/favicon-32.svg') }}"
        media="(prefers-color-scheme: light)">
    <link rel="icon" sizes="48x48" href="{{ asset('img/favicons/favicon-48.png') }}"
        media="(prefers-color-scheme: light)">
    <link rel="icon" sizes="192x192" href="{{ asset('img/favicons/favicon-192.png') }}"
        media="(prefers-color-scheme: light)">
    <link rel="icon" sizes="512x512" href="{{ asset('img/favicons/favicon-512.png') }}"
        media="(prefers-color-scheme: light)">

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('img/favicons/favicon-16-dark.png') }}"
        media="(prefers-color-scheme: dark)" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('img/favicons/favicon-16-dark.png') }}"
        media="(prefers-color-scheme: dark)">
    <link rel="icon" sizes="32x32" href="{{ asset('img/favicons/favicon-32-dark.png') }}"
        media="(prefers-color-scheme: dark)">
    <link rel="icon" sizes="48x48" href="{{ asset('img/favicons/favicon-48-dark.png') }}"
        media="(prefers-color-scheme: dark)">
    <link rel="icon" sizes="192x192" href="{{ asset('img/favicons/favicon-192-dark.png') }}"
        media="(prefers-color-scheme: dark)">
    <link rel="icon" sizes="512x512" href="{{ asset('img/favicons/favicon-512-dark.png') }}"
        media="(prefers-color-scheme: dark)">

    <style>
        :root {
            --primary-color: #005e82;
            --primary-light: #0088b8;
            --primary-dark: #003d56;
            --secondary-color: #be0a13;
            --secondary-light: #e60c17;
            --secondary-dark: #8a0710;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --bg-gradient-start: #0a192f;
            --bg-gradient-mid: #003d56;
            --bg-gradient-end: #005e82;
            --card-bg: #ffffff;
            --text-color: #1e293b;
            --text-muted: #64748b;
            --input-bg: #f8fafc;
            --input-border: #e2e8f0;
            --input-focus: #005e82;
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.12);
            --shadow-lg: 0 16px 48px rgba(0, 0, 0, 0.15);
            --shadow-xl: 0 24px 64px rgba(0, 0, 0, 0.2);
        }

        [data-theme="dark"] {
            --primary-color: #38bdf8;
            --primary-light: #60a5fa;
            --primary-dark: #0ea5e9;
            --secondary-color: #f87171;
            --secondary-light: #fca5a5;
            --secondary-dark: #ef4444;
            --success-color: #34d399;
            --warning-color: #fbbf24;
            --info-color: #60a5fa;
            --bg-gradient-start: #0f172a;
            --bg-gradient-mid: #1e293b;
            --bg-gradient-end: #334155;
            --card-bg: #1e293b;
            --text-color: #f1f5f9;
            --text-muted: #94a3b8;
            --input-bg: #0f172a;
            --input-border: #334155;
            --input-focus: #38bdf8;
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.5);
            --shadow-lg: 0 16px 48px rgba(0, 0, 0, 0.6);
            --shadow-xl: 0 24px 64px rgba(0, 0, 0, 0.7);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
            background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-mid) 50%, var(--bg-gradient-end) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            position: relative;
            overflow-x: hidden;
            transition: background 0.3s ease;
        }

        body::before {
            content: '';
            position: fixed;
            top: -50%;
            right: -20%;
            width: 1000px;
            height: 1000px;
            background: radial-gradient(circle, rgba(14, 165, 233, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 1;
        }

        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .theme-toggle-btn {
            background: var(--card-bg);
            border: 2px solid var(--input-border);
            width: 52px;
            height: 52px;
            border-radius: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
        }

        .theme-toggle-btn:hover {
            transform: scale(1.05);
            border-color: var(--primary-color);
        }

        .theme-toggle-btn:active {
            transform: scale(0.98);
        }

        .theme-toggle-btn i {
            font-size: 22px;
            color: var(--primary-color);
            transition: color 0.3s ease;
        }

        .forgot-container {
            width: 100%;
            max-width: 520px;
            position: relative;
            z-index: 10;
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .forgot-card {
            background: var(--card-bg);
            border-radius: 28px;
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .forgot-header {
            background-color: #0088b8;
            padding: 44px 32px 36px;
            text-align: center;
            position: relative;
        }

        .forgot-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }

        .icon-container {
            width: 100px;
            height: 100px;
            margin: 0 auto 24px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }

        .icon-container i {
            font-size: 48px;
            color: #ffffff;
        }

        .forgot-title {
            color: #ffffff;
            font-size: 30px;
            font-weight: 700;
            margin: 0 0 12px;
            text-shadow: 0 2px 12px rgba(0, 0, 0, 0.2);
            letter-spacing: -0.5px;
        }

        .forgot-subtitle {
            color: rgba(255, 255, 255, 0.92);
            font-size: 15px;
            margin: 0;
            font-weight: 400;
            line-height: 1.6;
        }

        .forgot-body {
            padding: 36px 32px 40px;
        }

        .info-box {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(59, 130, 246, 0.04) 100%);
            border: 2px solid rgba(59, 130, 246, 0.2);
            border-radius: 16px;
            padding: 18px 20px;
            margin-bottom: 28px;
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }

        .info-box i {
            font-size: 24px;
            color: var(--info-color);
            flex-shrink: 0;
            margin-top: 2px;
        }

        .info-box-content {
            flex: 1;
        }

        .info-box-title {
            font-weight: 600;
            font-size: 15px;
            color: var(--text-color);
            margin: 0 0 6px;
        }

        .info-box-text {
            font-size: 14px;
            color: var(--text-muted);
            margin: 0;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 10px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-label i {
            color: var(--primary-color);
            font-size: 16px;
        }

        .form-control {
            background: var(--input-bg);
            border: 2px solid var(--input-border);
            color: var(--text-color);
            padding: 15px 18px;
            border-radius: 14px;
            font-size: 15px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .form-control:hover {
            border-color: var(--primary-color);
        }

        .form-control:focus {
            background: var(--input-bg);
            border-color: var(--input-focus);
            color: var(--text-color);
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.1);
            outline: none;
        }

        .form-control::placeholder {
            color: var(--text-muted);
            opacity: 0.6;
        }

        .form-control.is-invalid {
            border-color: var(--secondary-color);
            background-color: rgba(239, 68, 68, 0.05);
        }

        .form-control.is-invalid:focus {
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
        }

        .invalid-feedback {
            font-size: 13px;
            margin-top: 6px;
            color: var(--secondary-color);
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }

        .invalid-feedback i {
            font-size: 14px;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            border: none;
            color: #ffffff;
            padding: 16px 24px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 14px;
            width: 100%;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            box-shadow: 0 8px 24px rgba(14, 165, 233, 0.3);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(14, 165, 233, 0.4);
        }

        .btn-submit:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-submit i {
            font-size: 18px;
        }

        .back-to-login {
            text-align: center;
            margin-top: 24px;
        }

        .back-to-login a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 8px;
        }

        .back-to-login a:hover {
            color: var(--secondary-color);
            background: rgba(56, 189, 248, 0.05);
        }

        .spinner-border-sm {
            width: 18px;
            height: 18px;
            border-width: 2px;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-overlay.active {
            display: flex;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.2);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        #toast-container {
            pointer-events: none;
        }

        #toast-container>* {
            pointer-events: auto;
        }

        #toast-container>.toast {
            background-color: #ffffff;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2), 0 0 1px rgba(0, 0, 0, 0.1);
            border-radius: 16px;
            padding: 18px 20px;
            margin-bottom: 16px;
            width: 380px;
            max-width: calc(100vw - 40px);
            border: none;
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        #toast-container>.toast::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
        }

        #toast-container>.toast-success {
            background-color: #f0fdf4;
            border-left: 5px solid #10b981;
        }

        #toast-container>.toast-success::before {
            background-color: #10b981;
        }

        #toast-container>.toast-error {
            background-color: #fef2f2;
            border-left: 5px solid #ef4444;
        }

        #toast-container>.toast-error::before {
            background-color: #ef4444;
        }

        #toast-container>.toast-info {
            background-color: #eff6ff;
            border-left: 5px solid #3b82f6;
        }

        #toast-container>.toast-info::before {
            background-color: #3b82f6;
        }

        #toast-container>.toast-warning {
            background-color: #fffbeb;
            border-left: 5px solid #f59e0b;
        }

        #toast-container>.toast-warning::before {
            background-color: #f59e0b;
        }

        [data-theme="dark"] #toast-container>.toast {
            background-color: #1e293b;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.6), 0 0 1px rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] #toast-container>.toast-success {
            background-color: #064e3b;
        }

        [data-theme="dark"] #toast-container>.toast-error {
            background-color: #7f1d1d;
        }

        [data-theme="dark"] #toast-container>.toast-info {
            background-color: #1e3a8a;
        }

        [data-theme="dark"] #toast-container>.toast-warning {
            background-color: #78350f;
        }

        #toast-container>.toast-info,
        #toast-container>.toast-warning,
        #toast-container>.toast-success,
        #toast-container>.toast-error {
            background-image: none !important;
        }

        #toast-container>.toast .toast-title {
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 6px;
            letter-spacing: 0.2px;
            display: flex;
            align-items: center;
            gap: 10px;
            line-height: 1.4;
        }

        #toast-container>.toast-success .toast-title {
            color: #059669;
        }

        #toast-container>.toast-error .toast-title {
            color: #dc2626;
        }

        #toast-container>.toast-info .toast-title {
            color: #2563eb;
        }

        #toast-container>.toast-warning .toast-title {
            color: #d97706;
        }

        [data-theme="dark"] #toast-container>.toast-success .toast-title {
            color: #34d399;
        }

        [data-theme="dark"] #toast-container>.toast-error .toast-title {
            color: #f87171;
        }

        [data-theme="dark"] #toast-container>.toast-info .toast-title {
            color: #60a5fa;
        }

        [data-theme="dark"] #toast-container>.toast-warning .toast-title {
            color: #fbbf24;
        }

        #toast-container>.toast .toast-title::before {
            font-family: 'bootstrap-icons';
            font-size: 22px;
            line-height: 1;
            flex-shrink: 0;
        }

        #toast-container>.toast-success .toast-title::before {
            content: '\f26b';
            color: #10b981;
        }

        #toast-container>.toast-error .toast-title::before {
            content: '\f33a';
            color: #ef4444;
        }

        #toast-container>.toast-info .toast-title::before {
            content: '\f431';
            color: #3b82f6;
        }

        #toast-container>.toast-warning .toast-title::before {
            content: '\f33a';
            color: #f59e0b;
        }

        [data-theme="dark"] #toast-container>.toast-success .toast-title::before {
            color: #34d399;
        }

        [data-theme="dark"] #toast-container>.toast-error .toast-title::before {
            color: #f87171;
        }

        [data-theme="dark"] #toast-container>.toast-info .toast-title::before {
            color: #60a5fa;
        }

        [data-theme="dark"] #toast-container>.toast-warning .toast-title::before {
            color: #fbbf24;
        }

        #toast-container>.toast .toast-message {
            font-weight: 500;
            font-size: 14px;
            line-height: 1.6;
            color: #475569;
            padding-left: 32px;
        }

        [data-theme="dark"] #toast-container>.toast .toast-message {
            color: #cbd5e1;
        }

        #toast-container>.toast .toast-close-button {
            position: absolute;
            right: 12px;
            top: 12px;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.05);
            color: #64748b;
            opacity: 0.7;
            font-size: 20px;
            font-weight: 700;
            line-height: 1;
            transition: all 0.2s ease;
        }

        #toast-container>.toast .toast-close-button:hover {
            opacity: 1;
            background: rgba(0, 0, 0, 0.1);
            transform: scale(1.1);
        }

        [data-theme="dark"] #toast-container>.toast .toast-close-button {
            background: rgba(255, 255, 255, 0.1);
            color: #cbd5e1;
        }

        [data-theme="dark"] #toast-container>.toast .toast-close-button:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        #toast-container>.toast .toast-progress {
            position: absolute;
            left: 0;
            bottom: 0;
            height: 4px;
            opacity: 0.8;
        }

        #toast-container>.toast-success .toast-progress {
            background-color: #10b981;
        }

        #toast-container>.toast-error .toast-progress {
            background-color: #ef4444;
        }

        #toast-container>.toast-info .toast-progress {
            background-color: #3b82f6;
        }

        #toast-container>.toast-warning .toast-progress {
            background-color: #f59e0b;
        }

        @media (max-width: 768px) {
            body::before {
                width: 600px;
                height: 600px;
            }

            .theme-toggle {
                top: 16px;
                right: 16px;
            }

            .theme-toggle-btn {
                width: 48px;
                height: 48px;
            }

            .forgot-container {
                max-width: 480px;
            }

            .forgot-card {
                border-radius: 24px;
            }

            .forgot-header {
                padding: 36px 28px 32px;
            }

            .icon-container {
                width: 90px;
                height: 90px;
                margin-bottom: 20px;
            }

            .icon-container i {
                font-size: 42px;
            }

            .forgot-title {
                font-size: 27px;
            }

            .forgot-subtitle {
                font-size: 14px;
            }

            .forgot-body {
                padding: 32px 28px 36px;
            }

            .info-box {
                padding: 16px 18px;
                margin-bottom: 24px;
            }

            .info-box i {
                font-size: 22px;
            }
        }

        @media (max-width: 425px) {
            body {
                padding: 16px 12px;
            }

            .forgot-container {
                max-width: 100%;
            }

            .forgot-card {
                border-radius: 20px;
            }

            .forgot-header {
                padding: 32px 24px 28px;
            }

            .icon-container {
                width: 80px;
                height: 80px;
                margin-bottom: 18px;
            }

            .icon-container i {
                font-size: 38px;
            }

            .forgot-title {
                font-size: 24px;
            }

            .forgot-subtitle {
                font-size: 13px;
            }

            .forgot-body {
                padding: 28px 24px 32px;
            }

            .info-box {
                padding: 14px 16px;
                margin-bottom: 20px;
                gap: 12px;
            }

            .info-box i {
                font-size: 20px;
            }

            .info-box-title {
                font-size: 14px;
            }

            .info-box-text {
                font-size: 13px;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .form-label {
                font-size: 13px;
            }

            .form-control {
                padding: 14px 16px;
                font-size: 14px;
            }

            .btn-submit {
                padding: 15px 20px;
                font-size: 15px;
            }
        }

        @media (max-width: 375px) {
            body {
                padding: 12px 10px;
            }

            .forgot-card {
                border-radius: 18px;
            }

            .forgot-header {
                padding: 28px 20px 24px;
            }

            .icon-container {
                width: 70px;
                height: 70px;
                margin-bottom: 16px;
            }

            .icon-container i {
                font-size: 34px;
            }

            .forgot-title {
                font-size: 22px;
            }

            .forgot-subtitle {
                font-size: 12px;
            }

            .forgot-body {
                padding: 24px 20px 28px;
            }

            .info-box {
                padding: 12px 14px;
                font-size: 12px;
            }

            .form-label {
                font-size: 12px;
            }

            .form-control {
                padding: 13px 15px;
                border-radius: 12px;
            }

            .btn-submit {
                padding: 14px 18px;
                font-size: 14px;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>

<body>
    <div class="theme-toggle">
        <button type="button" class="theme-toggle-btn" id="themeToggle" aria-label="Alternar tema">
            <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
        </button>
    </div>

    <div class="forgot-container">
        <div class="forgot-card">
            <div class="forgot-header">
                <div class="icon-container">
                    <i class="bi bi-key"></i>
                </div>
                <h1 class="forgot-title">Recuperar Senha</h1>
                <p class="forgot-subtitle">Insira seu e-mail e enviaremos instruções para redefinir sua senha</p>
            </div>

            <div class="forgot-body">
                <div class="info-box">
                    <i class="bi bi-info-circle-fill"></i>
                    <div class="info-box-content">
                        <p class="info-box-title">Como funciona?</p>
                        <p class="info-box-text">Você receberá um link seguro no seu e-mail para criar uma nova senha.
                            O link expira em 60 minutos.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('password.email') }}" id="forgotForm" novalidate>
                    @csrf

                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope-fill"></i>
                            <span>E-mail</span>
                        </label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                            id="email" name="email" value="{{ old('email') }}"
                            placeholder="seuemail@exemplo.com" required autofocus autocomplete="email">
                        @error('email')
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-circle-fill"></i>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="bi bi-send-fill"></i>
                        <span>Enviar Link de Recuperação</span>
                    </button>
                </form>

                <div class="back-to-login">
                    <a href="{{ route('login') }}">
                        <i class="bi bi-arrow-left"></i>
                        <span>Voltar para o login</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/toastr.min.js"></script>

    <script>
        (function() {
            'use strict';

            const elements = {
                themeToggle: document.getElementById('themeToggle'),
                themeIcon: document.getElementById('themeIcon'),
                forgotForm: document.getElementById('forgotForm'),
                submitBtn: document.getElementById('submitBtn'),
                emailInput: document.getElementById('email'),
                loadingOverlay: document.getElementById('loadingOverlay')
            };

            toastr.options = {
                closeButton: true,
                newestOnTop: true,
                progressBar: true,
                positionClass: 'toast-top-right',
                preventDuplicates: true,
                timeOut: 5000,
                extendedTimeOut: 1000,
                showEasing: 'swing',
                hideEasing: 'linear',
                showMethod: 'slideDown',
                hideMethod: 'slideUp',
                showDuration: 400,
                hideDuration: 400,
                tapToDismiss: true,
                closeHtml: '<button type="button">&times;</button>',
                escapeHtml: false
            };

            function initTheme() {
                const savedTheme = localStorage.getItem('theme') || 'light';
                document.documentElement.setAttribute('data-theme', savedTheme);
                updateThemeIcon(savedTheme);

                elements.themeToggle.addEventListener('click', function() {
                    const currentTheme = document.documentElement.getAttribute('data-theme');
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

                    document.documentElement.setAttribute('data-theme', newTheme);
                    localStorage.setItem('theme', newTheme);
                    updateThemeIcon(newTheme);

                    toastr.info('Tema ' + (newTheme === 'dark' ? 'escuro' : 'claro') + ' ativado',
                        'Tema alterado');
                });
            }

            function updateThemeIcon(theme) {
                elements.themeIcon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
            }

            function initFormValidation() {
                elements.forgotForm.addEventListener('submit', function(event) {
                    let isValid = true;

                    const email = elements.emailInput.value.trim();
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                    elements.emailInput.classList.remove('is-invalid');

                    if (!email || !emailRegex.test(email)) {
                        isValid = false;
                        elements.emailInput.classList.add('is-invalid');
                    }

                    if (!isValid) {
                        event.preventDefault();
                        toastr.error('Por favor, digite um e-mail válido.', 'Erro de validação');
                        return false;
                    }

                    elements.submitBtn.disabled = true;
                    elements.submitBtn.innerHTML =
                        '<span class="spinner-border spinner-border-sm"></span><span>Enviando...</span>';
                    elements.loadingOverlay.classList.add('active');
                });
            }

            function initSessionMessages() {
                @if (session('status'))
                    toastr.success('{{ session('status') }}', 'E-mail enviado!');
                @endif

                @if (session('error'))
                    toastr.error('{{ session('error') }}', 'Erro!');
                @endif

                @if (session('info'))
                    toastr.info('{{ session('info') }}', 'Informação');
                @endif

                @if (session('warning'))
                    toastr.warning('{{ session('warning') }}', 'Atenção!');
                @endif

                @if ($errors->any())
                    toastr.error('{{ $errors->first() }}', 'Erro de validação');
                @endif
            }

            function init() {
                initTheme();
                initFormValidation();
                initSessionMessages();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }

        })();
    </script>

</body>

</html>
