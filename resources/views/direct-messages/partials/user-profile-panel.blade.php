{{-- User Profile Panel for DM Chat View --}}
{{-- FIXED VERSION - Simple layout without complex absolute positioning --}}
<div class="flex flex-col h-full bg-zinc-800">

    {{-- Banner with Avatar inside --}}
    <div class="relative" style="padding-bottom: 40px; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #ec4899 100%);">
        {{-- Banner background --}}
        @if($user->profile->banner_url ?? false)
            <div class="absolute inset-0 overflow-hidden">
                <img src="{{ $user->profile->banner_url }}" alt="Banner" class="w-full h-full object-cover opacity-60">
            </div>
        @endif

        {{-- Avatar at bottom of banner --}}
        <div class="relative flex justify-start px-4" style="padding-top: 60px;">
            <div class="relative">
                <img
                    src="{{ $user->profile->avatar_url ?? '/images/default-avatar.png' }}"
                    alt="{{ $user->display_name }}"
                    style="width: 80px; height: 80px; border: 5px solid #27272a;"
                    class="rounded-full object-cover"
                >
                {{-- Status dot --}}
                <span style="position: absolute; bottom: 4px; right: 4px; width: 18px; height: 18px; border: 3px solid #27272a;"
                      class="rounded-full {{ ($user->profile->status ?? 'offline') === 'online' ? 'bg-green-500' : (($user->profile->status ?? 'offline') === 'idle' ? 'bg-yellow-500' : (($user->profile->status ?? 'offline') === 'dnd' ? 'bg-red-500' : 'bg-zinc-500')) }}">
                </span>
            </div>
        </div>
    </div>

    {{-- User Name Section --}}
    <div style="padding: 16px 16px 12px 16px; background-color: #18181b;">
        <h3 class="text-xl font-bold text-white truncate">{{ $user->display_name }}</h3>
        <p class="text-zinc-400 text-sm truncate" style="margin-top: 2px;">{{ '@' . $user->username }}</p>
    </div>

    {{-- Divider --}}
    <div style="height: 1px; background-color: #3f3f46; margin: 0 16px;"></div>

    {{-- Scrollable Content --}}
    <div class="flex-1 overflow-y-auto" style="padding: 16px;">

        {{-- STATUS --}}
        <div style="margin-bottom: 20px;">
            <h4 style="font-size: 11px; font-weight: 700; color: #a1a1aa; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">
                Status
            </h4>
            @php
                $currentGame = $user->profile->current_game ?? null;
                $gameName = is_array($currentGame) ? ($currentGame['name'] ?? null) : $currentGame;
            @endphp
            @if($gameName)
                <p style="color: #4ade80; font-size: 14px;">Playing {{ $gameName }}</p>
            @else
                <p style="font-size: 14px;" class="{{ ($user->profile->status ?? 'offline') === 'online' ? 'text-green-400' : 'text-zinc-400' }}">
                    {{ ucfirst($user->profile->status ?? 'Offline') }}
                </p>
            @endif
        </div>

        {{-- MEMBER SINCE --}}
        <div style="margin-bottom: 20px;">
            <h4 style="font-size: 11px; font-weight: 700; color: #a1a1aa; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">
                Member Since
            </h4>
            <p style="color: #ffffff; font-size: 14px;">{{ $user->created_at->format('j M Y') }}</p>
        </div>

        {{-- MUTUAL SERVERS --}}
        @php
            $mutualServers = auth()->user()->servers()->whereHas('members', fn($q) => $q->where('user_id', $user->id))->with('members')->take(5)->get();
        @endphp
        @if($mutualServers->isNotEmpty())
            <div style="margin-bottom: 20px;">
                <h4 style="font-size: 11px; font-weight: 700; color: #a1a1aa; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px;">
                    Mutual Servers - {{ $mutualServers->count() }}
                </h4>
                <div>
                    @foreach($mutualServers as $server)
                        <a href="{{ route('server.show', $server) }}"
                           class="flex items-center gap-3 hover:bg-zinc-700/50 transition-colors rounded"
                           style="padding: 8px; margin: 0 -8px;">
                            @if($server->icon_url)
                                <img src="{{ $server->icon_url }}" alt="{{ $server->name }}" style="width: 32px; height: 32px;" class="rounded-full object-cover">
                            @else
                                <div style="width: 32px; height: 32px; background-color: #4f46e5;" class="rounded-full flex items-center justify-center">
                                    <span style="font-size: 11px; font-weight: 600; color: white;">{{ strtoupper(substr($server->name, 0, 2)) }}</span>
                                </div>
                            @endif
                            <span style="font-size: 14px; color: #d4d4d8;" class="truncate">{{ $server->name }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ABOUT ME --}}
        @if($user->profile->bio ?? false)
            <div style="margin-bottom: 20px;">
                <h4 style="font-size: 11px; font-weight: 700; color: #a1a1aa; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">
                    About Me
                </h4>
                <p style="color: #d4d4d8; font-size: 14px; line-height: 1.5;">{{ $user->profile->bio }}</p>
            </div>
        @endif

        {{-- MUTUAL FRIENDS --}}
        @php
            $myFriendIds = auth()->user()->friends()->wherePivot('status', 'accepted')->pluck('users.id')->toArray();
            $theirFriendIds = $user->friends()->wherePivot('status', 'accepted')->pluck('users.id')->toArray();
            $mutualFriendIds = array_intersect($myFriendIds, $theirFriendIds);
            $mutualFriends = \App\Models\User::whereIn('id', array_slice($mutualFriendIds, 0, 5))->with('profile')->get();
        @endphp
        @if($mutualFriends->isNotEmpty())
            <div style="margin-bottom: 16px;">
                <h4 style="font-size: 11px; font-weight: 700; color: #a1a1aa; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px;">
                    Mutual Friends - {{ count($mutualFriendIds) }}
                </h4>
                <div class="flex flex-wrap gap-2">
                    @foreach($mutualFriends as $friend)
                        <a href="{{ route('profile.show', $friend->username) }}" title="{{ $friend->display_name }}">
                            <img src="{{ $friend->profile->avatar_url ?? '/images/default-avatar.png' }}"
                                 alt="{{ $friend->display_name }}"
                                 style="width: 40px; height: 40px; border: 2px solid #3f3f46;"
                                 class="rounded-full object-cover hover:border-indigo-500 transition-colors">
                        </a>
                    @endforeach
                    @if(count($mutualFriendIds) > 5)
                        <div style="width: 40px; height: 40px; border: 2px solid #52525b; font-size: 11px; color: #d4d4d8;"
                             class="rounded-full bg-zinc-700 flex items-center justify-center font-medium">
                            +{{ count($mutualFriendIds) - 5 }}
                        </div>
                    @endif
                </div>
            </div>
        @endif

    </div>

    {{-- View Full Profile Button --}}
    <div style="padding: 16px; border-top: 1px solid #3f3f46;">
        <a href="{{ route('profile.show', $user->username) }}"
           style="display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 10px 16px; background-color: #4f46e5; color: white; border-radius: 6px; font-weight: 500; font-size: 14px; text-decoration: none; transition: background-color 0.15s;"
           onmouseover="this.style.backgroundColor='#4338ca'" onmouseout="this.style.backgroundColor='#4f46e5'">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            View Full Profile
        </a>
    </div>

</div>
