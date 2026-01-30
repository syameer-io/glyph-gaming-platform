@props([
    'game' => [],
    'selected' => false
])

@php
    $gameId = $game['game_id'] ?? 0;
    $gameName = $game['game_name'] ?? 'Unknown Game';
    $isOwned = $game['is_owned'] ?? false;
    $playtime = $game['playtime'] ?? null;
    $bannerUrl = "https://cdn.cloudflare.steamstatic.com/steam/apps/{$gameId}/header.jpg";
@endphp

<div
    class="lobby-game-card"
    :class="{ 'selected': selectedGame == '{{ $gameId }}' }"
    @click="selectGame('{{ $gameId }}', '{{ addslashes($gameName) }}', '{{ $bannerUrl }}')"
    tabindex="0"
    role="button"
    aria-label="Select {{ $gameName }}"
    @keydown.enter="selectGame('{{ $gameId }}', '{{ addslashes($gameName) }}', '{{ $bannerUrl }}')"
    @keydown.space.prevent="selectGame('{{ $gameId }}', '{{ addslashes($gameName) }}', '{{ $bannerUrl }}')"
>
    {{-- Game Banner Image --}}
    <img
        src="{{ $bannerUrl }}"
        alt="{{ $gameName }}"
        loading="lazy"
        onerror="this.src='https://via.placeholder.com/460x215/18181b/71717a?text={{ urlencode($gameName) }}'"
    >

    {{-- Owned Badge --}}
    @if($isOwned)
        <div class="owned-badge">
            <svg width="10" height="10" fill="currentColor" viewBox="0 0 20 20" style="margin-right: 4px;">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
            OWNED
            @if($playtime)
                <span style="margin-left: 4px; opacity: 0.9;">{{ $playtime }}h</span>
            @endif
        </div>
    @endif

    {{-- Selection Checkmark --}}
    <div class="selection-checkmark" x-show="selectedGame == '{{ $gameId }}'" x-cloak>
        <svg width="24" height="24" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
    </div>

    {{-- Game Name Overlay --}}
    <div class="lobby-game-card-overlay">
        <span class="lobby-game-card-name">{{ $gameName }}</span>
    </div>
</div>
