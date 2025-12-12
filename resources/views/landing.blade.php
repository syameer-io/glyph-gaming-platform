<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Glyph - The ultimate gaming community platform. Unite with gamers, build legendary teams, and dominate together with Steam integration and intelligent matchmaking.">
    <meta name="keywords" content="gaming, community, teams, matchmaking, Steam, voice chat, lobbies">
    <title>Glyph - Unite. Play. Dominate.</title>

    {{-- Google Fonts for Gristela-like effect --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <style>
        /* =============================================================================
           CSS Reset & Base Styles
           ============================================================================= */
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            /* Color Palette */
            --color-bg-primary: #0e0e10;
            --color-bg-secondary: #18181b;
            --color-bg-tertiary: #1e1e22;
            --color-bg-elevated: #27272a;

            --color-text-primary: #efeff1;
            --color-text-secondary: #b3b3b5;
            --color-text-muted: #71717a;

            --accent-primary: #667eea;
            --accent-secondary: #764ba2;
            --accent-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --accent-gradient-hover: linear-gradient(135deg, #5a6fd6 0%, #6a4190 100%);

            --glow-primary: rgba(102, 126, 234, 0.4);
            --glow-secondary: rgba(118, 75, 162, 0.3);

            /* Transitions */
            --transition-fast: 150ms ease;
            --transition-medium: 200ms ease;
            --transition-slow: 300ms ease;

            /* Border Radius */
            --radius-sm: 4px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            --radius-2xl: 24px;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--color-bg-primary);
            color: var(--color-text-primary);
            line-height: 1.6;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--color-bg-secondary);
        }

        ::-webkit-scrollbar-thumb {
            background: #3f3f46;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #52525b;
        }

        /* Selection */
        ::selection {
            background-color: var(--accent-primary);
            color: #ffffff;
        }

        /* =============================================================================
           Hero Section
           ============================================================================= */
        .hero {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            padding: 20px;
        }

        /* Animated Background Mesh */
        .hero-background {
            position: absolute;
            inset: 0;
            z-index: 0;
            overflow: hidden;
        }

        .hero-background::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 50% at 20% 40%, rgba(102, 126, 234, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse 60% 60% at 80% 20%, rgba(118, 75, 162, 0.12) 0%, transparent 50%),
                radial-gradient(ellipse 50% 70% at 50% 80%, rgba(102, 126, 234, 0.1) 0%, transparent 50%);
            animation: meshFloat 20s ease-in-out infinite;
        }

        @keyframes meshFloat {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 1; }
            25% { transform: translate(2%, -2%) scale(1.02); opacity: 0.9; }
            50% { transform: translate(-1%, 1%) scale(0.98); opacity: 1; }
            75% { transform: translate(-2%, -1%) scale(1.01); opacity: 0.95; }
        }

        /* Floating Orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.6;
            pointer-events: none;
        }

        .orb-1 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, var(--accent-primary) 0%, transparent 70%);
            top: -100px;
            left: -100px;
            animation: floatOrb1 25s ease-in-out infinite;
        }

        .orb-2 {
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, var(--accent-secondary) 0%, transparent 70%);
            top: 20%;
            right: -80px;
            animation: floatOrb2 30s ease-in-out infinite;
        }

        .orb-3 {
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, var(--accent-primary) 0%, transparent 70%);
            bottom: -50px;
            left: 30%;
            animation: floatOrb3 22s ease-in-out infinite;
        }

        .orb-4 {
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, var(--accent-secondary) 0%, transparent 70%);
            bottom: 30%;
            right: 20%;
            animation: floatOrb4 28s ease-in-out infinite;
        }

        @keyframes floatOrb1 {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.5; }
            33% { transform: translate(50px, 30px) scale(1.1); opacity: 0.7; }
            66% { transform: translate(-30px, -20px) scale(0.9); opacity: 0.4; }
        }

        @keyframes floatOrb2 {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.4; }
            33% { transform: translate(-40px, 40px) scale(0.9); opacity: 0.6; }
            66% { transform: translate(30px, -30px) scale(1.1); opacity: 0.5; }
        }

        @keyframes floatOrb3 {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.5; }
            33% { transform: translate(40px, -30px) scale(1.05); opacity: 0.6; }
            66% { transform: translate(-50px, 20px) scale(0.95); opacity: 0.4; }
        }

        @keyframes floatOrb4 {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.4; }
            33% { transform: translate(-30px, -40px) scale(1.1); opacity: 0.5; }
            66% { transform: translate(40px, 30px) scale(0.9); opacity: 0.6; }
        }

        /* Hero Content */
        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 800px;
            animation: heroEntrance 1s ease-out;
        }

        @keyframes heroEntrance {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Logo */
        .logo {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: clamp(4rem, 12vw, 8rem);
            font-weight: 400;
            letter-spacing: 0.15em;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem;
            text-shadow: 0 0 80px var(--glow-primary);
            animation: logoPulse 4s ease-in-out infinite;
        }

        @keyframes logoPulse {
            0%, 100% { filter: brightness(1); }
            50% { filter: brightness(1.1); }
        }

        /* Tagline */
        .tagline {
            font-size: clamp(1.5rem, 4vw, 2.5rem);
            font-weight: 600;
            color: var(--color-text-primary);
            margin-bottom: 0.75rem;
            letter-spacing: 0.05em;
        }

        .subtitle {
            font-size: clamp(1rem, 2.5vw, 1.25rem);
            color: var(--color-text-secondary);
            margin-bottom: 3rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Buttons */
        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 32px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            border-radius: var(--radius-lg);
            transition: all var(--transition-medium);
            cursor: pointer;
            border: none;
            min-width: 140px;
        }

        .btn:focus {
            outline: 2px solid var(--accent-primary);
            outline-offset: 2px;
        }

        .btn-primary {
            background: var(--accent-gradient);
            color: #ffffff;
            box-shadow: 0 4px 20px var(--glow-primary);
        }

        .btn-primary:hover {
            background: var(--accent-gradient-hover);
            transform: translateY(-3px);
            box-shadow: 0 8px 30px var(--glow-primary);
        }

        .btn-primary:active {
            transform: translateY(-1px);
        }

        .btn-outline {
            background: transparent;
            color: var(--color-text-primary);
            border: 2px solid transparent;
            background-image: linear-gradient(var(--color-bg-primary), var(--color-bg-primary)), var(--accent-gradient);
            background-origin: border-box;
            background-clip: padding-box, border-box;
        }

        .btn-outline:hover {
            background-image: linear-gradient(rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)), var(--accent-gradient);
            transform: translateY(-3px);
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.2);
        }

        /* Scroll Indicator */
        .scroll-indicator {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1;
            animation: bounce 2s ease-in-out infinite;
        }

        .scroll-indicator svg {
            width: 32px;
            height: 32px;
            color: var(--color-text-muted);
            transition: color var(--transition-fast);
        }

        .scroll-indicator:hover svg {
            color: var(--accent-primary);
        }

        @keyframes bounce {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            50% { transform: translateX(-50%) translateY(12px); }
        }

        /* =============================================================================
           Features Section
           ============================================================================= */
        .features {
            padding: 100px 20px;
            background: linear-gradient(180deg, var(--color-bg-primary) 0%, var(--color-bg-secondary) 100%);
            position: relative;
        }

        .features::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.3), transparent);
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .features-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .features-title {
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 700;
            color: var(--color-text-primary);
            margin-bottom: 1rem;
        }

        .features-subtitle {
            font-size: clamp(1rem, 2vw, 1.125rem);
            color: var(--color-text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        /* Feature Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        @media (max-width: 1024px) {
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .features-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Feature Card */
        .feature-card {
            background: rgba(24, 24, 27, 0.8);
            border: 1px solid rgba(63, 63, 70, 0.5);
            border-radius: var(--radius-xl);
            padding: 32px;
            transition: all var(--transition-slow);
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--accent-gradient);
            opacity: 0;
            transition: opacity var(--transition-slow);
            z-index: 0;
        }

        .feature-card:hover {
            transform: translateY(-8px) scale(1.02);
            border-color: rgba(102, 126, 234, 0.5);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3), 0 0 40px var(--glow-primary);
        }

        .feature-card:hover::before {
            opacity: 0.05;
        }

        .feature-card > * {
            position: relative;
            z-index: 1;
        }

        .feature-icon {
            width: 56px;
            height: 56px;
            background: var(--accent-gradient);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px var(--glow-primary);
        }

        .feature-icon svg {
            width: 28px;
            height: 28px;
            color: #ffffff;
        }

        .feature-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--color-text-primary);
            margin-bottom: 12px;
        }

        .feature-description {
            font-size: 0.95rem;
            color: var(--color-text-secondary);
            line-height: 1.7;
        }

        /* Scroll Reveal Animation */
        .reveal {
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Staggered delays for feature cards */
        .feature-card:nth-child(1) { transition-delay: 0s; }
        .feature-card:nth-child(2) { transition-delay: 0.1s; }
        .feature-card:nth-child(3) { transition-delay: 0.2s; }
        .feature-card:nth-child(4) { transition-delay: 0.3s; }
        .feature-card:nth-child(5) { transition-delay: 0.4s; }
        .feature-card:nth-child(6) { transition-delay: 0.5s; }

        /* =============================================================================
           CTA Section
           ============================================================================= */
        .cta {
            padding: 100px 20px;
            background: linear-gradient(180deg, var(--color-bg-secondary) 0%, var(--color-bg-primary) 50%, var(--color-bg-secondary) 100%);
            position: relative;
            overflow: hidden;
        }

        .cta::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .cta-container {
            max-width: 700px;
            margin: 0 auto;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .cta-title {
            font-size: clamp(2rem, 5vw, 2.75rem);
            font-weight: 700;
            color: var(--color-text-primary);
            margin-bottom: 1.5rem;
        }

        .cta-subtitle {
            font-size: clamp(1rem, 2vw, 1.125rem);
            color: var(--color-text-secondary);
            margin-bottom: 2.5rem;
        }

        .cta-button {
            padding: 18px 48px;
            font-size: 1.125rem;
        }

        .cta-link {
            display: block;
            margin-top: 1.5rem;
            color: var(--color-text-secondary);
            font-size: 0.95rem;
        }

        .cta-link a {
            color: var(--accent-primary);
            text-decoration: none;
            font-weight: 500;
            transition: color var(--transition-fast);
        }

        .cta-link a:hover {
            color: #8b9ff0;
            text-decoration: underline;
        }

        /* =============================================================================
           Footer
           ============================================================================= */
        .footer {
            padding: 30px 20px;
            background: var(--color-bg-primary);
            border-top: 1px solid rgba(63, 63, 70, 0.3);
            text-align: center;
        }

        .footer-text {
            color: var(--color-text-muted);
            font-size: 0.875rem;
        }

        /* =============================================================================
           Accessibility: Reduced Motion
           ============================================================================= */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }

            html {
                scroll-behavior: auto;
            }

            .reveal {
                opacity: 1;
                transform: none;
            }
        }

        /* =============================================================================
           Focus Visible for Keyboard Navigation
           ============================================================================= */
        .btn:focus-visible {
            outline: 3px solid var(--accent-primary);
            outline-offset: 3px;
        }

        a:focus-visible {
            outline: 2px solid var(--accent-primary);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    {{-- Hero Section --}}
    <section class="hero" id="hero">
        <div class="hero-background">
            <div class="orb orb-1" aria-hidden="true"></div>
            <div class="orb orb-2" aria-hidden="true"></div>
            <div class="orb orb-3" aria-hidden="true"></div>
            <div class="orb orb-4" aria-hidden="true"></div>
        </div>

        <div class="hero-content">
            <h1 class="logo">GLYPH</h1>
            <p class="tagline">Unite. Play. Dominate.</p>
            <p class="subtitle">Where Gamers Build Legendary Teams</p>

            <div class="hero-buttons">
                <a href="{{ route('login') }}" class="btn btn-outline">Sign In</a>
                <a href="{{ route('register') }}" class="btn btn-primary">Sign Up</a>
            </div>
        </div>

        <a href="#features" class="scroll-indicator" aria-label="Scroll to features">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
            </svg>
        </a>
    </section>

    {{-- Features Section --}}
    <section class="features" id="features">
        <div class="features-container">
            <header class="features-header reveal">
                <h2 class="features-title">Why Choose Glyph?</h2>
                <p class="features-subtitle">Built by gamers, for gamers. Not just another chat app.</p>
            </header>

            <div class="features-grid">
                {{-- Feature 1: Steam Integration --}}
                <article class="feature-card reveal">
                    <div class="feature-icon">
                        <svg fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 2C6.48 2 2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3l-.5 3H13v6.95c5.05-.5 9-4.76 9-9.95 0-5.52-4.48-10-10-10z"/>
                            <circle cx="9" cy="9" r="1.5"/>
                            <path d="M11.5 2.05c.16-.03.33-.05.5-.05 5.52 0 10 4.48 10 10 0 .17-.02.34-.05.5"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">Deep Steam Integration</h3>
                    <p class="feature-description">Sync your profile, games, and achievements. Show off your gaming stats and current activity in real-time to your community.</p>
                </article>

                {{-- Feature 2: Smart Matchmaking --}}
                <article class="feature-card reveal">
                    <div class="feature-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">Smart Team Matching</h3>
                    <p class="feature-description">Our research-backed algorithm finds your perfect teammates based on skill level, play style, schedule, and region.</p>
                </article>

                {{-- Feature 3: Role-Based Teams --}}
                <article class="feature-card reveal">
                    <div class="feature-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">Role-Based Team Building</h3>
                    <p class="feature-description">Define your gaming roles - IGL, Support, Entry Fragger. Find teams that need exactly what you bring to the table.</p>
                </article>

                {{-- Feature 4: Game Lobbies --}}
                <article class="feature-card reveal">
                    <div class="feature-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">Universal Game Lobbies</h3>
                    <p class="feature-description">Create and join lobbies for any game. Steam links, server addresses, lobby codes - all in one convenient place.</p>
                </article>

                {{-- Feature 5: Community Goals --}}
                <article class="feature-card reveal">
                    <div class="feature-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">Community Achievements</h3>
                    <p class="feature-description">Set server-wide goals, track progress together, and climb the leaderboards with your gaming community.</p>
                </article>

                {{-- Feature 6: Voice Chat --}}
                <article class="feature-card reveal">
                    <div class="feature-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">Crystal Clear Voice</h3>
                    <p class="feature-description">High-quality voice channels powered by WebRTC. No third-party apps needed - jump right in and communicate seamlessly.</p>
                </article>
            </div>
        </div>
    </section>

    {{-- CTA Section --}}
    <section class="cta reveal" id="cta">
        <div class="cta-container">
            <h2 class="cta-title">Ready to Level Up Your Gaming?</h2>
            <p class="cta-subtitle">Join thousands of gamers building their dream teams on Glyph.</p>
            <a href="{{ route('register') }}" class="btn btn-primary cta-button">Get Started</a>
            <p class="cta-link">Already have an account? <a href="{{ route('login') }}">Sign In</a></p>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="footer">
        <p class="footer-text">&copy; {{ date('Y') }} Glyph. All rights reserved.</p>
    </footer>

    {{-- Scroll Reveal Script (Intersection Observer) --}}
    <script>
        (function() {
            'use strict';

            // Check for reduced motion preference
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (prefersReducedMotion) {
                // If user prefers reduced motion, show all elements immediately
                document.querySelectorAll('.reveal').forEach(function(el) {
                    el.classList.add('visible');
                });
                return;
            }

            // Intersection Observer for scroll reveal
            const observerOptions = {
                root: null,
                rootMargin: '0px 0px -50px 0px',
                threshold: 0.1
            };

            const revealOnScroll = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            // Observe all reveal elements
            document.querySelectorAll('.reveal').forEach(function(el) {
                revealOnScroll.observe(el);
            });

            // Smooth scroll for anchor links (progressive enhancement)
            document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    var targetId = this.getAttribute('href');
                    var target = document.querySelector(targetId);
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        })();
    </script>
</body>
</html>
