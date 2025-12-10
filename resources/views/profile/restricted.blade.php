@extends('layouts.app')

@section('title', $user->display_name . ' - Profile')

@section('content')
<x-navbar />

<div class="profile-header">
    <div class="container">
        <div class="profile-info">
            <img src="{{ $user->profile->avatar_url }}" alt="{{ $user->display_name }}" class="profile-avatar">
            <div class="profile-details">
                <h1>{{ $user->display_name }}</h1>
                <p>{{ '@' . $user->username }}</p>
            </div>
            <div style="margin-left: auto;">
                @if(auth()->check() && auth()->id() !== $user->id)
                    @if($isFriend)
                        <form method="POST" action="{{ route('friends.remove', $user->id) }}" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Remove Friend</button>
                        </form>
                    @elseif($friendRequestPending)
                        <button class="btn btn-secondary" disabled>Request Pending</button>
                    @elseif($friendRequestReceived)
                        <form method="POST" action="{{ route('friends.accept', $user->id) }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-success">Accept Request</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('friends.request') }}" style="display: inline;">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <button type="submit" class="btn btn-primary">Add Friend</button>
                        </form>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

<main>
    <div class="container">
        <div style="text-align: center; padding: 80px 20px;">
            <div style="margin-bottom: 24px;">
                <svg width="80" height="80" viewBox="0 0 20 20" fill="currentColor" style="color: #52525b;">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                </svg>
            </div>
            <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 12px; color: var(--color-text-primary);">This Profile is Private</h2>
            <p style="color: var(--color-text-secondary); max-width: 400px; margin: 0 auto 24px;">
                {{ $user->display_name }} has chosen to keep their profile visible to friends only.
                @if(!$isFriend && !$friendRequestPending)
                    Send a friend request to see their full profile.
                @elseif($friendRequestPending)
                    Your friend request is pending.
                @endif
            </p>
        </div>
    </div>
</main>
@endsection
