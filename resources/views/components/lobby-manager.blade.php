@props(['user', 'isOwnProfile' => false])

<div
    x-data="lobbyManager({{ $user->id }}, {{ json_encode($isOwnProfile) }})"
    x-init="init()"
    class="lobby-manager"
>
    @if($isOwnProfile)
        {{-- Lobby Creation Section (Own Profile Only) --}}
        <div class="card" style="margin-bottom: 24px;">
            <h3 class="card-header">Multi-Game Lobbies</h3>

            {{-- Error Display --}}
            <div x-show="error" x-cloak style="background-color: #dc2626; color: white; padding: 12px; border-radius: 6px; margin-bottom: 16px; font-size: 14px;">
                <span x-text="error"></span>
            </div>

            {{-- Game Selector --}}
            <div class="form-group" style="margin-bottom: 16px;">
                <label for="game-selector" class="form-label" style="font-size: 14px; color: #b3b3b5; margin-bottom: 8px; display: block;">
                    Select Game
                </label>
                <select
                    id="game-selector"
                    x-model="selectedGame"
                    @change="loadGameJoinMethods()"
                    :disabled="loading"
                    class="form-control"
                    style="width: 100%; padding: 10px 12px; background-color: #0e0e10; border: 1px solid #3f3f46; border-radius: 6px; color: #efeff1; font-size: 14px; cursor: pointer;"
                >
                    <option value="">-- Choose a game --</option>
                    @foreach($user->gamingPreferences->sortByDesc('playtime_forever')->take(10) as $preference)
                        <option value="{{ $preference->game_appid }}">
                            {{ $preference->game_name }} ({{ round($preference->playtime_forever / 60, 1) }} hrs)
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Join Method Selector --}}
            <div x-show="selectedGame && availableJoinMethods.length > 0" x-cloak class="form-group" style="margin-bottom: 16px;">
                <label for="join-method-selector" class="form-label" style="font-size: 14px; color: #b3b3b5; margin-bottom: 8px; display: block;">
                    Join Method
                </label>
                <select
                    id="join-method-selector"
                    x-model="selectedJoinMethod"
                    @change="updateFormFields()"
                    :disabled="loading"
                    class="form-control"
                    style="width: 100%; padding: 10px 12px; background-color: #0e0e10; border: 1px solid #3f3f46; border-radius: 6px; color: #efeff1; font-size: 14px; cursor: pointer;"
                >
                    <option value="">-- Choose join method --</option>
                    <template x-for="method in availableJoinMethods" :key="method.join_method">
                        <option :value="method.join_method" x-text="method.display_name"></option>
                    </template>
                </select>
            </div>

            {{-- Warframe Warning (Steam version required) --}}
            <div x-show="selectedGame == 230410" x-cloak style="margin-bottom: 16px;">
                <x-lobby-warning
                    type="warning"
                    message="<strong>Steam version required.</strong> In-game invites are preferred by the Warframe community."
                />
            </div>

            {{-- Dynamic Form Fields --}}
            <div x-show="selectedJoinMethod" x-cloak>
                {{-- Steam Lobby Link --}}
                <div x-show="selectedJoinMethod === 'steam_lobby'" x-cloak class="form-group" style="margin-bottom: 16px;">
                    <label for="steam-lobby-link" class="form-label" style="font-size: 14px; color: #b3b3b5; margin-bottom: 8px; display: block;">
                        Steam Lobby Link
                    </label>
                    <input
                        id="steam-lobby-link"
                        type="text"
                        x-model="formData.steam_lobby_link"
                        placeholder="steam://joinlobby/..."
                        :disabled="loading"
                        class="form-control"
                        style="width: 100%; padding: 10px 12px; background-color: #0e0e10; border: 1px solid #3f3f46; border-radius: 6px; color: #efeff1; font-size: 14px;"
                    >
                    <p class="help-text" style="font-size: 12px; color: #71717a; margin-top: 6px;">
                        Get your lobby link from Steam overlay (Shift+Tab)
                    </p>
                </div>

                {{-- Steam Connect (Server Address) --}}
                <div x-show="selectedJoinMethod === 'steam_connect'" x-cloak>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label for="server-ip" class="form-label" style="font-size: 14px; color: #b3b3b5; margin-bottom: 8px; display: block;">
                            Server IP/Domain
                        </label>
                        <input
                            id="server-ip"
                            type="text"
                            x-model="formData.server_ip"
                            placeholder="192.168.1.1 or domain.com"
                            :disabled="loading"
                            class="form-control"
                            style="width: 100%; padding: 10px 12px; background-color: #0e0e10; border: 1px solid #3f3f46; border-radius: 6px; color: #efeff1; font-size: 14px;"
                        >
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label for="server-port" class="form-label" style="font-size: 14px; color: #b3b3b5; margin-bottom: 8px; display: block;">
                            Server Port
                        </label>
                        <input
                            id="server-port"
                            type="number"
                            x-model="formData.server_port"
                            placeholder="27015"
                            :disabled="loading"
                            min="1"
                            max="65535"
                            class="form-control"
                            style="width: 100%; padding: 10px 12px; background-color: #0e0e10; border: 1px solid #3f3f46; border-radius: 6px; color: #efeff1; font-size: 14px;"
                        >
                    </div>
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="server-password" class="form-label" style="font-size: 14px; color: #b3b3b5; margin-bottom: 8px; display: block;">
                            Server Password (Optional)
                        </label>
                        <input
                            id="server-password"
                            type="password"
                            x-model="formData.server_password"
                            placeholder="Leave blank if no password"
                            :disabled="loading"
                            class="form-control"
                            style="width: 100%; padding: 10px 12px; background-color: #0e0e10; border: 1px solid #3f3f46; border-radius: 6px; color: #efeff1; font-size: 14px;"
                        >
                    </div>
                </div>

                {{-- Lobby Code --}}
                <div x-show="selectedJoinMethod === 'lobby_code'" x-cloak class="form-group" style="margin-bottom: 16px;">
                    <label for="lobby-code" class="form-label" style="font-size: 14px; color: #b3b3b5; margin-bottom: 8px; display: block;">
                        Lobby/Party Code
                    </label>
                    <input
                        id="lobby-code"
                        type="text"
                        x-model="formData.lobby_code"
                        placeholder="Enter code (e.g., AB12CD)"
                        :disabled="loading"
                        maxlength="50"
                        class="form-control"
                        style="width: 100%; padding: 10px 12px; background-color: #0e0e10; border: 1px solid #3f3f46; border-radius: 6px; color: #efeff1; font-size: 14px; text-transform: uppercase;"
                    >
                    <p class="help-text" style="font-size: 12px; color: #71717a; margin-top: 6px;">
                        Enter the party code from your game
                    </p>
                </div>

                {{-- Server Address (for non-Steam games like Minecraft) --}}
                <div x-show="selectedJoinMethod === 'server_address'" x-cloak>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label for="server-address-ip" class="form-label" style="font-size: 14px; color: #b3b3b5; margin-bottom: 8px; display: block;">
                            Server Address
                        </label>
                        <input
                            id="server-address-ip"
                            type="text"
                            x-model="formData.server_ip"
                            placeholder="play.example.com or IP address"
                            :disabled="loading"
                            class="form-control"
                            style="width: 100%; padding: 10px 12px; background-color: #0e0e10; border: 1px solid #3f3f46; border-radius: 6px; color: #efeff1; font-size: 14px;"
                        >
                    </div>
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="server-address-port" class="form-label" style="font-size: 14px; color: #b3b3b5; margin-bottom: 8px; display: block;">
                            Port (Optional)
                        </label>
                        <input
                            id="server-address-port"
                            type="number"
                            x-model="formData.server_port"
                            :placeholder="currentMethodConfig?.default_port || '25565'"
                            :disabled="loading"
                            min="1"
                            max="65535"
                            class="form-control"
                            style="width: 100%; padding: 10px 12px; background-color: #0e0e10; border: 1px solid #3f3f46; border-radius: 6px; color: #efeff1; font-size: 14px;"
                        >
                        <p class="help-text" style="font-size: 12px; color: #71717a; margin-top: 6px;" x-show="currentMethodConfig?.default_port" x-text="`Default port is ${currentMethodConfig?.default_port}`"></p>
                    </div>
                </div>

                {{-- Save Button --}}
                <button
                    @click="saveLobby()"
                    :disabled="loading || !canSave()"
                    class="btn btn-primary btn-sm"
                    style="width: 100%; margin-bottom: 12px;"
                >
                    <span x-show="!loading">Save Lobby</span>
                    <span x-show="loading" x-cloak>Saving...</span>
                </button>

                {{-- Instructions Button --}}
                <button
                    @click="showInstructions = true"
                    type="button"
                    class="btn btn-secondary btn-sm"
                    style="width: 100%;"
                    x-show="currentMethodConfig"
                >
                    Show Instructions
                </button>
            </div>
        </div>
    @endif

    {{-- Active Lobbies List --}}
    <div class="card">
        <h3 class="card-header">
            @if($isOwnProfile)
                Your Active Lobbies
            @else
                {{ $user->display_name }}'s Lobbies
            @endif
        </h3>

        {{-- Loading State --}}
        <div x-show="loading && activeLobbies.length === 0" x-cloak style="padding: 40px; text-align: center;">
            <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #3f3f46; border-top-color: #667eea; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <p style="color: #71717a; margin-top: 12px;">Loading lobbies...</p>
        </div>

        {{-- Empty State --}}
        <div x-show="!loading && activeLobbies.length === 0" x-cloak style="text-align: center; padding: 40px; background-color: #0e0e10; border-radius: 8px; border: 2px dashed #3f3f46;">
            <p style="color: #71717a; margin-bottom: 8px;">
                @if($isOwnProfile)
                    No active lobbies yet
                @else
                    {{ $user->display_name }} has no active lobbies
                @endif
            </p>
            <p style="color: #b3b3b5; font-size: 14px;">
                @if($isOwnProfile)
                    Create a lobby above to get started
                @else
                    Check back later to join their games
                @endif
            </p>
        </div>

        {{-- Active Lobbies Grid (Responsive) --}}
        <div
            x-show="activeLobbies.length > 0"
            x-cloak
            style="display: grid; gap: 12px; grid-template-columns: repeat(auto-fill, minmax(min(100%, 320px), 1fr));"
        >
            <template x-for="lobby in activeLobbies" :key="lobby.id">
                <div
                    class="lobby-card"
                    style="background-color: #0e0e10; border-radius: 8px; padding: 16px; border-left: 4px solid #667eea; position: relative; transition: all 0.3s ease; cursor: pointer;"
                    @mouseenter="$el.style.backgroundColor = '#18181b'"
                    @mouseleave="$el.style.backgroundColor = '#0e0e10'"
                >
                    {{-- Game Icon and Name --}}
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px;">
                        <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
                            <div style="width: 48px; height: 48px; background-color: #3f3f46; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <span style="font-size: 24px;" x-text="getGameIcon(lobby.game_id)">🎮</span>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <h4 style="margin: 0; color: #efeff1; font-weight: 600; font-size: 16px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" x-text="lobby.gaming_preference?.game_name || 'Unknown Game'"></h4>
                                <p style="margin: 4px 0 0 0; color: #b3b3b5; font-size: 13px;" x-text="formatJoinMethod(lobby.join_method)"></p>
                            </div>
                        </div>

                        {{-- Delete Button (Own Profile Only) --}}
                        <button
                            x-show="{{ $isOwnProfile ? 'true' : 'false' }}"
                            @click.stop="deleteLobby(lobby.id)"
                            type="button"
                            style="width: 32px; height: 32px; border-radius: 6px; background-color: #3f3f46; color: #ef4444; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; flex-shrink: 0;"
                            @mouseenter="$el.style.backgroundColor = '#ef4444'; $el.style.color = '#fff'"
                            @mouseleave="$el.style.backgroundColor = '#3f3f46'; $el.style.color = '#ef4444'"
                            title="Delete lobby"
                        >
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Lobby Details --}}
                    <div style="margin-bottom: 12px;">
                        {{-- Expiration Timer --}}
                        <div x-show="lobby.expires_at" x-cloak style="display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20" :style="getTimeRemainingMinutes(lobby.expires_at) < 5 ? 'color: #ef4444' : 'color: #71717a'">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            <span
                                style="font-size: 13px;"
                                :style="getTimeRemainingMinutes(lobby.expires_at) < 5 ? 'color: #ef4444; font-weight: 600;' : 'color: #71717a'"
                                x-text="formatTimeRemaining(lobby.expires_at)"
                            ></span>
                        </div>

                        {{-- No Expiration Label --}}
                        <div x-show="!lobby.expires_at" x-cloak style="display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20" style="color: #10b981;">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span style="font-size: 13px; color: #10b981; font-weight: 600;">Persistent Lobby</span>
                        </div>

                        {{-- Join Information Display --}}
                        <div style="background-color: #18181b; padding: 10px 12px; border-radius: 6px; font-family: monospace; font-size: 12px; color: #efeff1; word-break: break-all; position: relative;">
                            <span x-text="getJoinInfo(lobby)"></span>

                            {{-- Copy Button --}}
                            <button
                                @click.stop="copyToClipboard(getJoinInfo(lobby), lobby.id)"
                                type="button"
                                style="position: absolute; top: 8px; right: 8px; width: 28px; height: 28px; border-radius: 4px; background-color: #3f3f46; color: #b3b3b5; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;"
                                @mouseenter="$el.style.backgroundColor = '#667eea'; $el.style.color = '#fff'"
                                @mouseleave="$el.style.backgroundColor = '#3f3f46'; $el.style.color = '#b3b3b5'"
                                :title="copiedLobbyId === lobby.id ? 'Copied!' : 'Copy to clipboard'"
                            >
                                <svg x-show="copiedLobbyId !== lobby.id" width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"/>
                                    <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z"/>
                                </svg>
                                <svg x-show="copiedLobbyId === lobby.id" x-cloak width="14" height="14" fill="currentColor" viewBox="0 0 20 20" style="color: #10b981;">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div style="display: grid; gap: 8px;">
                        {{-- Join Button (for steam_lobby and steam_connect) --}}
                        <template x-if="lobby.join_method === 'steam_lobby' || lobby.join_method === 'steam_connect'">
                            <a
                                :href="getJoinLink(lobby)"
                                target="_blank"
                                class="btn btn-success btn-sm"
                                style="width: 100%; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;"
                            >
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                                </svg>
                                <span>Join via Steam</span>
                            </a>
                        </template>

                        {{-- Info Text (for other join methods) --}}
                        <template x-if="lobby.join_method !== 'steam_lobby' && lobby.join_method !== 'steam_connect'">
                            <div style="background-color: #18181b; padding: 10px 12px; border-radius: 6px; text-align: center; font-size: 13px; color: #b3b3b5;">
                                <span x-show="lobby.join_method === 'lobby_code'">Copy the code above and paste it in-game</span>
                                <span x-show="lobby.join_method === 'server_address'">Copy the address above and add to your server list</span>
                                <span x-show="lobby.join_method === 'join_command'">Copy the command above and run it in-game</span>
                                <span x-show="lobby.join_method === 'private_match'">Use the match details above to join</span>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Instructions Modal --}}
    <div
        x-show="showInstructions"
        x-cloak
        @click.self="showInstructions = false"
        style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 20px;"
    >
        <div
            @click.stop
            class="instructions-modal-content"
            style="background-color: #18181b; border-radius: 12px; max-width: 600px; width: 100%; max-height: 85vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.5);"
        >
            <x-lobby-instructions-modal :configuration="'currentMethodConfig'" />
        </div>
    </div>
