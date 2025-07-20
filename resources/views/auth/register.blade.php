@extends('layouts.app')

@section('title', 'Register - Glyph')

@section('content')
<div class="auth-container">
    <div class="auth-box">
        <div class="logo">
            <h1>Glyph</h1>
            <p style="color: #b3b3b5; margin-top: 8px;">Create your account</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-error">
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>

            <div class="form-group">
                <label for="display_name">Display Name</label>
                <input type="text" id="display_name" name="display_name" value="{{ old('display_name') }}" required>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="{{ old('username') }}" required>
                <small style="color: #71717a; font-size: 12px;">Letters, numbers and underscores only</small>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
            </div>

            <button type="submit" class="btn btn-primary">Create Account</button>
        </form>

        <p class="text-center mt-4" style="color: #b3b3b5;">
            Already have an account? <a href="{{ route('login') }}" class="link">Sign in</a>
        </p>
    </div>
</div>
@endsection