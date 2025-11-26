{{--
    Discord-style User Member Card Component

    A popup card that displays user information when clicking on a member
    in the server member list. Positioned to the left of the trigger element.

    @param User $user - The user to display
    @param Server $server - The current server (for roles)
    @param string $roleColor - The color of the user's highest role
--}}

@props([
    'user',
    'server',
    'roleColor' => '#ffffff',
])

@php
    // Get user's roles in this server
    $userRoles = $user->roles()
        ->wherePivot('server_id', $server->id)
        ->orderBy('position', 'desc')
        ->get();

    // Get active lobbies
    $lobbies = $user->gameLobbies ?? collect();
    $activeLobbies = $lobbies->filter(fn($lobby) => $lobby->isActive());

    // Game icon mapping (Steam CDN)
    $gameIcons = [
        730 => 'https://cdn.cloudflare.steamstatic.com/steam/apps/730/capsule_184x69.jpg', // CS2
        570 => 'https://cdn.cloudflare.steamstatic.com/steam/apps/570/capsule_184x69.jpg', // Dota 2
        230 => 'https://cdn.cloudflare.steamstatic.com/steam/apps/230/capsule_184x69.jpg', // Warframe (actually app 230410)
        230410 => 'https://cdn.cloudflare.steamstatic.com/steam/apps/230410/capsule_184x69.jpg', // Warframe
        1172470 => 'https://cdn.cloudflare.steamstatic.com/steam/apps/1172470/capsule_184x69.jpg', // Apex
        252490 => 'https://cdn.cloudflare.steamstatic.com/steam/apps/252490/capsule_184x69.jpg', // Rust
        578080 => 'https://cdn.cloudflare.steamstatic.com/steam/apps/578080/capsule_184x69.jpg', // PUBG
        359550 => 'https://cdn.cloudflare.steamstatic.com/steam/apps/359550/capsule_184x69.jpg', // R6S
        1097150 => 'https://cdn.cloudflare.steamstatic.com/steam/apps/1097150/capsule_184x69.jpg', // Fall Guys
    ];

    // Prepare lobby data for JavaScript
    $lobbyData = $activeLobbies->map(function($lobby) use ($gameIcons) {
        $gameAppId = $lobby->game_id ?? 730;
        $gameIcon = $gameIcons[$gameAppId] ?? "https://cdn.cloudflare.steamstatic.com/steam/apps/{$gameAppId}/capsule_184x69.jpg";

        return [
            'id' => $lobby->id,
            'game_name' => $lobby->getGameName(),
            'game_appid' => $gameAppId,
            'game_icon' => $gameIcon,
            'join_link' => $lobby->generateJoinLink(),
            'join_method' => $lobby->join_method,
            'display_format' => $lobby->getDisplayFormat(),
            'time_remaining_minutes' => $lobby->timeRemaining(),
            'is_expiring_soon' => $lobby->timeRemaining() && $lobby->timeRemaining() < 5,
        ];
    });

    // Get banner color or use role color as gradient
    $bannerGradient = "linear-gradient(135deg, {$roleColor}60 0%, {$roleColor}30 100%)";
@endphp

<div
    class="user-member-card"
    x-data="{
        lobbies: {{ $lobbyData->toJson() }},
        copying: false,
        async joinLobby(lobby) {
            if (!lobby.join_link) {
                return;
            }
            this.copying = true;
            try {
                await navigator.clipboard.writeText(lobby.join_link);
                if (lobby.join_method === 'steam_lobby' || lobby.join_method === 'steam_connect') {
                    window.location.href = lobby.join_link;
                }
            } catch (err) {
                console.error('Failed to copy:', err);
            }
            setTimeout(() => this.copying = false, 1500);
        }
    }"
    style="
        width: 340px;
        background-color: #232428;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.24);
        font-family: 'gg sans', 'Noto Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
    "
    @click.stop
