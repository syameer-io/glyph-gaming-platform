@extends('layouts.app')

@section('title', 'Find Friends - Glyph')

@section('content')
<x-navbar active-section="friends" />

<main>
    <div class="container" style="max-width: 800px;">
        <h1 style="margin-bottom: 32px;">Find Friends</h1>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        <div class="card" style="margin-bottom: 32px;">
            <form method="POST" action="{{ route('friends.search.users') }}">
                @csrf
                <div style="display: flex; gap: 12px;">
                    <input type="text" name="query" placeholder="Search by username or display name" value="{{ $query ?? '' }}" required style="flex: 1;">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
        </div>

        @if(isset($users))
            <div class="card">
                <h3 class="card-header">Search Results</h3>
                @forelse($users as $user)
                    <div class="user-card">
                        <img src="{{ $user->profile->avatar_url }}" alt="{{ $user->display_name }}" class="user-card-avatar">
                        <div class="user-card-info">
                            <div class="user-card-name">{{ $user->display_name }}</div>
                            <div class="user-card-username">{{ '@' . $user->username }}</div>
                        </div>
                        <div>
                            @php
                                $friendship = auth()->user()->friends()->wherePivot('friend_id', $user->id)->first();
                                $receivedRequest = $user->friends()->wherePivot('friend_id', auth()->id())->wherePivot('status', 'pending')->exists();
                            @endphp

                            @if($friendship && $friendship->pivot->status === 'accepted')
                                <span style="color: #10b981;">✓ Friends</span>
                            @elseif($friendship && $friendship->pivot->status === 'pending')
                                <span style="color: #71717a;">Request Sent</span>
                            @elseif($receivedRequest)
                                <form method="POST" action="{{ route('friends.accept', $user->id) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">Accept Request</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('friends.request') }}" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                                    <button type="submit" class="btn btn-primary btn-sm">Add Friend</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p style="color: #71717a; text-align: center; padding: 24px;">No users found matching "{{ $query }}"</p>
                @endforelse
            </div>
        @else
            <div class="empty-state">
                <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <h3>Search for friends</h3>
                <p>Enter a username or display name to find other gamers</p>
            </div>
        @endif
    </div>
</main>
@endsection