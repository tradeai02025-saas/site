<!DOCTYPE html>
<html lang="pt-BR">

{{-- resources/views/auth/login.blade.php --}}

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

        .login-container {
            width: 100%;
            max-width: 480px;
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

        .login-card {
            background: var(--card-bg);
            border-radius: 28px;
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .login-header {
            background-color: #0088b8;
            padding: 44px 32px 36px;
            text-align: center;
            position: relative;
        }

        .login-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }

        .logo-container {
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
        }

        .logo-image {
            max-width: 180px;
            height: auto;
            display: block;
            margin: 0 auto;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.15));
        }

        .login-title {
            color: #ffffff;
            font-size: 30px;
            font-weight: 700;
            margin: 0 0 8px;
            text-shadow: 0 2px 12px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 2;
            letter-spacing: -0.5px;
        }

        .login-subtitle {
            color: rgba(255, 255, 255, 0.92);
            font-size: 15px;
            margin: 0;
            font-weight: 400;
            position: relative;
            z-index: 2;
        }

        .login-body {
            padding: 36px 32px 40px;
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

        .password-wrapper {
            position: relative;
        }

        .password-wrapper .form-control {
            padding-right: 50px;
        }

        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 8px;
            font-size: 20px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            width: 36px;
            height: 36px;
        }

        .password-toggle:hover {
            color: var(--primary-color);
            background: rgba(56, 189, 248, 0.1);
        }

        .btn-login {
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

        .btn-login:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(14, 165, 233, 0.4);
        }

        .btn-login:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-login i {
            font-size: 18px;
        }

        .forgot-password {
            text-align: center;
            margin-top: 24px;
        }

        .forgot-password a {
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

        .forgot-password a:hover {
            color: var(--secondary-color);
            background: rgba(56, 189, 248, 0.05);
        }

        .alert {
            border-radius: 14px;
            border: none;
            padding: 16px 18px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            overflow: hidden;
            font-weight: 500;
        }

        .alert::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
        }

        .alert i {
            font-size: 20px;
            flex-shrink: 0;
        }

        .alert-content {
            flex: 1;
        }

        .alert-close {
            background: transparent;
            border: none;
            color: inherit;
            opacity: 0.6;
            cursor: pointer;
            padding: 4px;
            font-size: 20px;
            line-height: 1;
            transition: opacity 0.3s ease;
            border-radius: 4px;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .alert-close:hover {
            opacity: 1;
            background: rgba(0, 0, 0, 0.1);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.12) 0%, rgba(239, 68, 68, 0.06) 100%);
            color: var(--secondary-color);
        }

        .alert-danger::before {
            background: var(--secondary-color);
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(16, 185, 129, 0.06) 100%);
            color: var(--success-color);
        }

        .alert-success::before {
            background: var(--success-color);
        }

        .alert-info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.12) 0%, rgba(59, 130, 246, 0.06) 100%);
            color: var(--info-color);
        }

        .alert-info::before {
            background: var(--info-color);
        }

        .alert-warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.12) 0%, rgba(245, 158, 11, 0.06) 100%);
            color: var(--warning-color);
        }

        .alert-warning::before {
            background: var(--warning-color);
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

        @media (max-width: 1440px) {
            .login-container {
                max-width: 460px;
            }
        }

        @media (max-width: 1024px) {
            body {
                padding: 20px 16px;
            }

            .login-container {
                max-width: 440px;
            }

            .login-header {
                padding: 40px 28px 32px;
            }

            .login-title {
                font-size: 28px;
            }

            .login-body {
                padding: 32px 28px 36px;
            }
        }

        @media (max-width: 768px) {

            body::before,
            body::after {
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

            .theme-toggle-btn i {
                font-size: 20px;
            }

            .login-container {
                max-width: 420px;
            }

            .login-card {
                border-radius: 24px;
            }

            .login-header {
                padding: 36px 24px 28px;
            }

            .logo-image {
                max-width: 160px;
            }

            .login-title {
                font-size: 26px;
            }

            .login-subtitle {
                font-size: 14px;
            }

            .login-body {
                padding: 28px 24px 32px;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .btn-login {
                padding: 15px 20px;
                font-size: 15px;
            }

            #toast-container {
                width: calc(100% - 32px);
                left: 16px;
                right: 16px;
            }

            #toast-container>.toast {
                width: 100%;
                max-width: 100%;
            }
        }

        @media (max-width: 425px) {
            body {
                padding: 16px 12px;
            }

            body::before,
            body::after {
                width: 400px;
                height: 400px;
            }

            .login-container {
                max-width: 100%;
            }

            .login-card {
                border-radius: 20px;
            }

            .login-header {
                padding: 32px 20px 24px;
            }

            .logo-image {
                max-width: 140px;
            }

            .login-title {
                font-size: 24px;
            }

            .login-subtitle {
                font-size: 13px;
            }

            .login-body {
                padding: 24px 20px 28px;
            }

            .form-label {
                font-size: 13px;
            }

            .form-control {
                padding: 14px 16px;
                font-size: 14px;
            }

            .form-group {
                margin-bottom: 18px;
            }

            .btn-login {
                padding: 14px 18px;
                font-size: 14px;
                letter-spacing: 1px;
            }

            .forgot-password a {
                font-size: 13px;
            }

            .alert {
                padding: 14px 16px;
                font-size: 13px;
            }

            #toast-container>.toast {
                padding: 16px 18px;
                border-radius: 14px;
            }

            #toast-container>.toast .toast-title {
                font-size: 14px;
            }

            #toast-container>.toast .toast-message {
                font-size: 13px;
            }
        }

        @media (max-width: 375px) {
            body {
                padding: 12px 10px;
            }

            .theme-toggle {
                top: 12px;
                right: 12px;
            }

            .theme-toggle-btn {
                width: 44px;
                height: 44px;
            }

            .theme-toggle-btn i {
                font-size: 18px;
            }

            .login-card {
                border-radius: 18px;
            }

            .login-header {
                padding: 28px 18px 22px;
            }

            .logo-image {
                max-width: 130px;
            }

            .login-title {
                font-size: 22px;
            }

            .login-subtitle {
                font-size: 12px;
            }

            .login-body {
                padding: 22px 18px 26px;
            }

            .form-label {
                font-size: 12px;
                margin-bottom: 8px;
            }

            .form-control {
                padding: 13px 15px;
                font-size: 14px;
                border-radius: 12px;
            }

            .password-toggle {
                right: 12px;
                width: 34px;
                height: 34px;
                font-size: 18px;
            }

            .form-group {
                margin-bottom: 16px;
            }

            .btn-login {
                padding: 13px 16px;
                font-size: 13px;
                border-radius: 12px;
            }

            .btn-login i {
                font-size: 16px;
            }

            .forgot-password {
                margin-top: 20px;
            }

            .forgot-password a {
                font-size: 12px;
                padding: 6px 10px;
            }

            .alert {
                padding: 12px 14px;
                font-size: 12px;
                margin-bottom: 18px;
            }

            .alert i {
                font-size: 18px;
            }
        }

        @media (max-width: 325px) {
            body {
                padding: 10px 8px;
            }

            .theme-toggle {
                top: 10px;
                right: 10px;
            }

            .theme-toggle-btn {
                width: 40px;
                height: 40px;
            }

            .theme-toggle-btn i {
                font-size: 16px;
            }

            .login-card {
                border-radius: 16px;
            }

            .login-header {
                padding: 24px 16px 20px;
            }

            .logo-image {
                max-width: 120px;
            }

            .login-title {
                font-size: 20px;
            }

            .login-subtitle {
                font-size: 11px;
            }

            .login-body {
                padding: 20px 16px 24px;
            }

            .form-label {
                font-size: 11px;
                margin-bottom: 7px;
            }

            .form-label i {
                font-size: 14px;
            }

            .form-control {
                padding: 12px 14px;
                font-size: 13px;
                border-radius: 10px;
            }

            .password-wrapper .form-control {
                padding-right: 44px;
            }

            .password-toggle {
                right: 10px;
                width: 32px;
                height: 32px;
                font-size: 17px;
            }

            .form-group {
                margin-bottom: 14px;
            }

            .btn-login {
                padding: 12px 14px;
                font-size: 12px;
                border-radius: 10px;
                letter-spacing: 0.8px;
            }

            .btn-login i {
                font-size: 15px;
            }

            .forgot-password {
                margin-top: 18px;
            }

            .forgot-password a {
                font-size: 11px;
                padding: 5px 8px;
                gap: 4px;
            }

            .alert {
                padding: 10px 12px;
                font-size: 11px;
                margin-bottom: 16px;
                gap: 8px;
            }

            .alert i {
                font-size: 16px;
            }

            .alert-close {
                width: 24px;
                height: 24px;
                font-size: 16px;
            }

            .invalid-feedback {
                font-size: 11px;
                margin-top: 5px;
            }

            #toast-container>.toast {
                padding: 14px 16px;
                border-radius: 12px;
            }

            #toast-container>.toast .toast-title {
                font-size: 13px;
            }

            #toast-container>.toast .toast-message {
                font-size: 12px;
                padding-left: 28px;
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

        .form-control.is-invalid, .was-validated .form-control:invalid{
            background-image:none;
        }
    </style>
