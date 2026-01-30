<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Glyph - The ultimate gaming community platform. Unite with gamers, build legendary teams, and dominate together with Steam integration and intelligent matchmaking.">
    <meta name="keywords" content="gaming, community, teams, matchmaking, Steam, voice chat, lobbies">
    <title>Glyph - Unite. Play. Dominate.</title>

    {{-- Google Fonts: Orbitron (futuristic headings) + Rajdhani (technical body) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* =============================================================================
           CSS Reset & Base Styles
           ============================================================================= */
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* CSS Custom Property for animated border rotation */
        @property --border-angle {
            syntax: '<angle>';
            initial-value: 0deg;
            inherits: false;
        }

        :root {
            /* Original Color Palette */
            --color-bg-primary: #0e0e10;
            --color-bg-secondary: #18181b;
            --color-bg-tertiary: #1e1e22;
            --color-bg-elevated: #27272a;
            --color-bg-surface: #1a1a2e;

            --color-text-primary: #efeff1;
            --color-text-secondary: #b3b3b5;
            --color-text-muted: #71717a;

            --accent-primary: #667eea;
            --accent-secondary: #764ba2;
            --accent-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --accent-gradient-hover: linear-gradient(135deg, #5a6fd6 0%, #6a4190 100%);

            --glow-primary: rgba(102, 126, 234, 0.4);
            --glow-secondary: rgba(118, 75, 162, 0.3);

            /* Cyberpunk Accent Colors */
            --accent-cyan: #00f5ff;
            --accent-magenta: #ff00ff;
            --accent-cyan-glow: rgba(0, 245, 255, 0.4);
            --accent-magenta-glow: rgba(255, 0, 255, 0.3);
            --grid-color: rgba(102, 126, 234, 0.08);

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
            font-family: 'Rajdhani', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--color-bg-primary);
            color: var(--color-text-primary);
            line-height: 1.6;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            font-weight: 500;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--color-bg-secondary);
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, var(--accent-cyan), var(--accent-magenta));
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, var(--accent-magenta), var(--accent-cyan));
        }

        /* Selection */
        ::selection {
            background-color: var(--accent-cyan);
            color: #000000;
        }

        /* =============================================================================
           Global Overlays & Effects
           ============================================================================= */

        /* Noise Texture Overlay */
        .noise-overlay {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 9999;
            opacity: 0.03;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
        }

        /* Scanline Effect */
        .scanlines {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 9998;
            background: repeating-linear-gradient(
                0deg,
                transparent,
                transparent 2px,
                rgba(0, 0, 0, 0.03) 2px,
                rgba(0, 0, 0, 0.03) 4px
            );
        }

        /* Vignette Effect */
        .vignette {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 9997;
            background: radial-gradient(ellipse at center, transparent 40%, rgba(0, 0, 0, 0.4) 100%);
        }

        /* Cursor Glow Trail */
        .cursor-glow {
            position: fixed;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(0, 245, 255, 0.08) 0%, transparent 70%);
            pointer-events: none;
            z-index: 9996;
            transform: translate(-50%, -50%);
            transition: opacity 0.3s ease;
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

        /* Perspective Grid Background */
        .hero-grid {
            position: absolute;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            perspective: 500px;
        }

        .hero-grid::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: -50%;
            right: -50%;
            height: 100%;
            background-image:
                linear-gradient(90deg, var(--grid-color) 1px, transparent 1px),
                linear-gradient(180deg, var(--grid-color) 1px, transparent 1px);
            background-size: 80px 80px;
            transform: rotateX(60deg);
            transform-origin: center bottom;
            animation: gridPulse 4s ease-in-out infinite;
            mask-image: linear-gradient(to top, rgba(0,0,0,0.6) 0%, transparent 60%);
            -webkit-mask-image: linear-gradient(to top, rgba(0,0,0,0.6) 0%, transparent 60%);
        }

        @keyframes gridPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }

        /* Animated Background Mesh */
        .hero-background {
            position: absolute;
            inset: 0;
            z-index: 1;
            overflow: hidden;
        }

        .hero-background::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 50% at 20% 40%, rgba(0, 245, 255, 0.12) 0%, transparent 50%),
                radial-gradient(ellipse 60% 60% at 80% 20%, rgba(255, 0, 255, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse 50% 70% at 50% 80%, rgba(102, 126, 234, 0.1) 0%, transparent 50%);
            animation: meshFloat 20s ease-in-out infinite;
        }

        @keyframes meshFloat {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 1; }
            25% { transform: translate(2%, -2%) scale(1.02); opacity: 0.9; }
            50% { transform: translate(-1%, 1%) scale(0.98); opacity: 1; }
            75% { transform: translate(-2%, -1%) scale(1.01); opacity: 0.95; }
        }

        /* Enhanced Floating Orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.7;
            pointer-events: none;
        }

        .orb-1 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, var(--accent-cyan) 0%, transparent 70%);
            top: -150px;
            left: -150px;
            animation: floatOrb1 25s ease-in-out infinite;
        }

        .orb-2 {
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, var(--accent-magenta) 0%, transparent 70%);
            top: 15%;
            right: -100px;
            animation: floatOrb2 30s ease-in-out infinite;
        }

        .orb-3 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, var(--accent-primary) 0%, transparent 70%);
            bottom: -80px;
            left: 25%;
            animation: floatOrb3 22s ease-in-out infinite;
        }

        .orb-4 {
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, var(--accent-secondary) 0%, transparent 70%);
            bottom: 25%;
            right: 15%;
            animation: floatOrb4 28s ease-in-out infinite;
        }

        @keyframes floatOrb1 {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.6; }
            33% { transform: translate(60px, 40px) scale(1.15); opacity: 0.8; }
            66% { transform: translate(-40px, -30px) scale(0.9); opacity: 0.5; }
        }

        @keyframes floatOrb2 {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.5; }
            33% { transform: translate(-50px, 50px) scale(0.9); opacity: 0.7; }
            66% { transform: translate(40px, -40px) scale(1.15); opacity: 0.6; }
        }

        @keyframes floatOrb3 {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.6; }
            33% { transform: translate(50px, -40px) scale(1.1); opacity: 0.7; }
            66% { transform: translate(-60px, 30px) scale(0.95); opacity: 0.5; }
        }

        @keyframes floatOrb4 {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.5; }
            33% { transform: translate(-40px, -50px) scale(1.15); opacity: 0.6; }
            66% { transform: translate(50px, 40px) scale(0.9); opacity: 0.7; }
        }

        /* Floating Geometric Shapes */
        .floating-shapes {
            position: absolute;
            inset: 0;
            z-index: 2;
            pointer-events: none;
            overflow: hidden;
        }

        .shape {
            position: absolute;
            opacity: 0.15;
            animation: floatShape 20s ease-in-out infinite;
        }

        .shape.hexagon {
            width: 120px;
            height: 104px;
            background: linear-gradient(135deg, var(--accent-cyan), var(--accent-magenta));
            clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
            top: 15%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape.triangle {
            width: 0;
            height: 0;
            border-left: 50px solid transparent;
            border-right: 50px solid transparent;
            border-bottom: 86px solid var(--accent-cyan);
            opacity: 0.1;
            top: 25%;
            right: 15%;
            animation-delay: -5s;
        }

        .shape.diamond {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, var(--accent-magenta), var(--accent-primary));
            transform: rotate(45deg);
            bottom: 20%;
            left: 8%;
            animation-delay: -10s;
        }

        .shape.circle-outline {
            width: 100px;
            height: 100px;
            border: 2px solid var(--accent-cyan);
            border-radius: 50%;
            bottom: 30%;
            right: 10%;
            animation-delay: -15s;
        }

        .shape.cross {
            width: 60px;
            height: 60px;
            top: 60%;
            left: 15%;
            animation-delay: -8s;
        }

        .shape.cross::before,
        .shape.cross::after {
            content: '';
            position: absolute;
            background: var(--accent-magenta);
        }

        .shape.cross::before {
            width: 100%;
            height: 20%;
            top: 40%;
            left: 0;
        }

        .shape.cross::after {
            width: 20%;
            height: 100%;
            top: 0;
            left: 40%;
        }

        @keyframes floatShape {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            25% {
                transform: translateY(-30px) rotate(5deg);
            }
            50% {
                transform: translateY(-10px) rotate(-3deg);
            }
            75% {
                transform: translateY(-40px) rotate(8deg);
            }
        }

        /* Particle Field */
        .particles {
            position: absolute;
            inset: 0;
            z-index: 2;
            pointer-events: none;
            overflow: hidden;
        }

        .particles span {
            position: absolute;
            width: 4px;
            height: 4px;
            background: var(--accent-cyan);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--accent-cyan), 0 0 20px var(--accent-cyan);
            animation: particleFloat 15s linear infinite;
            opacity: 0;
        }

        .particles span:nth-child(odd) {
            background: var(--accent-magenta);
            box-shadow: 0 0 10px var(--accent-magenta), 0 0 20px var(--accent-magenta);
        }

        .particles span:nth-child(1) { left: 10%; animation-delay: 0s; animation-duration: 12s; }
        .particles span:nth-child(2) { left: 20%; animation-delay: 2s; animation-duration: 14s; }
        .particles span:nth-child(3) { left: 30%; animation-delay: 4s; animation-duration: 16s; }
        .particles span:nth-child(4) { left: 40%; animation-delay: 1s; animation-duration: 13s; }
        .particles span:nth-child(5) { left: 50%; animation-delay: 3s; animation-duration: 15s; }
        .particles span:nth-child(6) { left: 60%; animation-delay: 5s; animation-duration: 11s; }
        .particles span:nth-child(7) { left: 70%; animation-delay: 2.5s; animation-duration: 17s; }
        .particles span:nth-child(8) { left: 80%; animation-delay: 0.5s; animation-duration: 14s; }
        .particles span:nth-child(9) { left: 90%; animation-delay: 3.5s; animation-duration: 12s; }
        .particles span:nth-child(10) { left: 5%; animation-delay: 4.5s; animation-duration: 16s; }
        .particles span:nth-child(11) { left: 15%; animation-delay: 1.5s; animation-duration: 13s; }
        .particles span:nth-child(12) { left: 25%; animation-delay: 5.5s; animation-duration: 15s; }
        .particles span:nth-child(13) { left: 35%; animation-delay: 2.2s; animation-duration: 11s; }
        .particles span:nth-child(14) { left: 45%; animation-delay: 0.8s; animation-duration: 14s; }
        .particles span:nth-child(15) { left: 55%; animation-delay: 3.8s; animation-duration: 12s; }
        .particles span:nth-child(16) { left: 65%; animation-delay: 1.2s; animation-duration: 16s; }
        .particles span:nth-child(17) { left: 75%; animation-delay: 4.2s; animation-duration: 13s; }
        .particles span:nth-child(18) { left: 85%; animation-delay: 2.8s; animation-duration: 15s; }
        .particles span:nth-child(19) { left: 95%; animation-delay: 0.2s; animation-duration: 11s; }
        .particles span:nth-child(20) { left: 50%; animation-delay: 5.2s; animation-duration: 17s; }

        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) scale(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) scale(1);
                opacity: 0;
            }
        }

        /* Hero Content */
        .hero-content {
            position: relative;
            z-index: 10;
            text-align: center;
            max-width: 900px;
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

        /* Logo with Glitch + Holographic Effect */
        .logo {
            font-family: 'Orbitron', sans-serif;
            font-size: clamp(4.5rem, 14vw, 9rem);
            font-weight: 900;
            letter-spacing: 0.2em;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
            text-shadow: 0 0 80px var(--glow-primary);
            animation: logoPulse 4s ease-in-out infinite;
        }

        /* Glitch Effect */
        .glitch {
            position: relative;
            animation: glitchJitter 5s infinite;
        }

        .glitch::before,
        .glitch::after {
            content: attr(data-text);
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            font-family: 'Orbitron', sans-serif;
            font-size: clamp(4.5rem, 14vw, 9rem);
            font-weight: 900;
            letter-spacing: 0.2em;
        }

        .glitch::before {
            background: linear-gradient(135deg, var(--accent-cyan) 0%, transparent 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: glitch1 3s infinite linear alternate-reverse;
            clip-path: polygon(0 0, 100% 0, 100% 45%, 0 45%);
        }

        .glitch::after {
            background: linear-gradient(135deg, var(--accent-magenta) 0%, transparent 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: glitch2 2s infinite linear alternate-reverse;
            clip-path: polygon(0 55%, 100% 55%, 100% 100%, 0 100%);
        }

        @keyframes glitch1 {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-3px); }
            40% { transform: translateX(3px); }
            60% { transform: translateX(-2px); }
            80% { transform: translateX(2px); }
        }

        @keyframes glitch2 {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(3px); }
            40% { transform: translateX(-3px); }
            60% { transform: translateX(2px); }
            80% { transform: translateX(-2px); }
        }

        @keyframes glitchJitter {
            0%, 95%, 100% { transform: translate(0); }
            96% { transform: translate(-2px, 1px); }
            97% { transform: translate(2px, -1px); }
            98% { transform: translate(-1px, -1px); }
            99% { transform: translate(1px, 1px); }
        }

        /* Holographic Shimmer */
        .holographic {
            position: relative;
        }

        .holographic::before {
            content: '';
            position: absolute;
            inset: -10px;
            background: linear-gradient(
                90deg,
                transparent 0%,
                rgba(0, 245, 255, 0.3) 25%,
                rgba(255, 0, 255, 0.3) 50%,
                rgba(0, 245, 255, 0.3) 75%,
                transparent 100%
            );
            background-size: 200% 100%;
            animation: holographicShimmer 3s linear infinite;
            filter: blur(30px);
            z-index: -1;
            opacity: 0.7;
        }

        @keyframes holographicShimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        @keyframes logoPulse {
            0%, 100% { filter: brightness(1) drop-shadow(0 0 30px var(--accent-cyan-glow)); }
            50% { filter: brightness(1.15) drop-shadow(0 0 50px var(--accent-magenta-glow)); }
        }

        /* Tagline with Typing Effect */
        .tagline {
            font-family: 'Orbitron', sans-serif;
            font-size: clamp(1.6rem, 4.5vw, 2.8rem);
            font-weight: 600;
            color: var(--color-text-primary);
            margin-bottom: 0.75rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .tagline .cursor {
            display: inline-block;
            width: 3px;
            height: 1em;
            background: var(--accent-cyan);
            margin-left: 4px;
            animation: blink 0.8s infinite;
            vertical-align: text-bottom;
        }

        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0; }
        }

        .subtitle {
            font-family: 'Rajdhani', sans-serif;
            font-size: clamp(1.1rem, 2.8vw, 1.4rem);
            font-weight: 500;
            color: var(--color-text-secondary);
            margin-bottom: 3rem;
            max-width: 550px;
            margin-left: auto;
            margin-right: auto;
            letter-spacing: 0.05em;
        }

        /* Buttons */
        .hero-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 16px 36px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            text-decoration: none;
            border-radius: var(--radius-lg);
            transition: all var(--transition-medium);
            cursor: pointer;
            border: none;
            min-width: 160px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            position: relative;
            overflow: hidden;
        }

        .btn:focus {
            outline: 2px solid var(--accent-cyan);
            outline-offset: 2px;
        }

        .btn-primary {
            background: var(--accent-gradient);
            color: #ffffff;
            box-shadow:
                0 4px 20px var(--glow-primary),
                0 0 40px var(--accent-cyan-glow),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            inset: -2px;
            background: linear-gradient(90deg, var(--accent-cyan), var(--accent-magenta), var(--accent-cyan));
            background-size: 200% 100%;
            border-radius: inherit;
            z-index: -1;
            animation: buttonGlow 3s linear infinite;
            opacity: 0;
            transition: opacity var(--transition-medium);
        }

        .btn-primary:hover::before {
            opacity: 1;
        }

        .btn-primary:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow:
                0 8px 40px var(--glow-primary),
                0 0 60px var(--accent-cyan-glow);
        }

        .btn-primary:active {
            transform: translateY(-2px) scale(1.01);
        }

        @keyframes buttonGlow {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .btn-outline {
            background: transparent;
            color: var(--color-text-primary);
            border: 2px solid transparent;
            background-image: linear-gradient(var(--color-bg-primary), var(--color-bg-primary)), linear-gradient(90deg, var(--accent-cyan), var(--accent-magenta));
            background-origin: border-box;
            background-clip: padding-box, border-box;
        }

        .btn-outline:hover {
            background-image: linear-gradient(rgba(0, 245, 255, 0.1), rgba(255, 0, 255, 0.1)), linear-gradient(90deg, var(--accent-cyan), var(--accent-magenta));
            transform: translateY(-4px);
            box-shadow: 0 4px 30px rgba(0, 245, 255, 0.3);
        }

        /* Scroll Indicator */
        .scroll-indicator {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
            animation: bounce 2s ease-in-out infinite;
        }

        .scroll-indicator svg {
            width: 36px;
            height: 36px;
            color: var(--accent-cyan);
            filter: drop-shadow(0 0 10px var(--accent-cyan));
            transition: all var(--transition-fast);
        }

        .scroll-indicator:hover svg {
            color: var(--accent-magenta);
            filter: drop-shadow(0 0 15px var(--accent-magenta));
        }

        @keyframes bounce {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            50% { transform: translateX(-50%) translateY(15px); }
        }

        /* =============================================================================
           Features Section
           ============================================================================= */
        .features {
            padding: 120px 20px;
            background: linear-gradient(180deg, var(--color-bg-primary) 0%, var(--color-bg-surface) 50%, var(--color-bg-secondary) 100%);
            position: relative;
            overflow: hidden;
        }

        .features::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent-cyan), var(--accent-magenta), var(--accent-cyan), transparent);
            box-shadow: 0 0 20px var(--accent-cyan);
        }

        .features-container {
            max-width: 1300px;
            margin: 0 auto;
        }

        .features-header {
            text-align: center;
            margin-bottom: 80px;
        }

        .features-title {
            font-family: 'Orbitron', sans-serif;
            font-size: clamp(2.2rem, 5.5vw, 3.5rem);
            font-weight: 700;
            color: var(--color-text-primary);
            margin-bottom: 1rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .features-subtitle {
            font-family: 'Rajdhani', sans-serif;
            font-size: clamp(1.1rem, 2.2vw, 1.25rem);
            color: var(--color-text-secondary);
            max-width: 650px;
            margin: 0 auto;
            letter-spacing: 0.03em;
        }

        /* Bento Grid Layout */
        .bento-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-template-rows: auto auto;
            gap: 24px;
        }

        @media (max-width: 1200px) {
            .bento-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .feature-card.large {
                grid-column: span 1;
            }
        }

        @media (max-width: 768px) {
            .bento-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Feature Card with Holographic Border */
        .feature-card {
            background: rgba(26, 26, 46, 0.8);
            border-radius: var(--radius-xl);
            padding: 32px;
            transition: all var(--transition-slow);
            position: relative;
            overflow: visible;
            backdrop-filter: blur(10px);
            transform-style: preserve-3d;
            cursor: pointer;
        }

        /* Large Cards for Steam Integration & Matchmaking */
        .feature-card.large {
            grid-column: span 2;
            padding: 40px;
        }

        .feature-card.large .feature-icon {
            width: 72px;
            height: 72px;
        }

        .feature-card.large .feature-icon svg {
            width: 36px;
            height: 36px;
        }

        .feature-card.large .feature-title {
            font-size: 1.5rem;
        }

        .feature-card.large .feature-description {
            font-size: 1.05rem;
        }

        /* Animated Holographic Border */
        .holo-border {
            position: relative;
            z-index: 1;
        }

        .holo-border::before {
            content: '';
            position: absolute;
            inset: -2px;
            background: conic-gradient(
                from var(--border-angle),
                var(--accent-cyan) 0%,
                var(--accent-magenta) 25%,
                var(--accent-primary) 50%,
                var(--accent-magenta) 75%,
                var(--accent-cyan) 100%
            );
            border-radius: calc(var(--radius-xl) + 2px);
            z-index: -1;
            animation: borderRotate 4s linear infinite;
            opacity: 0.5;
            transition: opacity var(--transition-slow);
        }

        .holo-border::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(26, 26, 46, 0.95);
            border-radius: var(--radius-xl);
            z-index: -1;
        }

        .holo-border:hover::before {
            opacity: 1;
        }

        @keyframes borderRotate {
            0% { --border-angle: 0deg; }
            100% { --border-angle: 360deg; }
        }

        /* Cyberpunk Corner Brackets */
        .feature-card .corner-bracket {
            position: absolute;
            width: 20px;
            height: 20px;
            pointer-events: none;
            opacity: 0.6;
            transition: opacity var(--transition-medium);
        }

        .feature-card:hover .corner-bracket {
            opacity: 1;
        }

        .corner-bracket.top-left {
            top: 8px;
            left: 8px;
            border-top: 2px solid var(--accent-cyan);
            border-left: 2px solid var(--accent-cyan);
        }

        .corner-bracket.top-right {
            top: 8px;
            right: 8px;
            border-top: 2px solid var(--accent-cyan);
            border-right: 2px solid var(--accent-cyan);
        }

        .corner-bracket.bottom-left {
            bottom: 8px;
            left: 8px;
            border-bottom: 2px solid var(--accent-magenta);
            border-left: 2px solid var(--accent-magenta);
        }

        .corner-bracket.bottom-right {
            bottom: 8px;
            right: 8px;
            border-bottom: 2px solid var(--accent-magenta);
            border-right: 2px solid var(--accent-magenta);
        }

        .feature-card:hover {
            box-shadow:
                0 25px 50px rgba(0, 0, 0, 0.4),
                0 0 60px var(--accent-cyan-glow),
                inset 0 1px 0 rgba(0, 245, 255, 0.1);
        }

        .feature-card > *:not(.corner-bracket) {
            position: relative;
            z-index: 1;
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--accent-cyan), var(--accent-magenta));
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            box-shadow:
                0 4px 20px var(--accent-cyan-glow),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
            transition: all var(--transition-medium);
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1);
            box-shadow:
                0 8px 30px var(--accent-cyan-glow),
                0 0 40px var(--accent-magenta-glow);
            animation: iconPulse 1s ease-in-out infinite;
        }

        @keyframes iconPulse {
            0%, 100% { box-shadow: 0 8px 30px var(--accent-cyan-glow), 0 0 40px var(--accent-magenta-glow); }
            50% { box-shadow: 0 8px 40px var(--accent-magenta-glow), 0 0 60px var(--accent-cyan-glow); }
        }

        .feature-icon svg {
            width: 30px;
            height: 30px;
            color: #ffffff;
        }

        .feature-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--color-text-primary);
            margin-bottom: 12px;
            letter-spacing: 0.05em;
            transition: color var(--transition-fast);
        }

        .feature-card:hover .feature-title {
            color: var(--accent-cyan);
        }

        .feature-description {
            font-family: 'Rajdhani', sans-serif;
            font-size: 1rem;
            color: var(--color-text-secondary);
            line-height: 1.7;
        }

        /* Scroll Reveal Animation */
        .reveal {
            opacity: 0;
            transform: translateY(50px);
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
            padding: 140px 20px;
            background: linear-gradient(180deg, var(--color-bg-secondary) 0%, var(--color-bg-primary) 50%, var(--color-bg-secondary) 100%);
            position: relative;
            overflow: hidden;
        }

        /* Spotlight Effect */
        .spotlight {
            position: absolute;
            top: -200px;
            left: 50%;
            transform: translateX(-50%);
            width: 400px;
            height: 600px;
            background: linear-gradient(
                180deg,
                rgba(0, 245, 255, 0.2) 0%,
                rgba(0, 245, 255, 0.05) 50%,
                transparent 100%
            );
            clip-path: polygon(40% 0%, 60% 0%, 100% 100%, 0% 100%);
            animation: spotlightBeam 4s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes spotlightBeam {
            0%, 100% { opacity: 0.6; transform: translateX(-50%) scaleY(1); }
            50% { opacity: 1; transform: translateX(-50%) scaleY(1.1); }
        }

        /* CTA Particles */
        .cta-particles {
            position: absolute;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .cta-particles span {
            position: absolute;
            width: 6px;
            height: 6px;
            background: var(--accent-cyan);
            border-radius: 50%;
            bottom: 0;
            animation: particleRise 8s linear infinite;
            opacity: 0;
        }

        .cta-particles span:nth-child(1) { left: 10%; animation-delay: 0s; }
        .cta-particles span:nth-child(2) { left: 25%; animation-delay: 1.5s; background: var(--accent-magenta); }
        .cta-particles span:nth-child(3) { left: 40%; animation-delay: 3s; }
        .cta-particles span:nth-child(4) { left: 55%; animation-delay: 0.5s; background: var(--accent-magenta); }
        .cta-particles span:nth-child(5) { left: 70%; animation-delay: 2s; }
        .cta-particles span:nth-child(6) { left: 85%; animation-delay: 4s; background: var(--accent-magenta); }

        @keyframes particleRise {
            0% {
                transform: translateY(0) scale(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
                transform: translateY(-50px) scale(1);
            }
            90% {
                opacity: 0.8;
            }
            100% {
                transform: translateY(-400px) scale(0.5);
                opacity: 0;
            }
        }

        .cta::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 1000px;
            height: 1000px;
            background: radial-gradient(circle, rgba(0, 245, 255, 0.08) 0%, transparent 60%);
            pointer-events: none;
        }

        .cta-container {
            max-width: 750px;
            margin: 0 auto;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .cta-title {
            font-family: 'Orbitron', sans-serif;
            font-size: clamp(2.2rem, 5.5vw, 3rem);
            font-weight: 700;
            color: var(--color-text-primary);
            margin-bottom: 1.5rem;
            letter-spacing: 0.05em;
        }

        .cta-subtitle {
            font-family: 'Rajdhani', sans-serif;
            font-size: clamp(1.1rem, 2.2vw, 1.25rem);
            color: var(--color-text-secondary);
            margin-bottom: 3rem;
        }

        /* CTA Button with Pulsing Glow Ring */
        .cta-button-wrapper {
            position: relative;
            display: inline-block;
        }

        .cta-button-wrapper::before {
            content: '';
            position: absolute;
            inset: -15px;
            border: 2px solid var(--accent-cyan);
            border-radius: 30px;
            animation: glowRingPulse 2s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes glowRingPulse {
            0%, 100% {
                transform: scale(1);
                opacity: 0.5;
                border-color: var(--accent-cyan);
                box-shadow: 0 0 20px var(--accent-cyan-glow);
            }
            50% {
                transform: scale(1.05);
                opacity: 1;
                border-color: var(--accent-magenta);
                box-shadow: 0 0 40px var(--accent-magenta-glow);
            }
        }

        .cta-button {
            padding: 20px 56px;
            font-size: 1.2rem;
        }

        .cta-link {
            display: block;
            margin-top: 2rem;
            color: var(--color-text-secondary);
            font-family: 'Rajdhani', sans-serif;
            font-size: 1rem;
        }

        .cta-link a {
            color: var(--accent-cyan);
            text-decoration: none;
            font-weight: 600;
            transition: all var(--transition-fast);
        }

        .cta-link a:hover {
            color: var(--accent-magenta);
            text-shadow: 0 0 10px var(--accent-magenta);
        }

        /* =============================================================================
           Footer
           ============================================================================= */
        .footer {
            padding: 40px 20px;
            background: var(--color-bg-primary);
            position: relative;
            text-align: center;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent-cyan), var(--accent-magenta), var(--accent-cyan), transparent);
            box-shadow: 0 0 15px var(--accent-cyan);
        }

        .footer-brand {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.75rem;
            text-shadow: 0 0 30px var(--glow-primary);
        }

        .footer-text {
            color: var(--color-text-muted);
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.95rem;
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

            .glitch::before,
            .glitch::after,
            .holographic::before,
            .cursor-glow,
            .particles,
            .floating-shapes {
                display: none;
            }
        }

        /* =============================================================================
           Focus Visible for Keyboard Navigation
           ============================================================================= */
        .btn:focus-visible {
            outline: 3px solid var(--accent-cyan);
            outline-offset: 3px;
        }

        a:focus-visible {
            outline: 2px solid var(--accent-cyan);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    {{-- Global Overlays --}}
    <div class="noise-overlay" aria-hidden="true"></div>
    <div class="scanlines" aria-hidden="true"></div>
    <div class="vignette" aria-hidden="true"></div>
    <div class="cursor-glow" aria-hidden="true"></div>

    {{-- Hero Section --}}
    <section class="hero" id="hero">
        {{-- Perspective Grid --}}
        <div class="hero-grid" aria-hidden="true"></div>

        {{-- Background Mesh & Orbs --}}
        <div class="hero-background" aria-hidden="true">
            <div class="orb orb-1"></div>
            <div class="orb orb-2"></div>
            <div class="orb orb-3"></div>
            <div class="orb orb-4"></div>
        </div>

        {{-- Floating Geometric Shapes --}}
        <div class="floating-shapes" aria-hidden="true">
            <div class="shape hexagon" data-parallax="0.03"></div>
            <div class="shape triangle" data-parallax="0.05"></div>
            <div class="shape diamond" data-parallax="0.04"></div>
            <div class="shape circle-outline" data-parallax="0.02"></div>
            <div class="shape cross" data-parallax="0.06"></div>
        </div>

        {{-- Particle Field --}}
        <div class="particles" aria-hidden="true">
            <span></span><span></span><span></span><span></span><span></span>
            <span></span><span></span><span></span><span></span><span></span>
            <span></span><span></span><span></span><span></span><span></span>
            <span></span><span></span><span></span><span></span><span></span>
        </div>

        <div class="hero-content">
            <h1 class="logo glitch holographic" data-text="GLYPH">GLYPH</h1>
            <p class="tagline" id="tagline"></p>
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

            <div class="bento-grid">
                {{-- Feature 1: Steam Integration (LARGE) --}}
                <article class="feature-card large holo-border tilt-card reveal">
                    <div class="corner-bracket top-left"></div>
                    <div class="corner-bracket top-right"></div>
                    <div class="corner-bracket bottom-left"></div>
                    <div class="corner-bracket bottom-right"></div>
                    <div class="feature-icon">
                        <svg fill="currentColor" viewBox="0 0 256 259" aria-hidden="true">
                            <path d="M127.779 0C60.42 0 5.24 52.412 0 119.014l68.724 28.674a35.812 35.812 0 0 1 20.426-6.366c.682 0 1.356.019 2.02.056l30.566-44.71v-.626c0-26.903 21.69-48.796 48.353-48.796 26.662 0 48.352 21.893 48.352 48.796 0 26.902-21.69 48.804-48.352 48.804-.37 0-.73-.009-1.098-.018l-43.593 31.377c.028.582.046 1.163.046 1.735 0 20.204-16.283 36.636-36.294 36.636-17.566 0-32.263-12.658-35.584-29.412L4.41 164.654c15.223 54.313 64.673 94.132 123.369 94.132 70.818 0 128.221-57.938 128.221-129.393C256 57.93 198.597 0 127.779 0zM80.352 196.332l-15.749-6.568c2.787 5.867 7.621 10.775 14.033 13.47 13.857 5.83 29.836-.803 35.612-14.799a27.555 27.555 0 0 0 .046-21.035c-2.768-6.79-7.999-12.086-14.706-14.909-6.67-2.795-13.811-2.694-20.085-.304l16.275 6.79c10.222 4.3 15.056 16.145 10.794 26.461-4.253 10.314-15.998 15.195-26.22 10.894zm121.957-100.29c0-17.925-14.457-32.52-32.217-32.52-17.769 0-32.226 14.595-32.226 32.52 0 17.926 14.457 32.512 32.226 32.512 17.76 0 32.217-14.586 32.217-32.512zm-56.37-.055c0-13.488 10.84-24.42 24.2-24.42 13.368 0 24.208 10.932 24.208 24.42 0 13.488-10.84 24.421-24.209 24.421-13.359 0-24.2-10.933-24.2-24.42z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title" data-scramble>Deep Steam Integration</h3>
                    <p class="feature-description">Sync your profile, games, and achievements. Show off your gaming stats and current activity in real-time to your community. Your Steam library becomes your identity.</p>
                </article>

                {{-- Feature 2: Smart Matchmaking (LARGE) --}}
                <article class="feature-card large holo-border tilt-card reveal">
                    <div class="corner-bracket top-left"></div>
                    <div class="corner-bracket top-right"></div>
                    <div class="corner-bracket bottom-left"></div>
                    <div class="corner-bracket bottom-right"></div>
                    <div class="feature-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title" data-scramble>Smart Team Matching</h3>
                    <p class="feature-description">Our research-backed algorithm finds your perfect teammates based on skill level, play style, schedule, and region. Six weighted criteria ensure optimal compatibility every time.</p>
                </article>

                {{-- Feature 3: Role-Based Teams --}}
                <article class="feature-card holo-border tilt-card reveal">
                    <div class="corner-bracket top-left"></div>
                    <div class="corner-bracket top-right"></div>
                    <div class="corner-bracket bottom-left"></div>
                    <div class="corner-bracket bottom-right"></div>
                    <div class="feature-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title" data-scramble>Role-Based Teams</h3>
                    <p class="feature-description">Define your gaming roles - IGL, Support, Entry Fragger. Find teams that need exactly what you bring.</p>
                </article>

                {{-- Feature 4: Game Lobbies --}}
                <article class="feature-card holo-border tilt-card reveal">
                    <div class="corner-bracket top-left"></div>
                    <div class="corner-bracket top-right"></div>
                    <div class="corner-bracket bottom-left"></div>
                    <div class="corner-bracket bottom-right"></div>
                    <div class="feature-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title" data-scramble>Universal Lobbies</h3>
                    <p class="feature-description">Create lobbies for any game. Steam links, server addresses, lobby codes - all in one place.</p>
                </article>

                {{-- Feature 5: Community Goals --}}
                <article class="feature-card holo-border tilt-card reveal">
                    <div class="corner-bracket top-left"></div>
                    <div class="corner-bracket top-right"></div>
                    <div class="corner-bracket bottom-left"></div>
                    <div class="corner-bracket bottom-right"></div>
                    <div class="feature-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title" data-scramble>Community Goals</h3>
                    <p class="feature-description">Set server-wide goals, track progress together, and climb the leaderboards.</p>
                </article>

                {{-- Feature 6: Voice Chat --}}
                <article class="feature-card holo-border tilt-card reveal">
                    <div class="corner-bracket top-left"></div>
                    <div class="corner-bracket top-right"></div>
                    <div class="corner-bracket bottom-left"></div>
                    <div class="corner-bracket bottom-right"></div>
                    <div class="feature-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title" data-scramble>Crystal Voice</h3>
                    <p class="feature-description">High-quality WebRTC voice channels. No third-party apps needed - jump in seamlessly.</p>
                </article>
            </div>
        </div>
    </section>

    {{-- CTA Section --}}
    <section class="cta reveal" id="cta">
        {{-- Spotlight --}}
        <div class="spotlight" aria-hidden="true"></div>

        {{-- Rising Particles --}}
        <div class="cta-particles" aria-hidden="true">
            <span></span><span></span><span></span>
            <span></span><span></span><span></span>
        </div>

        <div class="cta-container">
            <h2 class="cta-title">Ready to Level Up Your Gaming?</h2>
            <p class="cta-subtitle">Join thousands of gamers building their dream teams on Glyph.</p>
            <div class="cta-button-wrapper">
                <a href="{{ route('register') }}" class="btn btn-primary cta-button">Get Started</a>
            </div>
            <p class="cta-link">Already have an account? <a href="{{ route('login') }}">Sign In</a></p>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="footer">
        <p class="footer-brand">GLYPH</p>
        <p class="footer-text">&copy; {{ date('Y') }} Glyph. All rights reserved.</p>
    </footer>

    {{-- Enhanced JavaScript: Parallax, Tilt, Typing, Text Scramble, Cursor Glow --}}
    <script>
        (function() {
            'use strict';

            // Check for reduced motion preference
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (prefersReducedMotion) {
                document.querySelectorAll('.reveal').forEach(function(el) {
                    el.classList.add('visible');
                });
                // Show tagline without animation
                document.getElementById('tagline').textContent = 'Unite. Play. Dominate.';
                return;
            }

            // =========================================================================
            // Typing Effect for Tagline
            // =========================================================================
            const taglineText = 'Unite. Play. Dominate.';
            const taglineEl = document.getElementById('tagline');
            let charIndex = 0;

            function typeWriter() {
                if (charIndex < taglineText.length) {
                    taglineEl.innerHTML = taglineText.substring(0, charIndex + 1) + '<span class="cursor"></span>';
                    charIndex++;
                    setTimeout(typeWriter, 80);
                } else {
                    // Keep cursor blinking after typing complete
                    setTimeout(() => {
                        taglineEl.innerHTML = taglineText + '<span class="cursor"></span>';
                    }, 100);
                }
            }

            // Start typing after hero entrance animation
            setTimeout(typeWriter, 800);

            // =========================================================================
            // Cursor Glow Trail
            // =========================================================================
            const cursorGlow = document.querySelector('.cursor-glow');
            let mouseX = 0, mouseY = 0;
            let glowX = 0, glowY = 0;

            document.addEventListener('mousemove', (e) => {
                mouseX = e.clientX;
                mouseY = e.clientY;
            });

            function animateCursorGlow() {
                glowX += (mouseX - glowX) * 0.1;
                glowY += (mouseY - glowY) * 0.1;
                cursorGlow.style.left = glowX + 'px';
                cursorGlow.style.top = glowY + 'px';
                requestAnimationFrame(animateCursorGlow);
            }
            animateCursorGlow();

            // =========================================================================
            // Mouse Parallax on Floating Shapes
            // =========================================================================
            const shapes = document.querySelectorAll('.shape[data-parallax]');
            const hero = document.querySelector('.hero');

            document.addEventListener('mousemove', (e) => {
                const centerX = window.innerWidth / 2;
                const centerY = window.innerHeight / 2;
                const deltaX = (e.clientX - centerX) / centerX;
                const deltaY = (e.clientY - centerY) / centerY;

                shapes.forEach(shape => {
                    const factor = parseFloat(shape.dataset.parallax) * 100;
                    const x = deltaX * factor;
                    const y = deltaY * factor;
                    shape.style.transform = `translate(${x}px, ${y}px)`;
                });
            });

            // =========================================================================
            // 3D Tilt Effect on Feature Cards
            // =========================================================================
            const tiltCards = document.querySelectorAll('.tilt-card');

            tiltCards.forEach(card => {
                card.addEventListener('mousemove', (e) => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;

                    const rotateX = (y - centerY) / centerY * -8;
                    const rotateY = (x - centerX) / centerX * 8;

                    card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;
                });

                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1)';
                });
            });

            // =========================================================================
            // Text Scramble Effect on Feature Titles
            // =========================================================================
            class TextScramble {
                constructor(el) {
                    this.el = el;
                    this.chars = '!<>-_\\/[]{}—=+*^?#________';
                    this.originalText = el.textContent;
                }

                scramble() {
                    const length = this.originalText.length;
                    let iteration = 0;
                    const maxIterations = length * 3;

                    const interval = setInterval(() => {
                        this.el.textContent = this.originalText
                            .split('')
                            .map((char, index) => {
                                if (index < iteration / 3) {
                                    return this.originalText[index];
                                }
                                return this.chars[Math.floor(Math.random() * this.chars.length)];
                            })
                            .join('');

                        if (iteration >= maxIterations) {
                            clearInterval(interval);
                            this.el.textContent = this.originalText;
                        }
                        iteration++;
                    }, 30);
                }
            }

            const scrambleElements = document.querySelectorAll('[data-scramble]');
            scrambleElements.forEach(el => {
                const scrambler = new TextScramble(el);
                el.parentElement.addEventListener('mouseenter', () => {
                    scrambler.scramble();
                });
            });

            // =========================================================================
            // Intersection Observer for Scroll Reveal
            // =========================================================================
            const observerOptions = {
                root: null,
                rootMargin: '0px 0px -80px 0px',
                threshold: 0.1
            };

            const revealOnScroll = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.reveal').forEach(el => {
                revealOnScroll.observe(el);
            });

            // =========================================================================
            // Smooth Scroll for Anchor Links
            // =========================================================================
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    const target = document.querySelector(targetId);
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // =========================================================================
            // Magnetic Button Effect
            // =========================================================================
            const buttons = document.querySelectorAll('.btn');

            buttons.forEach(btn => {
                btn.addEventListener('mousemove', (e) => {
                    const rect = btn.getBoundingClientRect();
                    const x = e.clientX - rect.left - rect.width / 2;
                    const y = e.clientY - rect.top - rect.height / 2;
                    btn.style.transform = `translate(${x * 0.2}px, ${y * 0.2}px)`;
                });

                btn.addEventListener('mouseleave', () => {
                    btn.style.transform = 'translate(0, 0)';
                });
            });
        })();
    </script>
</body>
</html>
