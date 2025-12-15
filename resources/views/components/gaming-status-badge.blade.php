@props([
    'user',
    'variant' => 'full',        // 'full', 'compact', 'inline'
    'size' => 'md',             // 'sm', 'md', 'lg'
    'showDetails' => true,      // Show server/map details
    'showIndicator' => true,    // Show pulsing indicator
    'class' => '',
])

@php
    $currentGame = $user->profile->current_game ?? null;

    if (!$currentGame) {
        return;
    }

    $gameName = $currentGame['name'] ?? 'Unknown Game';
    $gameAppId = $currentGame['appid'] ?? null;
    $serverName = $currentGame['server_name'] ?? null;
    $mapName = $currentGame['map'] ?? null;

    // Steam CDN URLs for game artwork
    $capsuleUrl = $gameAppId
        ? "https://cdn.cloudflare.steamstatic.com/steam/apps/{$gameAppId}/capsule_184x69.jpg"
        : null;

    // Determine icon size based on variant
    $iconSize = match($variant) {
        'full' => 'lg',
        'compact' => 'md',
        'inline' => 'sm',
        default => 'md',
    };

    // Determine indicator size
    $indicatorSize = $variant === 'full' ? '' : 'gaming-status-indicator--sm';
@endphp

@if($currentGame)
<div
    class="gaming-status-badge gaming-status-badge--{{ $variant }} {{ $class }}"
    role="status"
    aria-label="Currently playing {{ $gameName }}"
    data-gaming-status
    data-user-id="{{ $user->id }}"
    @if($capsuleUrl && $variant === 'full')
    style="--game-capsule-url: url('{{ $capsuleUrl }}')"
    @endif
>
    {{-- Game Icon --}}
    @if($gameAppId)
        <img
            src="{{ $capsuleUrl }}"
            alt="{{ $gameName }}"
            class="gaming-status-icon gaming-status-icon--{{ $iconSize }}"
            loading="lazy"
            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
        >
        <div class="gaming-status-icon-fallback gaming-status-icon-fallback--{{ $iconSize }}" style="display: none;">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
    @else
        {{-- Fallback icon when no appid --}}
        <div class="gaming-status-icon-fallback gaming-status-icon-fallback--{{ $iconSize }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
    @endif

    {{-- Content --}}
    <div class="gaming-status-content">
        @if($variant === 'full')
            <div class="gaming-status-label">Playing</div>
            <div class="gaming-status-title">{{ $gameName }}</div>
            @if($showDetails && ($serverName || $mapName))
                <div class="gaming-status-details">
                    @if($serverName)
                        <span>{{ $serverName }}</span>
                    @endif
                    @if($serverName && $mapName)
                        <span> &bull; </span>
                    @endif
                    @if($mapName)
                        <span>{{ $mapName }}</span>
                    @endif
                </div>
            @endif
        @else
            <div class="gaming-status-title">Playing {{ $gameName }}</div>
        @endif
    </div>

    {{-- Live Indicator --}}
    @if($showIndicator)
        <div class="gaming-status-indicator {{ $indicatorSize }}" title="Live"></div>
    @endif
</div>
@endif
