{{--
    Lobby Join Button Component

    A reusable component that displays join buttons for active game lobbies.
    Supports multiple display variants and sizes with real-time updates.

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
    // Get active lobbies from the user relationship
    $lobbies = $user->gameLobbies ?? collect();
    $activeLobbies = $lobbies->filter(fn($lobby) => $lobby->isActive());

    // Prepare lobby data for JavaScript
    $lobbyData = $activeLobbies->map(function($lobby) {
        // Use fallback for game icon (no game_icon_url in user_gaming_preferences table)
        $gameIcon = asset('images/default-game.png');

        return [
            'id' => $lobby->id,
            'game_name' => $lobby->gamingPreference->game_name ?? 'Unknown Game',
            'game_icon' => $gameIcon,
            'join_link' => $lobby->generateJoinLink(),
            'join_method' => $lobby->join_method,
            'display_format' => $lobby->getDisplayFormat(),
            'time_remaining_minutes' => $lobby->timeRemaining(),
            'expires_at' => $lobby->expires_at?->toIso8601String(),
            'is_expiring_soon' => $lobby->timeRemaining() && $lobby->timeRemaining() < 5,
            'is_expired' => false,
        ];
    });
@endphp

@if($activeLobbies->isNotEmpty())
    <div
        class="lobby-join-wrapper {{ $class }}"
        x-data="lobbyJoinButton({
            userId: {{ $user->id }},
            initialLobbies: {{ $lobbyData->toJson() }}
        })"
        x-init="init()"
    >
        @if($variant === 'full')
            {{-- Full button display --}}
            <div class="flex flex-wrap gap-2">
                <template x-for="lobby in lobbies" :key="lobby.id">
                    <button
                        type="button"
                        @click="joinLobby(lobby)"
                        class="btn-join-lobby
                               flex items-center gap-2
                               bg-green-600 hover:bg-green-700
                               text-white rounded-lg
                               transition-all duration-200
                               shadow-sm hover:shadow-md
                               disabled:opacity-50 disabled:cursor-not-allowed
                               focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:ring-offset-gray-800"
                        :class="{
                            'px-3 py-1.5 text-sm btn-small': '{{ $size }}' === 'small',
                            'px-4 py-2 text-base btn-medium': '{{ $size }}' === 'medium',
                            'px-5 py-3 text-lg btn-large': '{{ $size }}' === 'large',
                            'animate-pulse-glow': lobby.is_expiring_soon
                        }"
                        :disabled="lobby.is_expired"
                        :title="lobby.is_expired ? 'Lobby has expired' : 'Click to join ' + lobby.game_name"
                        :aria-label="'Join ' + lobby.game_name + (lobby.time_remaining_minutes ? ' (expires in ' + lobby.time_remaining_minutes + ' minutes)' : '')"
                    >
                        {{-- Game Icon --}}
                        <template x-if="{{ $showGameIcon ? 'true' : 'false' }}">
                            <img
                                :src="lobby.game_icon || '{{ asset('images/default-game.png') }}'"
                                :alt="lobby.game_name"
                                class="rounded object-cover"
                                :class="{
                                    'w-4 h-4': '{{ $size }}' === 'small',
                                    'w-5 h-5': '{{ $size }}' === 'medium',
                                    'w-6 h-6': '{{ $size }}' === 'large'
                                }"
                                loading="lazy"
                            >
                        </template>

                        {{-- Button Text --}}
                        <span class="font-medium whitespace-nowrap" x-text="'Join ' + lobby.game_name"></span>

                        {{-- Timer --}}
                        <template x-if="{{ $showTimer ? 'true' : 'false' }} && lobby.time_remaining_minutes">
                            <span
                                class="text-xs opacity-80 font-mono"
                                x-text="formatTimeRemaining(lobby.time_remaining_minutes)"
                            ></span>
                        </template>

                        {{-- Join Method Icon --}}
                        <template x-if="lobby.join_method === 'steam_lobby' || lobby.join_method === 'steam_connect'">
                            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
                            </svg>
                        </template>
                    </button>
                </template>
            </div>

        @elseif($variant === 'icon')
            {{-- Icon-only display with tooltip --}}
            <div
                class="relative cursor-pointer group"
                x-data="{ showTooltip: false }"
                @mouseenter="showTooltip = true"
                @mouseleave="showTooltip = false"
                @click="showTooltip = !showTooltip"
            >
                <div class="lobby-icon relative inline-block">
                    <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                    {{-- Animated indicator --}}
                    <span class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full animate-ping"></span>
                    <span class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full"></span>
                </div>

                {{-- Tooltip --}}
                <div
                    x-show="showTooltip"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-1"
                    class="absolute z-50 bg-gray-900 text-white text-sm rounded-lg p-3 shadow-xl min-w-[200px] -left-2 top-full mt-2"
                    @click.away="showTooltip = false"
                    style="display: none;"
                >
                    <template x-for="lobby in lobbies" :key="lobby.id">
                        <div class="flex items-center gap-2 mb-2 last:mb-0">
                            <img :src="lobby.game_icon" :alt="lobby.game_name" class="w-6 h-6 rounded flex-shrink-0">
                            <div class="flex-1 min-w-0">
                                <div class="font-medium truncate" x-text="lobby.game_name"></div>
                                <div class="text-xs opacity-75 truncate" x-text="lobby.display_format"></div>
                            </div>
                            <button
                                @click.stop="joinLobby(lobby)"
                                class="px-2 py-1 bg-green-600 hover:bg-green-700 rounded text-xs font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-green-500"
                                :disabled="lobby.is_expired"
                                :aria-label="'Join ' + lobby.game_name"
                            >
                                Join
                            </button>
                        </div>
                    </template>
                </div>
            </div>

        @elseif($variant === 'badge')
            {{-- Badge display --}}
            <div class="flex flex-wrap gap-1.5">
                <template x-for="lobby in lobbies" :key="lobby.id">
                    <button
                        @click="joinLobby(lobby)"
                        class="inline-flex items-center gap-1.5 px-2 py-1
                               bg-green-100 dark:bg-green-900/30
                               text-green-800 dark:text-green-300
                               text-xs font-medium
                               rounded-full cursor-pointer
                               hover:bg-green-200 dark:hover:bg-green-900/50
                               transition-colors
                               focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:ring-offset-gray-800
                               disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="lobby.is_expired"
                        :title="'Click to join ' + lobby.game_name"
                        :aria-label="'Join ' + lobby.game_name"
                    >
                        <img :src="lobby.game_icon" :alt="lobby.game_name" class="w-4 h-4 rounded-full">
                        <span class="truncate max-w-[100px]" x-text="lobby.game_name"></span>
                        <template x-if="lobby.time_remaining_minutes">
                            <span class="opacity-75" x-text="'• ' + lobby.time_remaining_minutes + 'm'"></span>
                        </template>
                    </button>
                </template>
            </div>
        @endif
    </div>
@endif
