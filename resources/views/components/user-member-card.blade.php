{{--
    Discord-style User Member Card Component
    Phase 2: Member List Enhancement (Enhanced)

    A popup card that displays user information when clicking on a member
    in the server member list. Positioned to the left of the trigger element.

    Features:
    - Banner area with role color gradient
    - Avatar with status indicator
    - Custom status with emoji
    - Display name and username
    - "Member since" date
    - Roles display
    - Active lobby/game info
    - "Message" quick action button
    - "Add Friend" button (if not friends)
    - Mutual servers count

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
    use App\Models\UserStatus;

    // Get current viewer for privacy checks
    $viewer = auth()->user();

    // Get user's roles in this server
    $userRoles = $user->roles()
        ->wherePivot('server_id', $server->id)
        ->orderBy('position', 'desc')
        ->get();

    // Privacy-aware lobby check
    $canSeeLobby = $user->profile->shouldShowLobbies($viewer);
    $lobbies = $canSeeLobby ? ($user->gameLobbies ?? collect()) : collect();
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

    // Privacy-aware status
    $canSeeStatus = $user->profile->shouldShowOnlineStatus($viewer);
    $status = $canSeeStatus ? $user->getCurrentStatus() : 'offline';
    $statusColor = $canSeeStatus ? $user->getStatusColor() : UserStatus::STATUS_COLORS['offline'];
    $statusLabel = $canSeeStatus ? (UserStatus::STATUS_LABELS[$status] ?? 'Offline') : 'Offline';

    // Privacy-aware activity
    $canSeeActivity = $user->profile->shouldShowGamingActivity($viewer);
    $customStatus = $canSeeActivity ? $user->getCustomStatus() : null;
    $activity = $canSeeActivity ? $user->getDisplayActivity() : null;

    // Check friendship status with current user
    $currentUser = auth()->user();
    $isSelf = $currentUser->id === $user->id;
    $isFriend = false;
    $hasPendingRequest = false;

    if (!$isSelf) {
        // Check if they are friends
        $isFriend = $currentUser->friends()
            ->where('friend_id', $user->id)
            ->wherePivot('status', 'accepted')
            ->exists();

        // Check for pending request
        if (!$isFriend) {
            $hasPendingRequest = $currentUser->friends()
                ->where('friend_id', $user->id)
                ->wherePivot('status', 'pending')
                ->exists();
        }
    }

    // Get mutual servers count
    $userServerIds = $user->servers()->pluck('servers.id');
    $currentUserServerIds = $currentUser->servers()->pluck('servers.id');
    $mutualServersCount = $userServerIds->intersect($currentUserServerIds)->count();

    // Get member since date - ensure it's a Carbon instance
    $memberPivot = $server->members()->where('user_id', $user->id)->first();
    $memberSinceRaw = $memberPivot?->pivot?->joined_at ?? $memberPivot?->pivot?->created_at ?? $user->created_at;

    // Convert to Carbon if it's a string
    if (is_string($memberSinceRaw)) {
        $memberSince = \Carbon\Carbon::parse($memberSinceRaw);
    } elseif ($memberSinceRaw instanceof \Carbon\Carbon || $memberSinceRaw instanceof \DateTime) {
        $memberSince = $memberSinceRaw;
    } else {
        $memberSince = null;
    }
@endphp

<div
    class="user-member-card"
    x-data="memberCardData_{{ $user->id }}()"
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

    {{-- Avatar Section (Clickable to view profile) --}}
    <div style="padding: 0 16px; position: relative; margin-top: -40px;">
        <a href="{{ route('profile.show', $user->username) }}" style="display: block; text-decoration: none;">
            <div style="
                width: 80px;
                height: 80px;
                border-radius: 50%;
                border: 6px solid #232428;
                background-color: #232428;
                overflow: hidden;
                position: relative;
                cursor: pointer;
                transition: opacity 0.15s ease;
            " onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                <img
                    src="{{ $user->profile->avatar_url ?? asset('images/default-avatar.png') }}"
                    alt="{{ $user->display_name }}"
                    style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;"
                >
                {{-- Status Indicator --}}
                <div style="
                    position: absolute;
                    bottom: 2px;
                    right: 2px;
                    width: 20px;
                    height: 20px;
                    border-radius: 50%;
                    border: 4px solid #232428;
                    background-color: {{ $statusColor }};
                " title="{{ $statusLabel }}"></div>
            </div>
        </a>
    </div>

    {{-- Card Body --}}
    <div style="
        background-color: #111214;
        margin: 12px;
        border-radius: 8px;
        padding: 12px;
    ">
        {{-- Display Name & Username (Name clickable to view profile) --}}
        <div style="margin-bottom: 12px;">
            <a
                href="{{ route('profile.show', $user->username) }}"
                style="
                    font-size: 20px;
                    font-weight: 600;
                    color: #f2f3f5;
                    line-height: 1.2;
                    text-decoration: none;
                    display: inline-block;
                    transition: color 0.15s ease;
                "
                onmouseover="this.style.color='#00b0f4'; this.style.textDecoration='underline'"
                onmouseout="this.style.color='#f2f3f5'; this.style.textDecoration='none'"
            >{{ $user->display_name }}</a>
            <div style="
                font-size: 14px;
                color: #b5bac1;
            ">{{ $user->username }}</div>
        </div>

        {{-- Custom Status Display --}}
        @if($customStatus || $activity)
            <div style="
                background-color: #1e1f22;
                border-radius: 8px;
                padding: 8px 12px;
                margin-bottom: 12px;
                display: flex;
                align-items: center;
                gap: 8px;
            ">
                @if($customStatus && $customStatus['emoji'])
                    <span style="font-size: 18px;">{{ $customStatus['emoji'] }}</span>
                @endif
                <span style="
                    font-size: 13px;
                    color: #b5bac1;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                ">{{ $activity ?? ($customStatus['text'] ?? '') }}</span>
            </div>
        @endif

        {{-- Gaming Status Section (when playing but no active lobby) --}}
        @if($canSeeActivity && $user->profile->current_game && $activeLobbies->isEmpty())
            @php
                $currentGame = $user->profile->current_game;
                $gameName = $currentGame['name'] ?? 'Unknown Game';
                $gameAppId = $currentGame['appid'] ?? null;
                $capsuleUrl = $gameAppId
                    ? "https://cdn.cloudflare.steamstatic.com/steam/apps/{$gameAppId}/capsule_184x69.jpg"
                    : null;
                $serverName = $currentGame['server_name'] ?? null;
                $mapName = $currentGame['map'] ?? null;
            @endphp
            <div class="member-card-gaming" @if($capsuleUrl) style="--game-capsule-url: url('{{ $capsuleUrl }}')" @endif>
                <div class="member-card-gaming-header">Playing</div>
                <div class="member-card-gaming-content">
                    @if($gameAppId)
                        <img
                            src="{{ $capsuleUrl }}"
                            alt="{{ $gameName }}"
                            class="member-card-gaming-icon"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                        >
                        <div class="gaming-status-icon-fallback gaming-status-icon-fallback--md" style="display: none;">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    @else
                        <div class="gaming-status-icon-fallback gaming-status-icon-fallback--md">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    @endif
                    <div class="member-card-gaming-info">
                        <div class="member-card-gaming-name">{{ $gameName }}</div>
                        @if($serverName || $mapName)
                            <div class="member-card-gaming-status">
                                @if($serverName){{ $serverName }}@endif
                                @if($serverName && $mapName) &bull; @endif
                                @if($mapName){{ $mapName }}@endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

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

        {{-- Member Since & Mutual Servers --}}
        <div style="display: flex; gap: 16px; margin-bottom: 12px;">
            {{-- Member Since --}}
            <div style="flex: 1;">
                <div style="
                    font-size: 12px;
                    font-weight: 700;
                    color: #b5bac1;
                    text-transform: uppercase;
                    margin-bottom: 4px;
                    letter-spacing: 0.02em;
                ">Member Since</div>
                <div style="font-size: 13px; color: #dbdee1;">
                    {{ $memberSince?->format('M d, Y') ?? 'Unknown' }}
                </div>
            </div>

            {{-- Mutual Servers --}}
            @if(!$isSelf && $mutualServersCount > 1)
                <div style="flex: 1;">
                    <div style="
                        font-size: 12px;
                        font-weight: 700;
                        color: #b5bac1;
                        text-transform: uppercase;
                        margin-bottom: 4px;
                        letter-spacing: 0.02em;
                    ">Mutual Servers</div>
                    <div style="font-size: 13px; color: #dbdee1;">
                        {{ $mutualServersCount }} {{ $mutualServersCount === 1 ? 'server' : 'servers' }}
                    </div>
                </div>
            @endif
        </div>

        {{-- Action Buttons --}}
        <div style="display: flex; gap: 8px;">
            {{-- Message Button --}}
            @if(!$isSelf)
                <a
                    href="{{ route('dm.index') }}?user={{ $user->id }}"
                    style="
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 8px;
                        flex: 1;
                        background-color: #5865f2;
                        color: #fff;
                        padding: 8px 16px;
                        border-radius: 4px;
                        font-size: 14px;
                        font-weight: 500;
                        text-decoration: none;
                        transition: background-color 0.17s ease;
                    "
                    onmouseover="this.style.backgroundColor='#4752c4'"
                    onmouseout="this.style.backgroundColor='#5865f2'"
                >
                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    Message
                </a>
            @endif

            {{-- Friend Action Button --}}
            @if(!$isSelf && !$isFriend && !$hasPendingRequest)
                {{-- Add Friend Button --}}
                <button
                    x-ref="friendBtn"
                    @click.stop="sendFriendRequest()"
                    :disabled="sendingFriendRequest"
                    style="
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 8px;
                        flex: 1;
                        background-color: #23a559;
                        color: #fff;
                        padding: 8px 16px;
                        border-radius: 4px;
                        border: none;
                        font-size: 14px;
                        font-weight: 500;
                        cursor: pointer;
                        transition: background-color 0.17s ease;
                    "
                    onmouseover="this.style.backgroundColor='#1a8a47'"
                    onmouseout="this.style.backgroundColor='#23a559'"
                >
                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    <span x-text="sendingFriendRequest ? 'Sending...' : 'Add Friend'"></span>
                </button>
            @elseif($hasPendingRequest)
                {{-- Request Sent (disabled) --}}
                <button
                    disabled
                    style="
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 8px;
                        flex: 1;
                        background-color: #4e5058;
                        color: #fff;
                        padding: 8px 16px;
                        border-radius: 4px;
                        border: none;
                        font-size: 14px;
                        font-weight: 500;
                        cursor: not-allowed;
                    "
                >
                    Request Sent
                </button>
            @elseif($isFriend)
                <a
                    href="{{ route('profile.show', $user->username) }}"
                    style="
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 8px;
                        flex: 1;
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
                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Friends
                </a>
            @else
                {{-- Self - View Profile --}}
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
            @endif
        </div>
    </div>
</div>

{{-- Alpine.js component data - defined as a function to avoid x-data parsing issues --}}
<script>
    document.addEventListener('alpine:init', () => {
        if (typeof Alpine.data === 'function' && !Alpine.data.memberCardData_{{ $user->id }}) {
            Alpine.data('memberCardData_{{ $user->id }}', () => ({
                lobbies: {!! $lobbyData->toJson() !!},
                copying: false,
                sendingFriendRequest: false,
                async joinLobby(lobby) {
                    if (!lobby.join_link) return;
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
                },
                async sendFriendRequest() {
                    if (this.sendingFriendRequest) return;
                    this.sendingFriendRequest = true;
                    try {
                        const response = await fetch('{{ route("friends.request") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ user_id: {{ $user->id }} })
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.$refs.friendBtn.textContent = 'Request Sent';
                            this.$refs.friendBtn.disabled = true;
                        } else {
                            alert(data.message || 'Failed to send friend request');
                        }
                    } catch (err) {
                        console.error('Friend request error:', err);
                        alert('Failed to send friend request');
                    }
                    this.sendingFriendRequest = false;
                }
            }));
        }
    });

    // Fallback: Define function on window if Alpine hasn't initialized yet
    window.memberCardData_{{ $user->id }} = function() {
        return {
            lobbies: {!! $lobbyData->toJson() !!},
            copying: false,
            sendingFriendRequest: false,
            async joinLobby(lobby) {
                if (!lobby.join_link) return;
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
            },
            async sendFriendRequest() {
                if (this.sendingFriendRequest) return;
                this.sendingFriendRequest = true;
                try {
                    const response = await fetch('{{ route("friends.request") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ user_id: {{ $user->id }} })
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.$refs.friendBtn.textContent = 'Request Sent';
                        this.$refs.friendBtn.disabled = true;
                    } else {
                        alert(data.message || 'Failed to send friend request');
                    }
                } catch (err) {
                    console.error('Friend request error:', err);
                    alert('Failed to send friend request');
                }
                this.sendingFriendRequest = false;
            }
        };
    };
</script>