</head>

<body>

    <div class="theme-toggle">
        <button type="button" class="theme-toggle-btn" id="themeToggle" aria-label="Alternar tema">
            <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
        </button>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-container">
                    <img src="{{ asset('img/logo-dark.png') }}" alt="Logo do Sistema" class="logo-image" id="logoImage">
                </div>
                <h1 class="login-title">Bem-vindo de volta!</h1>
                <p class="login-subtitle">Faça login para acessar o sistema</p>
            </div>

            <div class="login-body">
                @if ($errors->any() && !session('throttle_seconds'))
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div class="alert-content">{{ $errors->first() }}</div>
                        <button type="button" class="alert-close" aria-label="Fechar"
                            onclick="this.parentElement.remove()">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                @endif

                <div id="throttleAlert" class="alert alert-warning" role="alert" style="display: none;">
                    <i class="bi bi-hourglass-split" id="throttleIcon"></i>
                    <div class="alert-content" id="throttleMessage">
                        Muitas tentativas de login. Aguarde <strong id="throttleCountdown">0</strong>s para tentar novamente.
                    </div>
                </div>

                <form method="POST" action="{{ route('login') }}" id="loginForm" novalidate>
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

                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="bi bi-lock-fill"></i>
                            <span>Senha</span>
                        </label>
                        <div class="password-wrapper">
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                id="password" name="password" placeholder="Digite sua senha" required
                                autocomplete="current-password">
                            <button type="button" class="password-toggle" id="togglePassword" tabindex="-1"
                                aria-label="Mostrar senha">
                                <i class="bi bi-eye-fill" id="toggleIcon"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-circle-fill"></i>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>

                    <button type="submit" class="btn-login" id="submitBtn">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>Entrar</span>
                    </button>
                </form>

                <div class="forgot-password">
                    <a href="{{ route('password.request') }}">
                        <i class="bi bi-question-circle"></i>
                        <span>Esqueceu sua senha?</span>
                    </a>
                </div>

                <div style="text-align: center; margin-top: 18px; padding-top: 18px; border-top: 1px solid var(--input-border);">
                    <a href="{{ route('home') }}" style="display: inline-flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 500; color: var(--primary-color); text-decoration: none; transition: all 0.2s;">
                        <i class="bi bi-arrow-left"></i>
                        <span>Voltar ao site · Ver Planos & Preços</span>
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
                logoImage: document.getElementById('logoImage'),
                togglePassword: document.getElementById('togglePassword'),
                passwordInput: document.getElementById('password'),
                toggleIcon: document.getElementById('toggleIcon'),
                loginForm: document.getElementById('loginForm'),
                submitBtn: document.getElementById('submitBtn'),
                emailInput: document.getElementById('email'),
                loadingOverlay: document.getElementById('loadingOverlay'),
                throttleAlert: document.getElementById('throttleAlert'),
                throttleIcon: document.getElementById('throttleIcon'),
                throttleMessage: document.getElementById('throttleMessage'),
                throttleCountdown: document.getElementById('throttleCountdown')
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
                updateLogo(savedTheme);

                elements.themeToggle.addEventListener('click', function() {
                    const currentTheme = document.documentElement.getAttribute('data-theme');
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

                    document.documentElement.setAttribute('data-theme', newTheme);
                    localStorage.setItem('theme', newTheme);
                    updateThemeIcon(newTheme);
                    updateLogo(newTheme);

                    toastr.info('Tema ' + (newTheme === 'dark' ? 'escuro' : 'claro') + ' ativado',
                        'Tema alterado');
                });
            }

            function updateThemeIcon(theme) {
                elements.themeIcon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
            }

            function updateLogo(theme) {
                elements.logoImage.src = theme === 'dark' ?
                    '{{ asset('img/logo-dark.png') }}' :
                    '{{ asset('img/logo-dark.png') }}';
            }

            function initPasswordToggle() {
                elements.togglePassword.addEventListener('click', function() {
                    const isPassword = elements.passwordInput.type === 'password';
                    elements.passwordInput.type = isPassword ? 'text' : 'password';
                    elements.toggleIcon.className = isPassword ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';
                });
            }

            function initFormValidation() {
                elements.loginForm.addEventListener('submit', function(event) {
                    if (elements.submitBtn.disabled) {
                        event.preventDefault();
                        return false;
                    }

                    let isValid = true;

                    const email = elements.emailInput.value.trim();
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                    elements.emailInput.classList.remove('is-invalid');
                    elements.passwordInput.classList.remove('is-invalid');

                    if (!email || !emailRegex.test(email)) {
                        isValid = false;
                        elements.emailInput.classList.add('is-invalid');
                    }

                    if (!elements.passwordInput.value) {
                        isValid = false;
                        elements.passwordInput.classList.add('is-invalid');
                    }

                    if (!isValid) {
                        event.preventDefault();
                        toastr.error('Por favor, preencha todos os campos corretamente.', 'Erro de validação');
                        return false;
                    }

                    elements.submitBtn.disabled = true;
                    elements.submitBtn.innerHTML =
                        '<span class="spinner-border spinner-border-sm"></span><span>Entrando...</span>';
                    elements.loadingOverlay.classList.add('active');
                });
            }

            function initSessionMessages() {
                @if (session('success'))
                    toastr.success('{{ session('success') }}', 'Sucesso!');
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
            }

            function initThrottleCountdown() {
                @if (session('throttle_seconds'))
                    var totalSeconds = {{ (int) session('throttle_seconds') }};
                    var remaining = totalSeconds;

                    elements.throttleAlert.style.display = 'flex';
                    elements.submitBtn.disabled = true;
                    elements.emailInput.readOnly = true;
                    elements.passwordInput.readOnly = true;

                    function updateCountdown() {
                        elements.throttleCountdown.textContent = remaining;
                        elements.submitBtn.innerHTML =
                            'Tente Novamente em <span>' + remaining + 's</span>';

                        if (remaining <= 0) {
                            clearInterval(countdownTimer);

                            elements.throttleAlert.classList.remove('alert-warning');
                            elements.throttleAlert.classList.add('alert-success');
                            elements.throttleIcon.className = 'bi bi-check-circle-fill';
                            elements.throttleMessage.innerHTML = 'Você já pode tentar novamente!';

                            elements.submitBtn.disabled = false;
                            elements.submitBtn.innerHTML =
                                '<i class="bi bi-box-arrow-in-right"></i><span>Entrar</span>';
                            elements.emailInput.readOnly = false;
                            elements.passwordInput.readOnly = false;
                            elements.emailInput.focus();

                            toastr.success('Pode tentar novamente.', 'Pronto!');

                            setTimeout(function() {
                                elements.throttleAlert.style.display = 'none';
                            }, 4000);

                            return;
                        }

                        remaining--;
                    }

                    updateCountdown();
                    var countdownTimer = setInterval(updateCountdown, 1000);
                @endif
            }

            function init() {
                initTheme();
                initPasswordToggle();
                initFormValidation();
                initSessionMessages();
                initThrottleCountdown();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }

        })();

        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                var spinnerEmBotao = document.querySelector('button .spinner-border');
                var overlayAtivo = document.querySelector('.loading-overlay.active');
                if (spinnerEmBotao || overlayAtivo) {
                    window.location.reload();
                }
            }
        });
    </script>

</body>

</html>