>
    {{-- Banner Section --}}
    <div style="
        height: 60px;
        background: {{ $bannerGradient }};
        position: relative;
    "></div>

    {{-- Avatar Section --}}
    <div style="padding: 0 16px; position: relative; margin-top: -40px;">
        <div style="
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 6px solid #232428;
            background-color: #232428;
            overflow: hidden;
            position: relative;
        ">
            <img
                src="{{ $user->profile->avatar_url ?? asset('images/default-avatar.png') }}"
                alt="{{ $user->display_name }}"
                style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;"
            >
            {{-- Online Status Indicator --}}
            <div style="
                position: absolute;
                bottom: 2px;
                right: 2px;
                width: 16px;
                height: 16px;
                border-radius: 50%;
                border: 4px solid #232428;
                background-color: {{ $user->profile->status === 'online' ? '#23a559' : '#80848e' }};
            "></div>
        </div>
    </div>

    {{-- Card Body --}}
    <div style="
        background-color: #111214;
        margin: 12px;
        border-radius: 8px;
        padding: 12px;
    ">
        {{-- Display Name & Username --}}
        <div style="margin-bottom: 12px;">
            <div style="
                font-size: 20px;
                font-weight: 600;
                color: #f2f3f5;
                line-height: 1.2;
            ">{{ $user->display_name }}</div>
            <div style="
                font-size: 14px;
                color: #b5bac1;
            ">{{ $user->username }}</div>
        </div>

        {{-- Active Lobby Section --}}
        @if($activeLobbies->isNotEmpty())
            <div style="
                background-color: #1e1f22;
                border-radius: 8px;
                padding: 12px;
                margin-bottom: 12px;
            ">
                <div style="
                    font-size: 12px;
                    font-weight: 700;
                    color: #b5bac1;
                    text-transform: uppercase;
                    margin-bottom: 8px;
                    letter-spacing: 0.02em;
                ">Playing</div>

                <template x-for="lobby in lobbies" :key="lobby.id">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        {{-- Game Image (Large) --}}
                        <div style="
                            width: 60px;
                            height: 60px;
                            border-radius: 8px;
                            overflow: hidden;
                            flex-shrink: 0;
                            background-color: #2b2d31;
                        ">
                            <img
                                :src="lobby.game_icon"
                                :alt="lobby.game_name"
                                style="width: 100%; height: 100%; object-fit: cover;"
                                onerror="this.src='https://cdn.cloudflare.steamstatic.com/steam/apps/730/capsule_184x69.jpg'"
                            >
                        </div>

                        {{-- Game Info --}}
                        <div style="flex: 1; min-width: 0;">
                            <div style="
                                font-size: 14px;
                                font-weight: 600;
                                color: #f2f3f5;
                                margin-bottom: 2px;
                            " x-text="lobby.game_name"></div>
                            <div style="
                                font-size: 12px;
                                color: #b5bac1;
                                margin-bottom: 4px;
                            " x-text="lobby.display_format"></div>
                            <div style="
                                font-size: 12px;
                                color: #23a559;
                                font-weight: 500;
                            " x-show="lobby.time_remaining_minutes" x-text="lobby.time_remaining_minutes + ' min left'"></div>
                        </div>

                        {{-- Join Button --}}
                        <button
                            @click.stop="joinLobby(lobby)"
                            style="
                                background-color: #23a559;
                                color: white;
                                border: none;
                                padding: 8px 16px;
                                border-radius: 4px;
                                font-size: 14px;
                                font-weight: 500;
                                cursor: pointer;
                                transition: background-color 0.17s ease;
                                flex-shrink: 0;
                            "
                            onmouseover="this.style.backgroundColor='#1a8a47'"
                            onmouseout="this.style.backgroundColor='#23a559'"
                            :disabled="copying"
                        >
                            <span x-text="copying ? 'Copied!' : 'Join'"></span>
                        </button>
                    </div>
                </template>
            </div>
        @endif

        {{-- Divider --}}
        <div style="height: 1px; background-color: #3f4147; margin-bottom: 12px;"></div>

        {{-- Roles Section --}}
        @if($userRoles->isNotEmpty())
            <div style="margin-bottom: 12px;">
                <div style="
                    font-size: 12px;
                    font-weight: 700;
                    color: #b5bac1;
                    text-transform: uppercase;
                    margin-bottom: 8px;
                    letter-spacing: 0.02em;
                ">Roles</div>

                <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                    @foreach($userRoles->take(5) as $role)
                        <div style="
                            display: inline-flex;
                            align-items: center;
                            gap: 6px;
                            background-color: #2b2d31;
                            padding: 4px 8px;
                            border-radius: 4px;
                            font-size: 12px;
                            font-weight: 500;
                            color: #dbdee1;
                        ">
                            <span style="
                                width: 12px;
                                height: 12px;
                                border-radius: 50%;
                                background-color: {{ $role->color ?? '#99aab5' }};
                                flex-shrink: 0;
                            "></span>
                            <span style="max-width: 80px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $role->name }}</span>
                        </div>
                    @endforeach
                    @if($userRoles->count() > 5)
                        <div style="
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            width: 28px;
                            height: 24px;
                            background-color: #2b2d31;
                            border-radius: 4px;
                            font-size: 12px;
                            font-weight: 500;
                            color: #b5bac1;
                        ">+{{ $userRoles->count() - 5 }}</div>
                    @endif
                </div>
            </div>

            {{-- Divider --}}
            <div style="height: 1px; background-color: #3f4147; margin-bottom: 12px;"></div>
        @endif

        {{-- Member Since --}}
        <div style="margin-bottom: 12px;">
            <div style="
                font-size: 12px;
                font-weight: 700;
                color: #b5bac1;
                text-transform: uppercase;
                margin-bottom: 4px;
                letter-spacing: 0.02em;
            ">Member Since</div>
            <div style="font-size: 13px; color: #dbdee1;">
                {{ ($user->pivot->created_at ?? $user->created_at)?->format('M d, Y') ?? 'Unknown' }}
            </div>
        </div>

        {{-- Action Button --}}
        <a
            href="{{ route('profile.show', $user->username) }}"
            style="
                display: flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                background-color: #4e5058;
                color: #fff;
                padding: 8px 16px;
                border-radius: 4px;
                font-size: 14px;
                font-weight: 500;
                text-decoration: none;
                transition: background-color 0.17s ease;
            "
            onmouseover="this.style.backgroundColor='#6d6f78'"
            onmouseout="this.style.backgroundColor='#4e5058'"
        >
            View Profile
        </a>
    </div>
</div>
