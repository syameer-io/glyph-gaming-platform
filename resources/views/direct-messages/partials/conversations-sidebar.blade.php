{{-- Conversations Sidebar for Chat View --}}
<div class="flex flex-col h-full" style="background-color: var(--color-surface);">
    {{-- Header with Back Link --}}
    <div class="p-4" style="border-bottom: 1px solid var(--color-border-primary);">
        <a href="{{ route('dm.index') }}"
           class="flex items-center gap-2 transition-colors group" style="color: var(--color-text-muted);" onmouseover="this.style.color='var(--color-text-primary)'" onmouseout="this.style.color='var(--color-text-muted)'">
            <svg class="w-5 h-5 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            <span class="font-medium">All Messages</span>
        </a>
    </div>

    {{-- Conversations List --}}
    <div class="flex-1 overflow-y-auto dm-scrollable">
        @php
            $allConversations = \App\Models\Conversation::forUser(auth()->id())
                ->with(['userOne.profile', 'userTwo.profile', 'latestMessage.sender'])
                ->get()
                ->map(function ($conv) {
                    $conv->other_participant = $conv->getOtherParticipant(auth()->user());
                    $conv->unread_count = $conv->getUnreadCountFor(auth()->id());
                    return $conv;
                });
        @endphp

        @foreach($allConversations as $conv)
            @php
                $other = $conv->other_participant;
                $isActive = isset($conversation) && $conversation->id === $conv->id;
                $hasUnread = $conv->unread_count > 0 && !$isActive;
            @endphp

            <a href="{{ route('dm.show', $conv) }}"
               class="flex items-center gap-3 px-3 py-2.5 mx-2 my-0.5 rounded-md transition-all duration-150"
               style="{{ $isActive ? 'background-color: var(--color-surface-active);' : '' }}{{ $hasUnread ? 'background-color: rgba(102, 126, 234, 0.1);' : '' }}"
               onmouseover="if(!{{ $isActive ? 'true' : 'false' }}) this.style.backgroundColor='var(--color-bg-elevated)'"
               onmouseout="if(!{{ $isActive ? 'true' : 'false' }}) this.style.backgroundColor='{{ $hasUnread ? 'rgba(102, 126, 234, 0.1)' : 'transparent' }}'">
                {{-- Avatar with Status --}}
                <div class="relative flex-shrink-0">
                    <img src="{{ $other->profile->avatar_url ?? '/images/default-avatar.png' }}"
                         alt="{{ $other->display_name }}"
                         class="w-10 h-10 rounded-full object-cover">
                    <span class="absolute bottom-0 right-0 w-3 h-3 rounded-full {{ $other->profile->status === 'online' ? 'bg-green-500' :
                                    ($other->profile->status === 'idle' ? 'bg-yellow-500' :
                                    ($other->profile->status === 'dnd' ? 'bg-red-500' : 'bg-zinc-500')) }}"
                          style="border: 2px solid var(--color-surface);">
                    </span>
                </div>

                {{-- User Info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <span class="font-medium truncate {{ $hasUnread ? 'font-semibold' : '' }}" style="color: var(--color-text-primary);">
                            {{ $other->display_name }}
                        </span>
                        @if($hasUnread)
                            <span class="w-2 h-2 bg-indigo-500 rounded-full flex-shrink-0 ml-2"></span>
                        @endif
                    </div>
                    @if($conv->latestMessage)
                        <p class="text-xs truncate" style="color: var({{ $hasUnread ? '--color-text-secondary' : '--color-text-muted' }});">
                            @if($conv->latestMessage->sender_id === auth()->id())
                                <span style="color: var(--color-text-muted);">You:</span>
                            @endif
                            {{ Str::limit($conv->latestMessage->content, 25) }}
                        </p>
                    @else
                        <p class="text-xs italic" style="color: var(--color-text-muted);">No messages yet</p>
                    @endif
                </div>
            </a>
        @endforeach

        @if($allConversations->isEmpty())
            <div class="flex flex-col items-center justify-center h-full p-4 text-center">
                <svg class="w-12 h-12 mb-3" style="color: var(--color-text-faint);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <p class="text-sm" style="color: var(--color-text-muted);">No conversations yet</p>
            </div>
        @endif
    </div>
</div>
