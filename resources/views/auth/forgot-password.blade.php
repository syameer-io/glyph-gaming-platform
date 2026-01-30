@extends('layouts.app')

@section('title', 'Forgot Password - Glyph')

@section('content')
<div class="auth-container">
    <div class="auth-box">
        <div class="logo">
            {{-- Key icon for password reset --}}
            <div class="logo-accent">
                <svg width="40" height="40" viewBox="0 0 40 40" fill="none">
                    <circle cx="20" cy="16" r="8" stroke="url(#key-gradient)" stroke-width="2" opacity="0.5"/>
                    <path d="M20 24V34M16 30H24" stroke="url(#key-gradient)" stroke-width="2" stroke-linecap="round" opacity="0.5"/>
                    <circle cx="20" cy="16" r="4" fill="url(#key-gradient)" opacity="0.3"/>
                    <defs>
                        <linearGradient id="key-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#667eea"/>
                            <stop offset="100%" stop-color="#764ba2"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <h1>GLYPH</h1>
            <p class="logo-subtitle">Reset your password</p>
        </div>

        <div class="reset-description">
            <p>Enter your email address and we'll send you a link to reset your password.</p>
        </div>

        @if (session('success'))
            <div class="alert alert-success success-message">
                <div class="success-icon">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="success-content">
                    <strong>Check your email</strong>
                    <span>{{ session('success') }}</span>
                </div>
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
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="you@example.com">
                </div>
            </div>

            <div class="form-group" style="margin-top: 28px;">
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

        <div class="form-divider"></div>

        <p class="text-center auth-footer">
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

.logo-accent {
    margin-bottom: 12px;
    display: flex;
    justify-content: center;
}

.reset-description {
    text-align: center;
    margin-bottom: 28px;
}

.reset-description p {
    color: var(--color-text-muted, #71717a);
    font-size: 14px;
    line-height: 1.5;
}

/* Success message styling */
.success-message {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background-color: rgba(16, 185, 129, 0.08);
    border: 1px solid rgba(16, 185, 129, 0.2);
    color: var(--color-text-primary, #efeff1);
}

.success-icon {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    background: rgba(16, 185, 129, 0.15);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #10b981;
}

.success-content {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.success-content strong {
    color: #10b981;
    font-size: 14px;
}

.success-content span {
    color: var(--color-text-secondary, #a1a1aa);
    font-size: 13px;
}

.auth-footer {
    color: var(--color-text-secondary, #a1a1aa);
    font-size: 14px;
}

/* Alert info styling */
.alert-info {
    background-color: rgba(59, 130, 246, 0.08);
    border: 1px solid rgba(59, 130, 246, 0.2);
    color: #60a5fa;
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 16px;
    font-size: 13px;
}

.alert-info a {
    color: #667eea;
    text-decoration: underline;
}

/* Reduced motion preference */
@media (prefers-reduced-motion: reduce) {
    .logo-accent svg {
        animation: none;
    }
}
</style>
@endpush
