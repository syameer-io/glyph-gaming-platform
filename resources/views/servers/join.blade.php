@extends('layouts.app')

@section('title', 'Join Server - Glyph')

@section('content')
<x-navbar active-section="servers" />

<main>
    <div class="container" style="max-width: 600px;">
        <div class="auth-box" style="margin-top: 48px;">
            <h2 style="text-align: center; margin-bottom: 8px;">Join a Server</h2>
            <p style="text-align: center; color: #b3b3b5; margin-bottom: 32px;">
                Enter an invite code to join an existing server
            </p>

            @if (session('error'))
                <div class="alert alert-error">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('server.join.submit') }}">
                @csrf
                
                <div class="form-group">
                    <label for="invite_code">Invite Code</label>
                    <input type="text" id="invite_code" name="invite_code" 
                           placeholder="Enter 8-character code" 
                           maxlength="8" 
                           style="text-transform: uppercase; text-align: center; font-size: 20px; letter-spacing: 2px;"
                           required autofocus>
                </div>

                <div style="margin-top: 24px; padding: 16px; background-color: #0e0e10; border-radius: 8px;">
                    <p style="font-size: 14px; color: #b3b3b5;">
                        <strong>Invite codes look like:</strong> ABCD1234
                    </p>
                    <p style="font-size: 14px; color: #71717a; margin-top: 8px;">
                        Get an invite code from a server admin or member
                    </p>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 24px;">Join Server</button>
            </form>
        </div>
    </div>
</main>
@endsection