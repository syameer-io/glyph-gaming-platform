@extends('layouts.app')

@section('title', 'Register - Glyph')

@section('content')
<div class="auth-container">
    <div class="auth-box">
        <div class="logo">
            {{-- Decorative accent --}}
            <div class="logo-accent">
                <svg width="40" height="40" viewBox="0 0 40 40" fill="none">
                    <rect x="8" y="8" width="24" height="24" rx="6" stroke="url(#logo-gradient-reg)" stroke-width="2" opacity="0.5"/>
                    <rect x="14" y="14" width="12" height="12" rx="3" fill="url(#logo-gradient-reg)" opacity="0.3"/>
                    <defs>
                        <linearGradient id="logo-gradient-reg" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#667eea"/>
                            <stop offset="100%" stop-color="#764ba2"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <h1>GLYPH</h1>
            <p class="logo-subtitle">Create your account</p>
        </div>

        {{-- Visual progress indicator --}}
        <div class="progress-indicator">
            <div class="progress-step active">
                <div class="progress-dot"></div>
                <span>Account</span>
            </div>
            <div class="progress-line"></div>
            <div class="progress-step">
                <div class="progress-dot"></div>
                <span>Verify</span>
            </div>
            <div class="progress-line"></div>
            <div class="progress-step">
                <div class="progress-dot"></div>
                <span>Done</span>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-error">
                <ul class="error-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" x-data="registerForm()" @submit="handleSubmit">
            @csrf

            {{-- Account Details Section --}}
            <div class="form-section-title">Account Details</div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-with-icon">
                    <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="you@example.com">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="display_name">Display Name</label>
                    <div class="input-with-icon">
                        <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <input type="text" id="display_name" name="display_name" value="{{ old('display_name') }}" required placeholder="How others see you">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-with-icon">
                    <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <input type="text" id="username" name="username" value="{{ old('username') }}" required placeholder="Choose a unique username">
                </div>
                <span class="form-hint">Letters, numbers and underscores only</span>
            </div>

            <div class="form-divider"></div>

            {{-- Security Section --}}
            <div class="form-section-title">Security</div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-with-icon">
                    <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required placeholder="At least 8 characters" x-model="password" @input="checkPasswordStrength">
                    <button type="button" class="password-toggle" @click="showPassword = !showPassword" tabindex="-1" aria-label="Toggle password visibility">
                        <svg x-show="!showPassword" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showPassword" x-cloak fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>
                {{-- Password strength indicator --}}
                <div class="password-strength" x-show="password.length > 0" x-cloak>
                    <div class="strength-bar">
                        <div class="strength-fill" :style="{ width: strengthPercent + '%' }" :class="strengthClass"></div>
                    </div>
                    <span class="strength-text" :class="strengthClass" x-text="strengthText"></span>
                </div>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <div class="input-with-icon">
                    <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <input :type="showConfirmPassword ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required placeholder="Confirm your password" x-model="confirmPassword">
                    <button type="button" class="password-toggle" @click="showConfirmPassword = !showConfirmPassword" tabindex="-1" aria-label="Toggle password visibility">
                        <svg x-show="!showConfirmPassword" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showConfirmPassword" x-cloak fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>
                {{-- Password match indicator --}}
                <div class="password-match" x-show="confirmPassword.length > 0" x-cloak>
                    <span x-show="password === confirmPassword && confirmPassword.length > 0" class="match-success">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Passwords match
                    </span>
                    <span x-show="password !== confirmPassword && confirmPassword.length > 0" class="match-error">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Passwords don't match
                    </span>
                </div>
            </div>

            <div class="form-group" style="margin-top: 28px;">
                <button type="submit" class="btn btn-primary btn-full" :disabled="isLoading">
                    <span x-show="!isLoading">Create Account</span>
                    <span x-show="isLoading" x-cloak class="btn-loading">
                        <svg class="spinner" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-dasharray="60" stroke-dashoffset="20"/>
                        </svg>
                        Creating account...
                    </span>
                </button>
            </div>
        </form>

        <div class="form-divider"></div>

        <p class="text-center auth-footer">
            Already have an account? <a href="{{ route('login') }}" class="link">Sign in</a>
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script>
function registerForm() {
    return {
        showPassword: false,
        showConfirmPassword: false,
        isLoading: false,
        password: '',
        confirmPassword: '',
        strengthPercent: 0,
        strengthClass: '',
        strengthText: '',

        checkPasswordStrength() {
            const password = this.password;
            let score = 0;

            if (password.length >= 8) score += 25;
            if (password.length >= 12) score += 15;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score += 20;
            if (/\d/.test(password)) score += 20;
            if (/[^a-zA-Z0-9]/.test(password)) score += 20;

            this.strengthPercent = Math.min(score, 100);

            if (score < 30) {
                this.strengthClass = 'weak';
                this.strengthText = 'Weak';
            } else if (score < 60) {
                this.strengthClass = 'fair';
                this.strengthText = 'Fair';
            } else if (score < 80) {
                this.strengthClass = 'good';
                this.strengthText = 'Good';
            } else {
                this.strengthClass = 'strong';
                this.strengthText = 'Strong';
            }
        },

        handleSubmit(e) {
            this.isLoading = true;
            // Form will submit naturally, loading state shows during redirect
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

/* Progress indicator */
.progress-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 28px;
    padding: 0 20px;
}

.progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
}

.progress-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: rgba(63, 63, 70, 0.5);
    border: 2px solid rgba(63, 63, 70, 0.5);
    transition: all 0.3s ease;
}

.progress-step.active .progress-dot {
    background-color: #667eea;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
}

.progress-step.completed .progress-dot {
    background-color: #10b981;
    border-color: #10b981;
}

.progress-step span {
    font-size: 11px;
    font-weight: 500;
    color: var(--color-text-muted, #71717a);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.progress-step.active span {
    color: #667eea;
}

.progress-line {
    flex: 1;
    height: 2px;
    background-color: rgba(63, 63, 70, 0.3);
    max-width: 60px;
    margin-bottom: 18px;
}

/* Error list */
.error-list {
    margin: 0;
    padding-left: 18px;
    font-size: 14px;
}

.error-list li {
    margin-bottom: 4px;
}

.error-list li:last-child {
    margin-bottom: 0;
}

/* Password strength indicator */
.password-strength {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 8px;
}

.strength-bar {
    flex: 1;
    height: 4px;
    background-color: rgba(63, 63, 70, 0.3);
    border-radius: 2px;
    overflow: hidden;
}

.strength-fill {
    height: 100%;
    border-radius: 2px;
    transition: width 0.3s ease, background-color 0.3s ease;
}

.strength-fill.weak { background-color: #ef4444; }
.strength-fill.fair { background-color: #f59e0b; }
.strength-fill.good { background-color: #22c55e; }
.strength-fill.strong { background-color: #10b981; }

.strength-text {
    font-size: 12px;
    font-weight: 500;
    min-width: 50px;
}

.strength-text.weak { color: #ef4444; }
.strength-text.fair { color: #f59e0b; }
.strength-text.good { color: #22c55e; }
.strength-text.strong { color: #10b981; }

/* Password match indicator */
.password-match {
    margin-top: 8px;
}

.match-success,
.match-error {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 500;
}

.match-success { color: #10b981; }
.match-error { color: #ef4444; }

.auth-footer {
    color: var(--color-text-secondary, #a1a1aa);
    font-size: 14px;
}

/* Reduced motion preference */
@media (prefers-reduced-motion: reduce) {
    .strength-fill,
    .progress-dot {
        transition: none;
    }
}
</style>
@endpush
