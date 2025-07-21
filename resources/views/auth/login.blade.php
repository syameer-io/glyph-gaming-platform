@extends('layouts.app')

@section('title', 'Login - Glyph')

@section('content')
<div class="auth-container">
    <div class="auth-box">
        <div class="logo">
            <h1 class="font-gristela text-5xl">Glyph</h1>
            <p style="color: #b3b3b5; margin-top: 8px;">Welcome back!</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-error">
                @foreach ($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group flex justify-center">
                <button type="submit" class="btn btn-primary">Sign In</button>
            </div>
        </form>

        <p class="text-center mt-4" style="color: #b3b3b5;">
            Don't have an account? <a href="{{ route('register') }}" class="link">Create one</a>
        </p>
    </div>
</div>
@endsection