@extends('layouts.app')

@section('title', 'Friends - Glyph')

@section('content')
<x-navbar active-section="friends" />

<main>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
            <h1 style="margin: 0;">Friends</h1>
            <a href="{{ route('friends.search') }}" class="btn btn-primary" style="display: inline-flex; align-items: center;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <line x1="19" y1="8" x2="19" y2="14"></line>
                    <line x1="22" y1="11" x2="16" y2="11"></line>
                </svg>
                Add Friend
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="tabs">
            <a href="#friends" class="tab active" onclick="showTab('friends', this)">Friends ({{ $friends->count() }})</a>
            <a href="#pending" class="tab" onclick="showTab('pending', this)">Pending ({{ $pendingRequests->count() }})</a>
            <a href="#sent" class="tab" onclick="showTab('sent', this)">Sent ({{ $sentRequests->count() }})</a>
        </div>

        <div id="friends" class="tab-content">
            @forelse($friends as $friend)
                <div class="user-card">
                    <a href="{{ route('profile.show', $friend->username) }}">
                        <img src="{{ $friend->profile->avatar_url }}" alt="{{ $friend->display_name }}" class="user-card-avatar">
                    </a>
                    <div class="user-card-info">
                        <div class="user-card-name">
                            <span class="status-indicator {{ $friend->profile->status === 'online' ? 'status-online' : 'status-offline' }}"></span>
                            <a href="{{ route('profile.show', $friend->username) }}" class="link">{{ $friend->display_name }}</a>
                        </div>
                        <div class="user-card-username">{{ '@' . $friend->username }}</div>
                        @if($friend->profile->current_game)
                            <x-gaming-status-badge
                                :user="$friend"
                                variant="compact"
                                :show-details="false"
                                :show-indicator="true"
                                class="mt-1"
                            />
                        @endif
                        {{-- Lobby Join Button --}}
                        <div style="margin-top: 8px;">
                            <x-lobby-join-button
                                :user="$friend"
                                size="small"
                                variant="full"
                                :show-game-icon="true"
                                :show-timer="true"
                            />
                        </div>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <a href="{{ route('dm.with', $friend) }}" class="btn btn-primary btn-sm">
                            Message
                        </a>
                        <form method="POST" action="{{ route('friends.remove', $friend->id) }}" style="margin: 0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to remove this friend?')">Remove</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <h3>No friends yet</h3>
                    <p>Start by finding and adding some friends!</p>
                    <a href="{{ route('friends.search') }}" class="btn btn-primary" style="margin-top: 16px;">Find Friends</a>
                </div>
            @endforelse
        </div>

        <div id="pending" class="tab-content" style="display: none;">
            @forelse($pendingRequests as $request)
                <div class="user-card">
                    <a href="{{ route('profile.show', $request->username) }}">
                        <img src="{{ $request->profile->avatar_url }}" alt="{{ $request->display_name }}" class="user-card-avatar">
                    </a>
                    <div class="user-card-info">
                        <div class="user-card-name">
                            <a href="{{ route('profile.show', $request->username) }}" class="link">{{ $request->display_name }}</a>
                        </div>
                        <div class="user-card-username">{{ '@' . $request->username }}</div>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <form method="POST" action="{{ route('friends.accept', $request->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">Accept</button>
                        </form>
                        <form method="POST" action="{{ route('friends.decline', $request->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-sm">Decline</button>
                        </form>
                    </div>
                </div>
            @empty
                <p style="color: var(--color-text-muted); text-align: center; padding: 48px;">No pending friend requests</p>
            @endforelse
        </div>

        <div id="sent" class="tab-content" style="display: none;">
            @forelse($sentRequests as $request)
                <div class="user-card">
                    <a href="{{ route('profile.show', $request->username) }}">
                        <img src="{{ $request->profile->avatar_url }}" alt="{{ $request->display_name }}" class="user-card-avatar">
                    </a>
                    <div class="user-card-info">
                        <div class="user-card-name">
                            <a href="{{ route('profile.show', $request->username) }}" class="link">{{ $request->display_name }}</a>
                        </div>
                        <div class="user-card-username">{{ '@' . $request->username }}</div>
                    </div>
                    <span style="color: var(--color-text-muted);">Pending</span>
                </div>
            @empty
                <p style="color: var(--color-text-muted); text-align: center; padding: 48px;">No sent friend requests</p>
            @endforelse
        </div>
    </div>
</main>

<script>
function showTab(tabName, element) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.style.display = 'none';
    });
    
    // Remove active class from all tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Show selected tab content
    document.getElementById(tabName).style.display = 'block';
    
    // Add active class to clicked tab
    element.classList.add('active');
}
</script>
@endsection