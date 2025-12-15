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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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

        /* Animated Gradient Mesh Background */
        .auth-container::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 0;
            background:
                radial-gradient(ellipse 80% 50% at 20% 40%, var(--auth-gradient-1, rgba(102, 126, 234, 0.15)) 0%, transparent 50%),
                radial-gradient(ellipse 60% 60% at 80% 20%, var(--auth-gradient-2, rgba(118, 75, 162, 0.12)) 0%, transparent 50%),
                radial-gradient(ellipse 50% 70% at 50% 80%, var(--auth-gradient-3, rgba(102, 126, 234, 0.1)) 0%, transparent 50%);
            animation: meshFloat 20s ease-in-out infinite;
        }

        @keyframes meshFloat {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 1; }
            25% { transform: translate(2%, -2%) scale(1.02); opacity: 0.9; }
            50% { transform: translate(-1%, 1%) scale(0.98); opacity: 1; }
            75% { transform: translate(-2%, -1%) scale(1.01); opacity: 0.95; }
        }

        .auth-box {
            background-color: var(--auth-box-bg, #18181b);
            border-radius: 16px;
            padding: 48px;
            width: 100%;
            max-width: 480px;
            box-shadow: var(--shadow-xl, 0 20px 40px rgba(0, 0, 0, 0.4));
            position: relative;
            z-index: 1;
            border: 1px solid var(--auth-box-border, rgba(102, 126, 234, 0.2));
            animation: authBoxEntrance 0.5s ease-out;
        }

        .auth-box:hover {
            border-color: var(--auth-box-border-hover, rgba(102, 126, 234, 0.35));
        }

        @keyframes authBoxEntrance {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
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
            width: 20px;
            height: 20px;
            color: var(--color-text-muted, #71717a);
            pointer-events: none;
            transition: color 0.2s;
        }

        .input-with-icon:focus-within .input-icon {
            color: var(--accent-primary, #667eea);
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            padding: 4px;
            cursor: pointer;
            color: var(--color-text-muted, #71717a);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s;
        }

        .password-toggle:hover {
            color: var(--color-text-secondary, #b3b3b5);
        }

        .password-toggle svg {
            width: 20px;
            height: 20px;
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
            gap: 12px;
            margin: 16px 0 24px;
        }

        .otp-input {
            width: 52px;
            height: 64px;
            text-align: center;
            font-size: 28px;
            font-weight: 600;
            background-color: var(--color-input-bg, #0e0e10);
            border: 2px solid var(--color-input-border, #3f3f46);
            border-radius: 12px;
            color: var(--color-text-primary, #efeff1);
            caret-color: var(--accent-primary, #667eea);
            transition: all 0.2s ease;
            padding: 0;
        }

        .otp-input:focus {
            outline: none;
            border-color: var(--color-input-border-focus, #667eea);
            background-color: var(--color-input-bg-focus, #18181b);
            box-shadow: 0 0 0 3px var(--accent-primary-light, rgba(102, 126, 234, 0.2));
        }

        .otp-input.has-value {
            border-color: var(--accent-primary, #667eea);
            background-color: var(--accent-primary-light, rgba(102, 126, 234, 0.05));
        }

        .otp-input::placeholder {
            color: var(--color-text-faint, #52525b);
        }

        .otp-input::-webkit-outer-spin-button,
        .otp-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        @media (max-width: 400px) {
            .otp-input-container {
                gap: 8px;
            }
            .otp-input {
                width: 44px;
                height: 56px;
                font-size: 24px;
            }
        }

        /* Reduced motion preference */
        @media (prefers-reduced-motion: reduce) {
            .auth-container::before {
                animation: none;
            }
            .auth-box {
                animation: none;
            }
        }

        .logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo h1 {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: var(--color-text-secondary, #b3b3b5);
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        textarea,
        select {
            width: 100%;
            padding: 12px 16px;
            background-color: var(--color-input-bg, #0e0e10);
            border: 2px solid var(--color-input-border, #3f3f46);
            border-radius: 8px;
            color: var(--color-input-text, #efeff1);
            font-size: 16px;
            transition: all 0.2s;
            font-family: inherit;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--color-input-border-focus, #667eea);
            background-color: var(--color-input-bg-focus, #18181b);
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
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
            transition: color 0.2s;
        }

        .link:hover {
            color: var(--color-text-link-hover, #764ba2);
            text-decoration: underline;
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