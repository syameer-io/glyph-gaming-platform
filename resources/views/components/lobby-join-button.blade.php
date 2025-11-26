{{--
    Lobby Join Button Component

    A reusable component that displays join buttons for active game lobbies.
    Professional badge design with game thumbnails, timers, and hover effects.

    @param User $user - The user whose lobbies to display
    @param string $size - Button size: 'small', 'medium', 'large' (default: 'medium')
    @param string $variant - Display variant: 'full', 'icon', 'badge' (default: 'full')
    @param bool $showTimer - Whether to show time remaining (default: true)
    @param bool $showGameIcon - Whether to show game icon (default: true)
    @param string $class - Additional CSS classes
--}}

@props([
    'user',
    'size' => 'medium',
    'variant' => 'full',
    'showTimer' => true,
    'showGameIcon' => true,
    'class' => '',
])

@php
    // Prefer activeLobbies if eager-loaded, otherwise filter gameLobbies
    $activeLobbies = $user->relationLoaded('activeLobbies')
        ? ($user->activeLobbies ?? collect())
        : ($user->gameLobbies ?? collect())->filter(fn($lobby) => $lobby->isActive());
@endphp

@if($activeLobbies->isNotEmpty())
    {{-- Scoped styles for lobby badges --}}
    <style>
        .lobby-badge-container {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }
        .lobby-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            background: linear-gradient(135deg, rgba(35, 165, 89, 0.15) 0%, rgba(16, 185, 129, 0.1) 100%);
            border: 1px solid rgba(35, 165, 89, 0.3);
            border-radius: 6px;
            transition: all 0.2s ease;
            cursor: default;
        }
        .lobby-badge:hover {
            background: linear-gradient(135deg, rgba(35, 165, 89, 0.25) 0%, rgba(16, 185, 129, 0.2) 100%);
            border-color: rgba(35, 165, 89, 0.5);
        }
        .lobby-badge:hover .lobby-join-btn {
            transform: scale(1.05);
        }
        .lobby-game-icon {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            object-fit: cover;
            flex-shrink: 0;
        }
        .lobby-game-info {
            display: flex;
            flex-direction: column;
            gap: 1px;
            min-width: 0;
        }
        .lobby-game-name {
            font-size: 12px;
            font-weight: 600;
            color: #efeff1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100px;
        }
        .lobby-timer {
            font-size: 10px;
            font-weight: 500;
        }
        .lobby-timer-active {
            color: #23a559;
        }
        .lobby-timer-expiring {
            color: #ef4444;
            animation: pulse 2s infinite;
        }
        .lobby-join-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 10px;
            background-color: #23a559;
            color: white;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
            flex-shrink: 0;
        }
        .lobby-join-btn:hover {
            background-color: #1a8a47;
        }
        .lobby-more-indicator {
            font-size: 11px;
            color: #71717a;
            font-style: italic;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
    </style>

    <div class="lobby-badge-container {{ $class }}">
        @if($variant === 'full')
            {{-- Professional badge display (matches teams page design) --}}
            @foreach($activeLobbies->take(2) as $lobby)
                @php
                    $gameAppId = $lobby->game_id ?? 730;
                    $gameName = $lobby->getGameName();
                    $timeRemaining = $lobby->timeRemaining();
                    $joinLink = $lobby->generateJoinLink();
                    $isSteamJoin = in_array($lobby->join_method, ['steam_lobby', 'steam_connect']);
                    $isExpiringSoon = $timeRemaining && $timeRemaining < 5;
                @endphp
                <div class="lobby-badge">
                    {{-- Game Icon from Steam CDN --}}
                    @if($showGameIcon)
                        <img
                            src="https://cdn.cloudflare.steamstatic.com/steam/apps/{{ $gameAppId }}/capsule_184x69.jpg"
                            alt="{{ $gameName }}"
                            class="lobby-game-icon"
                            onerror="this.style.display='none'"
                        >
                    @endif

                    {{-- Game Name & Timer --}}
                    <div class="lobby-game-info">
                        <span class="lobby-game-name">{{ $gameName }}</span>
                        @if($showTimer)
                            @if($timeRemaining)
                                <span class="lobby-timer {{ $isExpiringSoon ? 'lobby-timer-expiring' : 'lobby-timer-active' }}">
                                    {{ $timeRemaining < 60 ? $timeRemaining . 'm left' : floor($timeRemaining/60) . 'h ' . ($timeRemaining % 60) . 'm' }}
                                </span>
                            @else
                                <span class="lobby-timer lobby-timer-active">Active</span>
                            @endif
                        @endif
                    </div>

                    {{-- Join Button --}}
                    @if($isSteamJoin && $joinLink)
                        <a href="{{ $joinLink }}"
                           class="lobby-join-btn"
                           title="Join {{ $gameName }} lobby via Steam"
                        >
                            Join
                        </a>
                    @endif
                </div>
            @endforeach

            {{-- More indicator for additional lobbies --}}
            @if($activeLobbies->count() > 2)
                <span class="lobby-more-indicator">+{{ $activeLobbies->count() - 2 }} more</span>
            @endif

        @elseif($variant === 'icon')
            {{-- Icon-only display with CSS hover tooltip --}}
            @php
                $firstLobby = $activeLobbies->first();
                $gameAppId = $firstLobby->game_id ?? 730;
                $gameName = $firstLobby->getGameName();
                $timeRemaining = $firstLobby->timeRemaining();
                $joinLink = $firstLobby->generateJoinLink();
                $isSteamJoin = in_array($firstLobby->join_method, ['steam_lobby', 'steam_connect']);
            @endphp
            <div class="lobby-icon-wrapper">
                {{-- Pulsing green indicator --}}
                <div class="lobby-pulse-indicator">
                    <span class="lobby-pulse-ring"></span>
                    <span class="lobby-pulse-dot"></span>
                </div>

                {{-- Tooltip on hover --}}
                <div class="lobby-icon-tooltip">
                    <div class="lobby-icon-tooltip-arrow"></div>
                    <div style="font-size: 10px; color: #23a559; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                        Active Lobby
                    </div>
                    @foreach($activeLobbies->take(3) as $lobby)
                        @php
                            $lGameAppId = $lobby->game_id ?? 730;
                            $lGameName = $lobby->getGameName();
                            $lTimeRemaining = $lobby->timeRemaining();
                            $lJoinLink = $lobby->generateJoinLink();
                            $lIsSteamJoin = in_array($lobby->join_method, ['steam_lobby', 'steam_connect']);
                        @endphp
                        <div class="lobby-tooltip-item">
                            <img
                                src="https://cdn.cloudflare.steamstatic.com/steam/apps/{{ $lGameAppId }}/capsule_184x69.jpg"
                                alt="{{ $lGameName }}"
                                class="lobby-game-icon"
                                onerror="this.style.display='none'"
                            >
                            <div class="lobby-game-info">
                                <span class="lobby-game-name">{{ $lGameName }}</span>
                                @if($lTimeRemaining)
                                    <span class="lobby-timer lobby-timer-active">{{ $lTimeRemaining }}m left</span>
                                @else
                                    <span class="lobby-timer lobby-timer-active">Active</span>
                                @endif
                            </div>
                            @if($lIsSteamJoin && $lJoinLink)
                                <a href="{{ $lJoinLink }}" class="lobby-join-btn" title="Join {{ $lGameName }}">Join</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

        @elseif($variant === 'badge')
            {{-- Compact badge display (same professional design, smaller) --}}
            @foreach($activeLobbies->take(2) as $lobby)
                @php
                    $gameAppId = $lobby->game_id ?? 730;
                    $gameName = $lobby->getGameName();
                    $timeRemaining = $lobby->timeRemaining();
                    $joinLink = $lobby->generateJoinLink();
                    $isSteamJoin = in_array($lobby->join_method, ['steam_lobby', 'steam_connect']);
                    $isExpiringSoon = $timeRemaining && $timeRemaining < 5;
                @endphp
                <div class="lobby-badge lobby-badge-compact">
                    @if($showGameIcon)
                        <img
                            src="https://cdn.cloudflare.steamstatic.com/steam/apps/{{ $gameAppId }}/capsule_184x69.jpg"
                            alt="{{ $gameName }}"
                            class="lobby-game-icon-sm"
                            onerror="this.style.display='none'"
                        >
                    @endif
                    <span class="lobby-game-name-compact">{{ $gameName }}</span>
                    @if($showTimer && $timeRemaining)
                        <span class="lobby-timer-compact {{ $isExpiringSoon ? 'lobby-timer-expiring' : 'lobby-timer-active' }}">{{ $timeRemaining }}m</span>
                    @endif
                    @if($isSteamJoin && $joinLink)
                        <a href="{{ $joinLink }}" class="lobby-join-btn-compact" title="Join {{ $gameName }}">Join</a>
                    @endif
                </div>
            @endforeach
            @if($activeLobbies->count() > 2)
                <span class="lobby-more-indicator">+{{ $activeLobbies->count() - 2 }}</span>
            @endif
        @endif
    </div>

    {{-- Additional styles for icon and badge variants --}}
    <style>
        /* Icon variant styles */
        .lobby-icon-wrapper {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }
        .lobby-pulse-indicator {
            position: relative;
            width: 24px;
            height: 24px;
        }
        .lobby-pulse-ring {
            position: absolute;
            inset: 0;
            background-color: #23a559;
            border-radius: 50%;
            animation: ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;
            opacity: 0.75;
        }
        .lobby-pulse-dot {
            position: absolute;
            inset: 4px;
            background-color: #23a559;
            border-radius: 50%;
        }
        @keyframes ping {
            75%, 100% {
                transform: scale(2);
                opacity: 0;
            }
        }
        .lobby-icon-tooltip {
            position: absolute;
            z-index: 50;
            right: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-right: 12px;
            background-color: #1e1e22;
            border: 1px solid rgba(35, 165, 89, 0.3);
            border-radius: 8px;
            padding: 12px;
            min-width: 220px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease, visibility 0.2s ease;
        }
        .lobby-icon-wrapper:hover .lobby-icon-tooltip {
            opacity: 1;
            visibility: visible;
        }
        .lobby-icon-tooltip-arrow {
            position: absolute;
            right: -8px;
            top: 50%;
            transform: translateY(-50%);
            border: 8px solid transparent;
            border-left-color: #1e1e22;
        }
        .lobby-tooltip-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .lobby-tooltip-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .lobby-tooltip-item:first-of-type {
            padding-top: 0;
        }

        /* Compact badge variant styles */
        .lobby-badge-compact {
            padding: 4px 8px;
            gap: 6px;
        }
        .lobby-game-icon-sm {
            width: 18px;
            height: 18px;
            border-radius: 3px;
            object-fit: cover;
            flex-shrink: 0;
        }
        .lobby-game-name-compact {
            font-size: 11px;
            font-weight: 600;
            color: #efeff1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 70px;
        }
        .lobby-timer-compact {
            font-size: 9px;
            font-weight: 500;
        }
        .lobby-join-btn-compact {
            padding: 2px 6px;
            background-color: #23a559;
            color: white;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 0.15s ease;
            flex-shrink: 0;
        }
        .lobby-join-btn-compact:hover {
            background-color: #1a8a47;
        }
    </style>
@endif
