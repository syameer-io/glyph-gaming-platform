{{-- User Profile Panel for Chat View --}}
<div class="flex flex-col h-full bg-zinc-800">
    {{-- Profile Header with Banner --}}
    <div class="relative">
        <div class="h-24 bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 relative overflow-hidden">
            @if($user->profile->banner_url ?? false)
                <img src="{{ $user->profile->banner_url }}" alt="Profile Banner" class="absolute inset-0 w-full h-full object-cover opacity-80">
            @endif
        </div>
        <div class="absolute -bottom-10 left-4">
            <div class="relative">
                <img src="{{ $user->profile->avatar_url ?? '/images/default-avatar.png' }}" alt="{{ $user->display_name }}" class="w-20 h-20 rounded-full border-4 border-zinc-800 object-cover shadow-lg">
                <span class="absolute bottom-1 right-1 w-4 h-4 rounded-full border-2 border-zinc-800
                    {{ ($user->profile->status ?? 'offline') === 'online' ? 'bg-green-500' : (($user->profile->status ?? 'offline') === 'idle' ? 'bg-yellow-500' : (($user->profile->status ?? 'offline') === 'dnd' ? 'bg-red-500' : 'bg-zinc-500')) }}"></span>
            </div>
        </div>
    </div>
    <div class="pt-12 px-4 pb-4 border-b border-zinc-700">
        <h3 class="text-xl font-bold text-white">{{ $user->display_name }}</h3>
        <p class="text-zinc-400 text-sm">{{ '@' . $user->username }}</p>
    </div>
    <div class="flex-1 overflow-y-auto dm-scrollable p-4 space-y-5">
        <div class="bg-zinc-900/50 rounded-lg p-3">
            <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-2">Status</h4>
            @php
                $currentGame = $user->profile->current_game ?? null;
                $gameName = is_array($currentGame) ? ($currentGame['name'] ?? null) : $currentGame;
            @endphp
            @if($gameName)
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    <span class="text-green-400 text-sm">Playing {{ $gameName }}</span>
                </div>
            @else
                <p class="text-white text-sm">
                    @switch($user->profile->status ?? 'offline')
                        @case('online')
                            <span class="text-green-400">Online</span>
                            @break
                        @case('idle')
                            <span class="text-yellow-400">Idle</span>
                            @break
                        @case('dnd')
                            <span class="text-red-400">Do Not Disturb</span>
                            @break
                        @default
                            <span class="text-zinc-500">Offline</span>
                    @endswitch
                </p>
            @endif
            @if($user->profile->custom_status ?? false)
                <p class="text-zinc-300 text-sm mt-1">{{ $user->profile->custom_status }}</p>
            @endif
        </div>
        @if($user->profile->bio ?? false)
            <div>
                <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-2">About Me</h4>
                <p class="text-zinc-300 text-sm leading-relaxed">{{ $user->profile->bio }}</p>
            </div>
        @endif
        <div>
            <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-2">Member Since</h4>
            <p class="text-white text-sm">{{ $user->created_at->format('F j, Y') }}</p>
        </div>
        @php
            $mutualServers = auth()->user()->servers()->whereHas('members', fn($q) => $q->where('user_id', $user->id))->with('members')->take(5)->get();
        @endphp
        @if($mutualServers->isNotEmpty())
            <div>
                <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-2">Mutual Servers - {{ $mutualServers->count() }}</h4>
                <div class="space-y-2">
                    @foreach($mutualServers as $server)
                        <a href="{{ route('server.show', $server) }}" class="flex items-center gap-3 p-2 rounded-lg hover:bg-zinc-700/50 transition-colors group">
                            @if($server->icon_url)
                                <img src="{{ $server->icon_url }}" alt="{{ $server->name }}" class="w-8 h-8 rounded-lg object-cover">
                            @else
                                <div class="w-8 h-8 rounded-lg bg-zinc-700 flex items-center justify-center">
                                    <span class="text-xs font-medium text-zinc-300">{{ strtoupper(substr($server->name, 0, 2)) }}</span>
                                </div>
                            @endif
                            <span class="text-sm text-zinc-300 group-hover:text-white transition-colors truncate">{{ $server->name }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
        @php
            $myFriendIds = auth()->user()->friends()->wherePivot('status', 'accepted')->pluck('users.id')->toArray();
            $theirFriendIds = $user->friends()->wherePivot('status', 'accepted')->pluck('users.id')->toArray();
            $mutualFriendIds = array_intersect($myFriendIds, $theirFriendIds);
            $mutualFriends = \App\Models\User::whereIn('id', array_slice($mutualFriendIds, 0, 5))->with('profile')->get();
        @endphp
        @if($mutualFriends->isNotEmpty())
            <div>
                <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-2">Mutual Friends - {{ count($mutualFriendIds) }}</h4>
                <div class="flex flex-wrap gap-2">
                    @foreach($mutualFriends as $friend)
                        <a href="{{ route('profile.show', $friend->username) }}" class="group relative" title="{{ $friend->display_name }}">
                            <img src="{{ $friend->profile->avatar_url ?? '/images/default-avatar.png' }}" alt="{{ $friend->display_name }}" class="w-10 h-10 rounded-full object-cover border-2 border-zinc-700 group-hover:border-indigo-500 transition-colors">
                        </a>
                    @endforeach
                    @if(count($mutualFriendIds) > 5)
                        <div class="w-10 h-10 rounded-full bg-zinc-700 flex items-center justify-center border-2 border-zinc-600 text-xs text-zinc-300 font-medium">+{{ count($mutualFriendIds) - 5 }}</div>
                    @endif
                </div>
            </div>
        @endif
    </div>
    <div class="p-4 border-t border-zinc-700">
        <a href="{{ route('profile.show', $user->username) }}" class="flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-zinc-700 hover:bg-zinc-600 text-white rounded-lg transition-colors font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            View Full Profile
        </a>
    </div>
</div>
