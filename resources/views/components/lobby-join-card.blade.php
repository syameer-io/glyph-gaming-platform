@props(['lobby', 'isOwnProfile' => false])

<div
    class="lobby-card"
    style="background-color: #0e0e10; border-radius: 8px; padding: 16px; border-left: 4px solid #667eea; position: relative; transition: all 0.3s ease; cursor: pointer;"
    :class="{ 'hover:bg-zinc-900': true }"
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
            x-show="isOwnProfile"
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
