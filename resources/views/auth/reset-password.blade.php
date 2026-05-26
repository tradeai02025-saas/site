<!DOCTYPE html>
<html lang="pt-BR">

{{-- resources/views/auth/reset-password.blade.php --}}

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css" rel="stylesheet">

    <link rel="icon" href="{{ asset('img/favicons/favicon.svg') }}" type="image/svg+xml">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('img/favicons/favicon-16.png') }}" media="(prefers-color-scheme: light)" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('img/favicons/favicon-16.png') }}" media="(prefers-color-scheme: light)">
    <link rel="icon" sizes="32x32" href="{{ asset('img/favicons/favicon-32.svg') }}" media="(prefers-color-scheme: light)">
    <link rel="icon" sizes="48x48" href="{{ asset('img/favicons/favicon-48.png') }}" media="(prefers-color-scheme: light)">
    <link rel="icon" sizes="192x192" href="{{ asset('img/favicons/favicon-192.png') }}" media="(prefers-color-scheme: light)">
    <link rel="icon" sizes="512x512" href="{{ asset('img/favicons/favicon-512.png') }}" media="(prefers-color-scheme: light)">

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('img/favicons/favicon-16-dark.png') }}" media="(prefers-color-scheme: dark)" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('img/favicons/favicon-16-dark.png') }}" media="(prefers-color-scheme: dark)">
    <link rel="icon" sizes="32x32" href="{{ asset('img/favicons/favicon-32-dark.png') }}" media="(prefers-color-scheme: dark)">
    <link rel="icon" sizes="48x48" href="{{ asset('img/favicons/favicon-48-dark.png') }}" media="(prefers-color-scheme: dark)">
    <link rel="icon" sizes="192x192" href="{{ asset('img/favicons/favicon-192-dark.png') }}" media="(prefers-color-scheme: dark)">
    <link rel="icon" sizes="512x512" href="{{ asset('img/favicons/favicon-512-dark.png') }}" media="(prefers-color-scheme: dark)">

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
            transition: all 0.2s ease;
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
            transition: color 0.2s ease;
        }

        .reset-container {
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

        .reset-card {
            background: var(--card-bg);
            border-radius: 28px;
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .reset-header {
            background-color: #0088b8;
            padding: 44px 32px 36px;
            text-align: center;
            position: relative;
        }

        .reset-header::after {
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

        .reset-title {
            color: #ffffff;
            font-size: 30px;
            font-weight: 700;
            margin: 0 0 8px;
            text-shadow: 0 2px 12px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 2;
            letter-spacing: -0.5px;
        }

        .reset-subtitle {
            color: rgba(255, 255, 255, 0.92);
            font-size: 15px;
            margin: 0;
            font-weight: 400;
            position: relative;
            z-index: 2;
        }

        .reset-body {
            padding: 36px 32px 40px;
        }

        .info-box {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(59, 130, 246, 0.04) 100%);
            border-left: 4px solid var(--info-color);
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 28px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .info-box i {
            color: var(--info-color);
            font-size: 22px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .info-box-content {
            flex: 1;
        }

        .info-box-title {
            font-weight: 600;
            color: var(--text-color);
            font-size: 14px;
            margin-bottom: 6px;
        }

        .info-box-text {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.5;
            margin: 0;
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

        .form-label .required {
            color: var(--secondary-color);
            margin-left: 2px;
        }

        .form-control {
            background: var(--input-bg);
            border: 2px solid var(--input-border);
            color: var(--text-color);
            padding: 15px 18px;
            border-radius: 14px;
            font-size: 15px;
            transition: all 0.2s ease;
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

        .form-control:read-only {
            background-color: var(--input-border);
            cursor: not-allowed;
            opacity: 0.7;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper .form-control {
            padding-right: 50px;
        }

        #password {
            padding-right: 100px;
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
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            width: 36px;
            height: 36px;
        }

        .btn-generate-password {
            position: absolute;
            right: 60px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 8px;
            font-size: 20px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            width: 36px;
            height: 36px;
        }

        .btn-generate-password:not(:disabled):hover {
            color: var(--primary-color);
            background: rgba(56, 189, 248, 0.1);
            transform: translateY(-50%) scale(1.05);
        }

        .btn-generate-password:not(:disabled):active {
            transform: translateY(-50%) scale(0.95);
        }

        .btn-generate-password:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-generate-password.generating {
            pointer-events: none;
        }

        .btn-generate-password i {
            transition: transform 0.2s ease;
        }

        .btn-generate-password.generating i {
            animation: quickSpin 0.6s ease-in-out;
        }

        @keyframes quickSpin {
            0% {
                transform: rotate(0deg) scale(1);
            }
            50% {
                transform: rotate(180deg) scale(1.2);
            }
            100% {
                transform: rotate(360deg) scale(1);
            }
        }

        .password-toggle:hover {
            color: var(--primary-color);
            background: rgba(56, 189, 248, 0.1);
            transform: translateY(-50%) scale(1.05);
        }

        .password-toggle:active {
            transform: translateY(-50%) scale(0.95);
        }

        .password-toggle:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        .password-toggle i {
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .password-toggle.animating i {
            animation: quickFlip 0.3s ease;
        }

        @keyframes quickFlip {
            0% {
                transform: scale(1) rotateY(0deg);
            }
            50% {
                transform: scale(0.5) rotateY(90deg);
            }
            100% {
                transform: scale(1) rotateY(0deg);
            }
        }

        .password-strength {
            margin-top: 10px;
            display: none;
        }

        .password-strength.show {
            display: block;
        }

        .strength-bar {
            height: 6px;
            background: var(--input-border);
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .strength-bar-fill {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 3px;
        }

        .strength-text {
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .strength-weak .strength-bar-fill {
            width: 33%;
            background: var(--secondary-color);
        }

        .strength-weak .strength-text {
            color: var(--secondary-color);
        }

        .strength-medium .strength-bar-fill {
            width: 66%;
            background: var(--warning-color);
        }

        .strength-medium .strength-text {
            color: var(--warning-color);
        }

        .strength-strong .strength-bar-fill {
            width: 100%;
            background: var(--success-color);
        }

        .strength-strong .strength-text {
            color: var(--success-color);
        }

        .password-requirements {
            margin-top: 12px;
            padding: 14px;
            background: var(--input-bg);
            border-radius: 10px;
            border: 1px solid var(--input-border);
        }

        .password-requirements-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 10px;
        }

        .requirement {
            font-size: 12px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
            padding: 0.5rem 0.75rem;
            background: var(--card-bg);
            border-radius: 8px;
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }

        .requirement:last-child {
            margin-bottom: 0;
        }

        .requirement i {
            font-size: 14px;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }

        .requirement.met {
            color: var(--success-color);
            border-color: var(--success-color);
            background: rgba(16, 185, 129, 0.05);
        }

        .requirement.met i {
            color: var(--success-color);
        }

        .btn-reset {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            border: none;
            color: #ffffff;
            padding: 16px 24px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 14px;
            width: 100%;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            box-shadow: 0 8px 24px rgba(14, 165, 233, 0.3);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-reset:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(14, 165, 233, 0.4);
        }

        .btn-reset:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-reset:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-reset i {
            font-size: 18px;
        }

        .back-login {
            text-align: center;
            margin-top: 24px;
        }

        .back-login a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 8px;
        }

        .back-login a:hover {
            color: var(--secondary-color);
            background: rgba(56, 189, 248, 0.05);
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
            .reset-container {
                max-width: 100%;
            }

            .reset-header {
                padding: 36px 24px 28px;
            }

            .reset-title {
                font-size: 26px;
            }

            .reset-body {
                padding: 28px 24px 32px;
            }
        }

        @media (max-width: 576px) {
            #password {
                padding-right: 50px;
            }

            .btn-generate-password {
                position: relative;
                right: auto;
                top: auto;
                transform: none;
                margin-top: 0.5rem;
                width: 100%;
                justify-content: center;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                padding: 12px;
                border: 2px solid var(--input-border);
                background: var(--input-bg);
                font-size: 14px;
                font-weight: 600;
                border-radius: 14px;
            }

            .btn-generate-password:not(:disabled):hover {
                transform: none;
            }

            .btn-generate-password::after {
                content: 'Gerar Senha Forte';
            }
        }

        @media (max-width: 425px) {
            .reset-card {
                border-radius: 20px;
            }

            .reset-header {
                padding: 32px 20px 24px;
            }

            .reset-title {
                font-size: 24px;
            }

            .reset-body {
                padding: 24px 20px 28px;
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

        .form-control.is-invalid, .was-validated .form-control:invalid {
            background-image: none;
        }
    </style>
</head>

<body>
    <div class="theme-toggle">
        <button type="button" class="theme-toggle-btn" id="themeToggle" aria-label="Alternar tema">
            <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
        </button>
    </div>

    <div class="reset-container">
        <div class="reset-card">
            <div class="reset-header">
                <div class="logo-container">
                    <img src="{{ asset('img/logo-dark.png') }}" alt="Logo do Sistema" class="logo-image" id="logoImage">
                </div>
                <h1 class="reset-title">Redefinir Senha</h1>
                <p class="reset-subtitle">Crie uma nova senha segura para sua conta</p>
            </div>

            <div class="reset-body">
                <div class="info-box">
                    <i class="bi bi-shield-check"></i>
                    <div class="info-box-content">
                        <div class="info-box-title">Segurança em primeiro lugar</div>
                        <p class="info-box-text">Escolha uma senha forte que contenha letras, números e caracteres especiais.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('password.update') }}" id="resetForm" novalidate>
                    @csrf

                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope-fill"></i>
                            <span>E-mail</span>
                        </label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                            name="email" value="{{ $email ?? old('email') }}" readonly required>
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
                            <span>Nova Senha</span>
                            <span class="required">*</span>
                        </label>
                        <div class="password-wrapper">
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                id="password" name="password" placeholder="Digite sua nova senha" required
                                autocomplete="new-password">
                            <button type="button" class="btn-generate-password" id="generatePassword" tabindex="-1"
                                title="Gerar senha forte" aria-label="Gerar senha">
                                <i class="bi bi-stars"></i>
                            </button>
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

                        <div class="password-strength" id="passwordStrength">
                            <div class="strength-bar">
                                <div class="strength-bar-fill" id="strengthBarFill"></div>
                            </div>
                            <div class="strength-text" id="strengthText">
                                <i class="bi bi-shield-fill-exclamation"></i>
                                <span>Digite uma senha</span>
                            </div>
                        </div>

                        <div class="password-requirements">
                            <div class="password-requirements-title">Requisitos da senha:</div>
                            <div class="requirement" id="req-length">
                                <i class="bi bi-circle"></i>
                                <span>Mínimo de 8 caracteres</span>
                            </div>
                            <div class="requirement" id="req-lowercase">
                                <i class="bi bi-circle"></i>
                                <span>Pelo menos uma letra minúscula (a-z)</span>
                            </div>
                            <div class="requirement" id="req-uppercase">
                                <i class="bi bi-circle"></i>
                                <span>Pelo menos uma letra maiúscula (A-Z)</span>
                            </div>
                            <div class="requirement" id="req-number">
                                <i class="bi bi-circle"></i>
                                <span>Pelo menos um número (0-9)</span>
                            </div>
                            <div class="requirement" id="req-special">
                                <i class="bi bi-circle"></i>
                                <span>Pelo menos um caractere especial (!@#$%^&*)</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">
                            <i class="bi bi-lock-fill"></i>
                            <span>Confirmar Nova Senha</span>
                            <span class="required">*</span>
                        </label>
                        <div class="password-wrapper">
                            <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror"
                                id="password_confirmation" name="password_confirmation" placeholder="Confirme sua nova senha"
                                required autocomplete="new-password">
                            <button type="button" class="password-toggle" id="togglePasswordConfirmation" tabindex="-1"
                                aria-label="Mostrar confirmação">
                                <i class="bi bi-eye-fill" id="toggleIconConfirmation"></i>
                            </button>
                        </div>
                        @error('password_confirmation')
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-circle-fill"></i>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                        <div class="invalid-feedback" id="confirmationMismatch" style="display: none;">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <span>As senhas não coincidem</span>
                        </div>
                    </div>

                    <button type="submit" class="btn-reset" id="submitBtn">
                        <i class="bi bi-shield-check"></i>
                        <span>Redefinir Senha</span>
                    </button>
                </form>

                <div class="back-login">
                    <a href="{{ route('login') }}">
                        <i class="bi bi-arrow-left"></i>
                        <span>Voltar ao login</span>
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
                togglePasswordConfirmation: document.getElementById('togglePasswordConfirmation'),
                passwordConfirmationInput: document.getElementById('password_confirmation'),
                toggleIconConfirmation: document.getElementById('toggleIconConfirmation'),
                generatePassword: document.getElementById('generatePassword'),
                resetForm: document.getElementById('resetForm'),
                submitBtn: document.getElementById('submitBtn'),
                passwordStrength: document.getElementById('passwordStrength'),
                strengthBarFill: document.getElementById('strengthBarFill'),
                strengthText: document.getElementById('strengthText'),
                confirmationMismatch: document.getElementById('confirmationMismatch')
            };

            let isGenerating = false;

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
                function hidePasswordField($input) {
                    if ($input.attr('type') === 'text') {
                        $input.attr('type', 'password');
                        
                        const inputId = $input.attr('id');
                        let $button;
                        
                        if (inputId === 'password') {
                            $button = $(elements.togglePassword);
                        } else {
                            $button = $(elements.togglePasswordConfirmation);
                        }
                        
                        const $icon = $button.find('i');
                        $icon.removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
                    }
                }

                $(elements.passwordInput).on('blur', function() {
                    hidePasswordField($(this));
                });

                $(elements.passwordConfirmationInput).on('blur', function() {
                    hidePasswordField($(this));
                });

                $(elements.passwordInput).on('focus', function() {
                    hidePasswordField($(elements.passwordConfirmationInput));
                });

                $(elements.passwordConfirmationInput).on('focus', function() {
                    hidePasswordField($(elements.passwordInput));
                });

                elements.togglePassword.addEventListener('click', function() {
                    const $button = $(this);
                    const $input = $(elements.passwordInput);
                    
                    if ($button.prop('disabled')) return;
                    
                    $button.prop('disabled', true);
                    
                    const newType = $input.attr('type') === 'password' ? 'text' : 'password';
                    $input.attr('type', newType);
                    
                    const $icon = $button.find('i');
                    $button.addClass('animating');
                    
                    if (newType === 'text') {
                        $icon.removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
                    } else {
                        $icon.removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
                    }
                    
                    setTimeout(() => {
                        $button.removeClass('animating').prop('disabled', false);
                    }, 300);
                });

                elements.togglePasswordConfirmation.addEventListener('click', function() {
                    const $button = $(this);
                    const $input = $(elements.passwordConfirmationInput);
                    
                    if ($button.prop('disabled')) return;
                    
                    $button.prop('disabled', true);
                    
                    const newType = $input.attr('type') === 'password' ? 'text' : 'password';
                    $input.attr('type', newType);
                    
                    const $icon = $button.find('i');
                    $button.addClass('animating');
                    
                    if (newType === 'text') {
                        $icon.removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
                    } else {
                        $icon.removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
                    }
                    
                    setTimeout(() => {
                        $button.removeClass('animating').prop('disabled', false);
                    }, 300);
                });
            }

            function generateSecurePassword(length = 16) {
                const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                const lowercase = 'abcdefghijklmnopqrstuvwxyz';
                const numbers = '0123456789';
                const special = '!@#$%^&*';
                const all = uppercase + lowercase + numbers + special;
                
                let password = '';
                password += uppercase[Math.floor(Math.random() * uppercase.length)];
                password += lowercase[Math.floor(Math.random() * lowercase.length)];
                password += numbers[Math.floor(Math.random() * numbers.length)];
                password += special[Math.floor(Math.random() * special.length)];
                
                for (let i = password.length; i < length; i++) {
                    password += all[Math.floor(Math.random() * all.length)];
                }
                
                return password.split('').sort(() => Math.random() - 0.5).join('');
            }

            function initPasswordGenerator() {
                elements.generatePassword.addEventListener('click', function() {
                    if (isGenerating) return;

                    isGenerating = true;
                    const $button = $(this);
                    
                    $button.prop('disabled', true).addClass('generating');
                    
                    const generatedPassword = generateSecurePassword();
                    
                    $(elements.passwordInput).val(generatedPassword).attr('type', 'text');
                    $(elements.passwordConfirmationInput).val(generatedPassword).attr('type', 'password');
                    
                    $(elements.togglePassword).find('i').removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
                    $(elements.togglePasswordConfirmation).find('i').removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
                    
                    checkPasswordStrength(generatedPassword);
                    
                    elements.passwordConfirmationInput.dispatchEvent(new Event('input'));
                    
                    toastr.success('Senha forte gerada com sucesso!', 'Gerador de Senha', { timeOut: 4000 });

                    setTimeout(() => {
                        $button.removeClass('generating').prop('disabled', false);
                        isGenerating = false;
                    }, 600);
                });
            }

            function resetPasswordRequirements() {
                const requirements = ['length', 'lowercase', 'uppercase', 'number', 'special'];
                
                requirements.forEach(req => {
                    const element = document.getElementById(`req-${req}`);
                    element.classList.remove('met');
                    element.querySelector('i').className = 'bi bi-circle';
                });
            }

            function checkPasswordStrength(password) {
                let strength = 0;
                const requirements = {
                    length: password.length >= 8,
                    lowercase: /[a-z]/.test(password),
                    uppercase: /[A-Z]/.test(password),
                    number: /[0-9]/.test(password),
                    special: /[!@#$%^&*]/.test(password)
                };

                document.getElementById('req-length').classList.toggle('met', requirements.length);
                document.getElementById('req-lowercase').classList.toggle('met', requirements.lowercase);
                document.getElementById('req-uppercase').classList.toggle('met', requirements.uppercase);
                document.getElementById('req-number').classList.toggle('met', requirements.number);
                document.getElementById('req-special').classList.toggle('met', requirements.special);

                document.getElementById('req-length').querySelector('i').className = requirements.length ?
                    'bi bi-check-circle-fill' : 'bi bi-circle';
                document.getElementById('req-lowercase').querySelector('i').className = requirements.lowercase ?
                    'bi bi-check-circle-fill' : 'bi bi-circle';
                document.getElementById('req-uppercase').querySelector('i').className = requirements.uppercase ?
                    'bi bi-check-circle-fill' : 'bi bi-circle';
                document.getElementById('req-number').querySelector('i').className = requirements.number ?
                    'bi bi-check-circle-fill' : 'bi bi-circle';
                document.getElementById('req-special').querySelector('i').className = requirements.special ?
                    'bi bi-check-circle-fill' : 'bi bi-circle';

                if (requirements.length) strength++;
                if (requirements.lowercase) strength++;
                if (requirements.uppercase) strength++;
                if (requirements.number) strength++;
                if (requirements.special) strength++;

                elements.passwordStrength.className = 'password-strength show';

                if (strength <= 2) {
                    elements.passwordStrength.classList.add('strength-weak');
                    elements.passwordStrength.classList.remove('strength-medium', 'strength-strong');
                    elements.strengthText.innerHTML =
                        '<i class="bi bi-shield-fill-exclamation"></i><span>Senha fraca</span>';
                } else if (strength <= 4) {
                    elements.passwordStrength.classList.add('strength-medium');
                    elements.passwordStrength.classList.remove('strength-weak', 'strength-strong');
                    elements.strengthText.innerHTML =
                        '<i class="bi bi-shield-fill-check"></i><span>Senha média</span>';
                } else {
                    elements.passwordStrength.classList.add('strength-strong');
                    elements.passwordStrength.classList.remove('strength-weak', 'strength-medium');
                    elements.strengthText.innerHTML =
                        '<i class="bi bi-shield-fill-check"></i><span>Senha forte</span>';
                }

                return Object.values(requirements).every(req => req);
            }

            function initPasswordStrength() {
                elements.passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    if (password.length > 0) {
                        checkPasswordStrength(password);
                    } else {
                        elements.passwordStrength.classList.remove('show');
                        resetPasswordRequirements();
                    }
                });
            }

            function initPasswordConfirmation() {
                elements.passwordConfirmationInput.addEventListener('paste', function(e) {
                    e.preventDefault();
                    toastr.warning('Por favor, digite a senha manualmente para confirmação', 'Colar não permitido');
                });

                elements.passwordConfirmationInput.addEventListener('input', function() {
                    const password = elements.passwordInput.value;
                    const confirmation = this.value;

                    if (confirmation.length > 0) {
                        if (password !== confirmation) {
                            this.classList.add('is-invalid');
                            elements.confirmationMismatch.style.display = 'flex';
                        } else {
                            this.classList.remove('is-invalid');
                            elements.confirmationMismatch.style.display = 'none';
                        }
                    } else {
                        this.classList.remove('is-invalid');
                        elements.confirmationMismatch.style.display = 'none';
                    }
                });
            }

            function initFormValidation() {
                elements.resetForm.addEventListener('submit', function(event) {
                    let isValid = true;

                    elements.passwordInput.classList.remove('is-invalid');
                    elements.passwordConfirmationInput.classList.remove('is-invalid');

                    const password = elements.passwordInput.value;
                    const confirmation = elements.passwordConfirmationInput.value;

                    if (password.length < 8) {
                        isValid = false;
                        elements.passwordInput.classList.add('is-invalid');
                        toastr.error('A senha deve ter pelo menos 8 caracteres', 'Senha inválida');
                    }

                    if (!/[a-z]/.test(password) || !/[A-Z]/.test(password) || !/[0-9]/.test(password) || !/[!@#$%^&*]/.test(password)) {
                        isValid = false;
                        elements.passwordInput.classList.add('is-invalid');
                        toastr.error('A senha deve atender a todos os requisitos de segurança', 'Senha fraca');
                    }

                    if (password !== confirmation) {
                        isValid = false;
                        elements.passwordConfirmationInput.classList.add('is-invalid');
                        elements.confirmationMismatch.style.display = 'flex';
                        toastr.error('As senhas não coincidem', 'Erro de confirmação');
                    }

                    if (!isValid) {
                        event.preventDefault();
                        return false;
                    }

                    elements.submitBtn.disabled = true;
                    elements.submitBtn.innerHTML =
                        '<span class="spinner-border spinner-border-sm"></span><span>Redefinindo...</span>';
                });
            }

            function initSessionMessages() {
                @if (session('success'))
                    toastr.success('{{ session('success') }}', 'Sucesso!');
                @endif

                @if (session('error'))
                    toastr.error('{{ session('error') }}', 'Erro!');
                @endif

                @if (session('status'))
                    toastr.success('{{ session('status') }}', 'Sucesso!');
                @endif

                @if ($errors->any())
                    toastr.error('{{ $errors->first() }}', 'Erro de validação');
                @endif
            }

            function init() {
                initTheme();
                initPasswordToggle();
                initPasswordGenerator();
                initPasswordStrength();
                initPasswordConfirmation();
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