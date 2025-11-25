@extends('layouts.app')

@section('title', 'Friends - Glyph')

@section('content')
<nav class="navbar">
    <div class="container">
        <div class="navbar-content">
            <a href="{{ route('dashboard') }}" class="navbar-brand">Glyph</a>
            <div class="navbar-nav">
                <a href="{{ route('friends.search') }}" class="btn btn-primary btn-sm">Find Friends</a>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm">Back to Dashboard</a>
            </div>
        </div>
    </div>
</nav>

<main>
    <div class="container">
        <h1 style="margin-bottom: 32px;">Friends</h1>

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
                            <div style="font-size: 12px; color: #10b981; margin-top: 4px;">
                                Playing {{ $friend->profile->current_game['name'] }}
                            </div>
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
                    <form method="POST" action="{{ route('friends.remove', $friend->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to remove this friend?')">Remove</button>
                    </form>
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
                <p style="color: #71717a; text-align: center; padding: 48px;">No pending friend requests</p>
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
                    <span style="color: #71717a;">Pending</span>
                </div>
            @empty
                <p style="color: #71717a; text-align: center; padding: 48px;">No sent friend requests</p>
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