</div>

{{-- Add spinner animation and responsive styles --}}
<style>
@keyframes spin {
    to { transform: rotate(360deg); }
}
[x-cloak] { display: none !important; }

/* Mobile Responsive Styles */
@media (max-width: 640px) {
    /* Larger touch targets for mobile */
    .lobby-manager .btn {
        min-height: 44px;
        font-size: 15px;
    }

    .lobby-manager .form-control {
        min-height: 44px;
        font-size: 16px; /* Prevents zoom on iOS */
    }

    /* Better spacing on mobile */
    .lobby-manager .card {
        padding: 16px;
    }

    /* Lobby cards full width on mobile */
    .lobby-card {
        min-height: auto;
    }

    /* Stack action buttons vertically on small screens */
    .lobby-card .btn {
        padding: 10px 12px;
        font-size: 14px;
    }

    /* Instructions modal full screen on mobile */
    .instructions-modal-content {
        max-height: 90vh !important;
        border-radius: 8px !important;
        margin: 10px !important;
    }
}

@media (min-width: 641px) and (max-width: 1024px) {
    /* Tablet: 2 columns */
    .lobby-manager .lobby-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1025px) {
    /* Desktop: 3 columns */
    .lobby-manager .lobby-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* Touch-friendly hover states (disable on touch devices) */
@media (hover: none) {
    .lobby-card:hover {
        background-color: #0e0e10 !important;
    }
}
</style>
