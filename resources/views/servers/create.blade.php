@extends('layouts.app')

@section('title', 'Create Server - Glyph')

@section('content')
<x-navbar active-section="servers" />

<main>
    <div class="container" style="max-width: 600px;">
        <div class="auth-box" style="margin-top: 48px;">
            <h2 style="text-align: center; margin-bottom: 8px;">Create a Server</h2>
            <p style="text-align: center; color: #b3b3b5; margin-bottom: 32px;">
                Your server is where you and your friends hang out. Make yours and start talking.
            </p>

            @if ($errors->any())
                <div class="alert alert-error">
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('server.store') }}">
                @csrf
                
                <div class="form-group">
                    <label for="name">Server Name</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="My Awesome Server" required autofocus>
                </div>

                <div class="form-group">
                    <label for="description">Description (Optional)</label>
                    <textarea id="description" name="description" rows="3" placeholder="What's your server about?">{{ old('description') }}</textarea>
                </div>

                <div style="margin-top: 32px; padding: 16px; background-color: #0e0e10; border-radius: 8px;">
                    <p style="font-size: 14px; color: #b3b3b5; margin-bottom: 8px;">
                        <strong>When you create this server:</strong>
                    </p>
                    <ul style="font-size: 14px; color: #71717a; margin-left: 20px;">
                        <li>You'll automatically become the Server Admin</li>
                        <li>You can invite friends with a unique invite code</li>
                        <li>You'll have full control over channels and members</li>
                    </ul>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 24px;">Create Server</button>
            </form>
        </div>
    </div>
</main>
@endsection