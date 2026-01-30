<!DOCTYPE html>
<html lang="en" data-theme="{{ auth()->check() && auth()->user()->profile ? (auth()->user()->profile->theme ?? 'dark') : 'dark' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if(isset($server))
    <meta name="server-id" content="{{ $server->id }}">
    @endif
    <title>@yield('title', 'Glyph')</title>

    {{-- Satoshi Font - Refined geometric sans-serif --}}
    <link href="https://api.fontshare.com/v2/css?f[]=satoshi@400,500,600,700&display=swap" rel="stylesheet">

    {{-- Orbitron Font - Gaming/Tech display font for logo --}}
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&display=swap" rel="stylesheet">

    {{-- FOUC Prevention: Set theme before CSS loads --}}
    <script>
        (function() {
            // Get theme from server-rendered attribute or localStorage
            var theme = document.documentElement.getAttribute('data-theme');
            if (!theme || theme === 'dark') {
                // Check localStorage for guest preference
                var storedTheme = localStorage.getItem('glyph-theme');
                if (storedTheme && (storedTheme === 'dark' || storedTheme === 'light')) {
                    theme = storedTheme;
                    document.documentElement.setAttribute('data-theme', theme);
                }
            }
        })();
    </script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Satoshi', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--color-bg-primary, #0e0e10);
            color: var(--color-text-primary, #efeff1);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Refined background - subtle dot grid pattern with gradient overlay */
        .auth-container::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 0;
            background-image: radial-gradient(circle at 1px 1px, rgba(102, 126, 234, 0.04) 1px, transparent 0);
            background-size: 32px 32px;
        }

        .auth-container::after {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 0;
            background:
                radial-gradient(ellipse 80% 50% at 20% 40%, rgba(102, 126, 234, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse 60% 60% at 80% 20%, rgba(118, 75, 162, 0.06) 0%, transparent 50%),
                radial-gradient(ellipse 50% 70% at 50% 80%, rgba(102, 126, 234, 0.05) 0%, transparent 50%);
            animation: subtleFloat 30s ease-in-out infinite;
        }

        @keyframes subtleFloat {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.85; }
        }

        .auth-box {
            background-color: var(--auth-box-bg, rgba(24, 24, 27, 0.95));
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 48px;
            width: 100%;
            max-width: 440px;
            box-shadow:
                0 0 0 1px rgba(102, 126, 234, 0.08),
                0 8px 32px rgba(0, 0, 0, 0.5),
                0 32px 64px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
            border: 1px solid rgba(63, 63, 70, 0.4);
            animation: authBoxEntrance 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .auth-box::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 20px;
            padding: 1px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), transparent 50%);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
        }

        @keyframes authBoxEntrance {
            from { opacity: 0; transform: translateY(16px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Form section dividers */
        .form-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--color-border-primary, #3f3f46), transparent);
            margin: 28px 0;
        }

        .form-section-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--color-text-muted, #71717a);
            margin-bottom: 16px;
        }

        /* Auth Form Input Enhancements */
        .input-with-icon {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-with-icon input[type="text"],
        .input-with-icon input[type="email"],
        .input-with-icon input[type="password"] {
            padding-left: 44px !important;
            padding-right: 44px;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            width: 18px;
            height: 18px;
            color: var(--color-text-muted, #71717a);
            pointer-events: none;
            transition: color 0.2s ease, transform 0.2s ease;
        }

        .input-with-icon:focus-within .input-icon {
            color: var(--accent-primary, #667eea);
            transform: scale(1.05);
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            padding: 6px;
            cursor: pointer;
            color: var(--color-text-muted, #71717a);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease, transform 0.2s ease;
            border-radius: 6px;
        }

        .password-toggle:hover {
            color: var(--color-text-secondary, #b3b3b5);
            background-color: rgba(255, 255, 255, 0.05);
        }

        .password-toggle:active {
            transform: scale(0.95);
        }

        .password-toggle svg {
            width: 18px;
            height: 18px;
        }

        /* Full Width Button */
        .btn-full {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Loading State */
        .btn-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .spinner {
            width: 18px;
            height: 18px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        /* OTP Input Styles */
        .otp-input-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0 28px;
        }

        .otp-input {
            width: 48px;
            height: 60px;
            text-align: center;
            font-size: 26px;
            font-weight: 600;
            font-family: 'Satoshi', monospace;
            background-color: rgba(14, 14, 16, 0.8);
            border: 1px solid rgba(63, 63, 70, 0.5);
            border-radius: 14px;
            color: var(--color-text-primary, #efeff1);
            caret-color: var(--accent-primary, #667eea);
            transition: all 0.2s ease;
            padding: 0;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .otp-input:focus {
            outline: none;
            border-color: var(--accent-primary, #667eea);
            background-color: rgba(24, 24, 27, 0.9);
            box-shadow:
                0 0 0 3px rgba(102, 126, 234, 0.12),
                inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .otp-input.has-value {
            border-color: var(--accent-primary, #667eea);
            background-color: rgba(102, 126, 234, 0.08);
            transform: scale(1.02);
        }

        .otp-input::placeholder {
            color: var(--color-text-faint, #52525b);
        }

        .otp-input::-webkit-outer-spin-button,
        .otp-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Subtle waiting animation for empty OTP inputs */
        .otp-input:not(.has-value):not(:focus) {
            animation: otpWaiting 2s ease-in-out infinite;
        }

        @keyframes otpWaiting {
            0%, 100% { border-color: rgba(63, 63, 70, 0.5); }
            50% { border-color: rgba(63, 63, 70, 0.3); }
        }

        @media (max-width: 400px) {
            .otp-input-container {
                gap: 6px;
            }
            .otp-input {
                width: 42px;
                height: 52px;
                font-size: 22px;
                border-radius: 12px;
            }
        }

        /* Reduced motion preference */
        @media (prefers-reduced-motion: reduce) {
            .auth-container::before,
            .auth-container::after {
                animation: none;
            }
            .auth-box {
                animation: none;
            }
        }

        /* Light theme auth overrides */
        [data-theme="light"] .auth-container {
            background-color: #f4f4f5;
        }

        [data-theme="light"] .auth-container::before {
            background-image: radial-gradient(circle at 1px 1px, rgba(102, 126, 234, 0.06) 1px, transparent 0);
        }

        [data-theme="light"] .auth-container::after {
            background:
                radial-gradient(ellipse 80% 50% at 20% 40%, rgba(102, 126, 234, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse 60% 60% at 80% 20%, rgba(118, 75, 162, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse 50% 70% at 50% 80%, rgba(102, 126, 234, 0.06) 0%, transparent 50%);
        }

        [data-theme="light"] .auth-box {
            background-color: rgba(255, 255, 255, 0.95);
            border-color: rgba(0, 0, 0, 0.08);
            box-shadow:
                0 0 0 1px rgba(0, 0, 0, 0.05),
                0 8px 32px rgba(0, 0, 0, 0.08),
                0 32px 64px rgba(0, 0, 0, 0.05);
        }

        [data-theme="light"] .auth-box::before {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), transparent 50%);
        }

        [data-theme="light"] input[type="text"],
        [data-theme="light"] input[type="email"],
        [data-theme="light"] input[type="password"] {
            background-color: rgba(244, 244, 245, 0.8);
            border-color: rgba(0, 0, 0, 0.1);
            color: #18181b;
        }

        [data-theme="light"] input::placeholder {
            color: #a1a1aa;
        }

        [data-theme="light"] input:focus {
            background-color: #ffffff;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
        }

        [data-theme="light"] .otp-input {
            background-color: rgba(244, 244, 245, 0.8);
            border-color: rgba(0, 0, 0, 0.1);
            color: #18181b;
        }

        [data-theme="light"] .otp-input:focus {
            background-color: #ffffff;
            border-color: #667eea;
        }

        [data-theme="light"] .otp-input.has-value {
            background-color: rgba(102, 126, 234, 0.06);
        }

        [data-theme="light"] .form-divider {
            background: linear-gradient(90deg, transparent, rgba(0, 0, 0, 0.1), transparent);
        }

        [data-theme="light"] label {
            color: #52525b;
        }

        [data-theme="light"] .logo-subtitle,
        [data-theme="light"] .auth-footer {
            color: #71717a;
        }

        [data-theme="light"] .form-hint {
            color: #a1a1aa;
        }

        [data-theme="light"] .alert-error {
            background-color: rgba(239, 68, 68, 0.08);
            border-color: rgba(239, 68, 68, 0.2);
        }

        [data-theme="light"] .alert-success {
            background-color: rgba(16, 185, 129, 0.08);
            border-color: rgba(16, 185, 129, 0.2);
        }

        /* Mobile responsive styles for auth pages */
        @media (max-width: 480px) {
            .auth-box {
                padding: 32px 24px;
                border-radius: 16px;
                max-width: 100%;
            }

            .logo h1 {
                font-size: 2.5rem;
            }

            .logo-subtitle {
                font-size: 14px;
            }

            .form-group {
                margin-bottom: 18px;
            }

            label {
                font-size: 12px;
            }

            input[type="text"],
            input[type="email"],
            input[type="password"] {
                padding: 12px 14px;
                font-size: 16px; /* Prevents zoom on iOS */
            }

            .input-with-icon input[type="text"],
            .input-with-icon input[type="email"],
            .input-with-icon input[type="password"] {
                padding-left: 40px !important;
                padding-right: 40px;
            }

            .input-icon {
                left: 12px;
                width: 16px;
                height: 16px;
            }

            .password-toggle {
                right: 10px;
            }

            .password-toggle svg {
                width: 16px;
                height: 16px;
            }

            .btn {
                padding: 12px 20px;
                font-size: 14px;
            }

            .form-divider {
                margin: 20px 0;
            }

            .auth-footer {
                font-size: 13px;
            }
        }

        .logo {
            text-align: center;
            margin-bottom: 36px;
        }

        .logo h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 0.15em;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo-subtitle {
            color: var(--color-text-secondary, #a1a1aa);
            margin-top: 10px;
            font-size: 15px;
            font-weight: 400;
            letter-spacing: -0.01em;
        }

        .form-group {
            margin-bottom: 22px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 500;
            color: var(--color-text-secondary, #a1a1aa);
            letter-spacing: 0.01em;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        textarea,
        select {
            width: 100%;
            padding: 13px 16px;
            background-color: var(--color-input-bg);
            border: 1px solid var(--color-input-border);
            border-radius: 10px;
            color: var(--color-input-text);
            font-size: 15px;
            transition: all 0.2s ease;
            font-family: inherit;
            box-shadow: var(--input-shadow, inset 0 1px 2px rgba(0, 0, 0, 0.1));
        }

        /* Select dropdown styling for both themes */
        select option {
            background-color: var(--color-surface);
            color: var(--color-text-primary);
            padding: 8px 12px;
        }

        input::placeholder {
            color: var(--color-input-placeholder);
            font-weight: 400;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--color-input-border-focus);
            background-color: var(--color-input-bg-focus);
            box-shadow:
                0 0 0 3px rgba(102, 126, 234, 0.15),
                var(--input-shadow, inset 0 1px 2px rgba(0, 0, 0, 0.1));
        }

        input:hover,
        textarea:hover,
        select:hover {
            border-color: var(--color-input-border-hover);
            background-color: var(--color-input-bg-hover);
        }

        /* Form hint text */
        .form-hint {
            font-size: 12px;
            color: var(--color-text-muted, #71717a);
            margin-top: 6px;
            display: block;
            line-height: 1.4;
        }

        /* Validation states */
        .input-success {
            border-color: #10b981 !important;
        }

        .input-error {
            border-color: #ef4444 !important;
        }

        .btn {
            display: inline-block;
            padding: 13px 24px;
            font-size: 15px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: inherit;
            letter-spacing: 0.01em;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.25);
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(255,255,255,0.12), transparent);
            pointer-events: none;
        }

        .btn-primary:hover {
            transform: translateY(-1px) scale(1.01);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.35);
        }

        .btn-primary:active {
            transform: translateY(0) scale(0.99);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .btn-secondary {
            background-color: var(--btn-secondary-bg, #3f3f46);
            color: var(--btn-secondary-text, #efeff1);
        }

        .btn-secondary:hover {
            background-color: var(--btn-secondary-bg-hover, #52525b);
        }

        .btn-danger {
            background-color: var(--btn-danger-bg, #dc2626);
            color: var(--btn-danger-text, white);
        }

        .btn-danger:hover {
            background-color: var(--btn-danger-bg-hover, #b91c1c);
        }

        .btn-success {
            background-color: var(--btn-success-bg, #059669);
            color: var(--btn-success-text, white);
        }

        .btn-success:hover {
            background-color: var(--btn-success-bg-hover, #047857);
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 14px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background-color: var(--alert-success-bg, rgba(16, 185, 129, 0.1));
            color: var(--alert-success-text, #10b981);
            border: 1px solid var(--alert-success-border, #10b981);
        }

        .alert-error {
            background-color: var(--alert-error-bg, rgba(239, 68, 68, 0.1));
            color: var(--alert-error-text, #ef4444);
            border: 1px solid var(--alert-error-border, #ef4444);
        }

        .text-center {
            text-align: center;
        }

        .mt-4 {
            margin-top: 16px;
        }

        .link {
            color: var(--color-text-link, #667eea);
            text-decoration: none;
            transition: color 0.2s ease;
            position: relative;
        }

        .link::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 1px;
            background-color: currentColor;
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.25s ease;
        }

        .link:hover {
            color: var(--color-text-link-hover, #818cf8);
        }

        .link:hover::after {
            transform: scaleX(1);
            transform-origin: left;
        }

        .navbar {
            background-color: var(--navbar-bg, #18181b);
            padding: 16px 0;
            border-bottom: 1px solid var(--navbar-border, #3f3f46);
        }

        .navbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
        }

        .navbar-nav {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .navbar-user {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        main {
            flex: 1;
            padding: 32px 0;
        }

        .grid {
            display: grid;
            gap: 24px;
        }

        .grid-cols-2 {
            grid-template-columns: repeat(2, 1fr);
        }

        .grid-cols-3 {
            grid-template-columns: repeat(3, 1fr);
        }

        .card {
            background-color: var(--card-bg, #18181b);
            border-radius: 12px;
            padding: 24px;
            border: 1px solid var(--card-border, #3f3f46);
        }

        .card-header {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 16px;
            color: var(--color-text-primary, #efeff1);
        }

        .sidebar {
            background-color: var(--color-surface, #18181b);
            border-radius: 12px;
            padding: 24px;
            height: fit-content;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .sidebar-link {
            display: block;
            padding: 12px 16px;
            color: var(--color-text-secondary, #b3b3b5);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .sidebar-link:hover {
            background-color: var(--color-surface-hover, #3f3f46);
            color: var(--color-text-primary, #efeff1);
        }

        .sidebar-link.active {
            background-color: var(--accent-primary, #667eea);
            color: white;
        }

        .user-card {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background-color: var(--color-bg-primary, #0e0e10);
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .user-card-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-card-info {
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }

        .user-card-name {
            font-weight: 600;
            color: var(--color-text-primary, #efeff1);
        }

        .user-card-username {
            font-size: 14px;
            color: var(--color-text-muted, #71717a);
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }

        .status-online {
            background-color: var(--status-online, #10b981);
        }

        .status-offline {
            background-color: var(--status-offline, #6b7280);
        }

        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 48px 0;
            margin-bottom: 32px;
        }

        .profile-info {
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
        }

        .profile-details h1 {
            color: white;
            margin-bottom: 8px;
        }

        .profile-details p {
            color: rgba(255, 255, 255, 0.8);
        }

        .tabs {
            display: flex;
            gap: 8px;
            border-bottom: 2px solid var(--color-border-primary, #3f3f46);
            margin-bottom: 24px;
        }

        .tab {
            padding: 12px 24px;
            color: var(--color-text-secondary, #b3b3b5);
            text-decoration: none;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }

        .tab:hover {
            color: var(--color-text-primary, #efeff1);
        }

        .tab.active {
            color: var(--accent-primary, #667eea);
            border-bottom-color: var(--accent-primary, #667eea);
        }

        .game-card {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background-color: var(--color-bg-primary, #0e0e10);
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .game-icon {
            width: 48px;
            height: 48px;
            background-color: var(--color-surface-hover, #3f3f46);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .game-info {
            flex: 1;
        }

        .game-name {
            font-weight: 600;
            color: var(--color-text-primary, #efeff1);
            margin-bottom: 4px;
        }

        .game-playtime {
            font-size: 14px;
            color: var(--color-text-muted, #71717a);
        }

        .achievement-progress {
            width: 100%;
            height: 8px;
            background-color: var(--color-surface-hover, #3f3f46);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
        }

        .achievement-progress-bar {
            height: 100%;
            background: var(--accent-gradient, linear-gradient(135deg, #667eea 0%, #764ba2 100%));
            transition: width 0.3s ease;
        }

        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: var(--color-text-muted, #71717a);
        }

        .empty-state-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .grid-cols-2 {
                grid-template-columns: 1fr;
            }

            .profile-info {
                flex-direction: column;
                text-align: center;
            }

            .sidebar {
                margin-bottom: 24px;
            }
        }

        /* Toast Notification Animations */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    </style>
    @stack('head')
    @stack('styles')
    <!-- Alpine.js and App Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @yield('content')

    <script>
        // Laravel data for JavaScript
        window.Laravel = {
            user: @auth @json(auth()->user()->only(['id', 'display_name'])) @else null @endauth,
            csrfToken: '{{ csrf_token() }}'
        };
    </script>
    @stack('scripts')
</body>
</html>