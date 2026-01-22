@extends('layouts.app')

@section('title', 'Forgot Password - Glyph')

@section('content')
<div class="auth-container">
    <div class="auth-box">
        <div class="logo">
            <h1 class="font-gristela text-5xl">Glyph</h1>
            <p style="color: var(--color-text-secondary); margin-top: 8px;">Reset your password</p>
        </div>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('debug_reset_url'))
            <div class="alert alert-info" style="word-break: break-all;">
                <strong>Debug Mode:</strong> Reset URL: <a href="{{ session('debug_reset_url') }}">{{ session('debug_reset_url') }}</a>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                @foreach ($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" x-data="forgotPasswordForm()" @submit="handleSubmit">
            @csrf

            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-with-icon">
                    <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="Enter your email address">
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-full" :disabled="isLoading">
                    <span x-show="!isLoading">Send Reset Link</span>
                    <span x-show="isLoading" x-cloak class="btn-loading">
                        <svg class="spinner" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-dasharray="60" stroke-dashoffset="20"/>
                        </svg>
                        Sending...
                    </span>
                </button>
            </div>
        </form>

        <p class="text-center mt-4" style="color: var(--color-text-secondary);">
            Remember your password? <a href="{{ route('login') }}" class="link">Sign in</a>
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script>
function forgotPasswordForm() {
    return {
        isLoading: false,
        handleSubmit(e) {
            this.isLoading = true;
        }
    };
}
</script>
@endpush

@push('styles')
<style>
[x-cloak] { display: none !important; }
.alert-success {
    background-color: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #22c55e;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
}
.alert-info {
    background-color: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.3);
    color: #3b82f6;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
}
.alert-info a {
    color: #667eea;
    text-decoration: underline;
}
</style>
@endpush
