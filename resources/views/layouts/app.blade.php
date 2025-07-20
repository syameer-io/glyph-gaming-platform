<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if(isset($server))
    <meta name="server-id" content="{{ $server->id }}">
    @endif
    <title>@yield('title', 'Glyph')</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #0e0e10;
            color: #efeff1;
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
        }

        .auth-box {
            background-color: #18181b;
            border-radius: 12px;
            padding: 40px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
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
            color: #b3b3b5;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        textarea,
        select {
            width: 100%;
            padding: 12px 16px;
            background-color: #0e0e10;
            border: 2px solid #3f3f46;
            border-radius: 8px;
            color: #efeff1;
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
            border-color: #667eea;
            background-color: #18181b;
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
            background-color: #3f3f46;
            color: #efeff1;
        }

        .btn-secondary:hover {
            background-color: #52525b;
        }

        .btn-danger {
            background-color: #dc2626;
            color: white;
        }

        .btn-danger:hover {
            background-color: #b91c1c;
        }

        .btn-success {
            background-color: #059669;
            color: white;
        }

        .btn-success:hover {
            background-color: #047857;
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
            background-color: #065f46;
            color: #6ee7b7;
            border: 1px solid #047857;
        }

        .alert-error {
            background-color: #7f1d1d;
            color: #fca5a5;
            border: 1px solid #991b1b;
        }

        .text-center {
            text-align: center;
        }

        .mt-4 {
            margin-top: 16px;
        }

        .link {
            color: #667eea;
            text-decoration: none;
            transition: color 0.2s;
        }

        .link:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .navbar {
            background-color: #18181b;
            padding: 16px 0;
            border-bottom: 1px solid #3f3f46;
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
            background-color: #18181b;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #3f3f46;
        }

        .card-header {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #efeff1;
        }

        .sidebar {
            background-color: #18181b;
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
            color: #b3b3b5;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .sidebar-link:hover {
            background-color: #3f3f46;
            color: #efeff1;
        }

        .sidebar-link.active {
            background-color: #667eea;
            color: white;
        }

        .user-card {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background-color: #0e0e10;
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
        }

        .user-card-name {
            font-weight: 600;
            color: #efeff1;
        }

        .user-card-username {
            font-size: 14px;
            color: #71717a;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }

        .status-online {
            background-color: #10b981;
        }

        .status-offline {
            background-color: #6b7280;
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
            border-bottom: 2px solid #3f3f46;
            margin-bottom: 24px;
        }

        .tab {
            padding: 12px 24px;
            color: #b3b3b5;
            text-decoration: none;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }

        .tab:hover {
            color: #efeff1;
        }

        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .game-card {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background-color: #0e0e10;
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .game-icon {
            width: 48px;
            height: 48px;
            background-color: #3f3f46;
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
            color: #efeff1;
            margin-bottom: 4px;
        }

        .game-playtime {
            font-size: 14px;
            color: #71717a;
        }

        .achievement-progress {
            width: 100%;
            height: 8px;
            background-color: #3f3f46;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
        }

        .achievement-progress-bar {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
        }

        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: #71717a;
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
    </style>
    @stack('styles')
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