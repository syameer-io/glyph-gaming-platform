@extends('layouts.app')

@section('title', 'Verify OTP - Glyph')

@section('content')
<div class="auth-container">
    <div class="auth-box">
        <div class="logo">
            <h1>GLYPH</h1>
            <p class="logo-subtitle">Two-Factor Authentication</p>
        </div>

        {{-- Visual progress indicator --}}
        <div class="progress-indicator">
            <div class="progress-step completed">
                <div class="progress-dot">
                    <svg width="8" height="8" viewBox="0 0 12 12" fill="none">
                        <path d="M2 6L5 9L10 3" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <span>Account</span>
            </div>
            <div class="progress-line active"></div>
            <div class="progress-step active">
                <div class="progress-dot"></div>
                <span>Verify</span>
            </div>
            <div class="progress-line"></div>
            <div class="progress-step">
                <div class="progress-dot"></div>
                <span>Done</span>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                @foreach ($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </div>
        @endif

        <div class="otp-hero">
            <div class="otp-icon-container">
                <div class="otp-icon-ring"></div>
                <div class="otp-icon-bg">
                    <svg width="32" height="32" fill="none" stroke="white" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
            <p class="otp-description">
                We've sent a verification code to your email.<br>
                <span class="otp-hint">Please enter it below to continue.</span>
            </p>
        </div>

        <form method="POST" action="{{ route('verify.otp') }}" x-data="otpForm()" @submit.prevent="submitForm" id="otp-form">
            @csrf

            <!-- Hidden input for form submission -->
            <input type="hidden" name="otp" x-model="otpValue">

            <div class="form-group">
                <label class="otp-label">Verification Code</label>
                <div class="otp-input-container">
                    <template x-for="(digit, index) in digits" :key="index">
                        <input
                            type="text"
                            inputmode="numeric"
                            maxlength="1"
                            class="otp-input"
                            :class="{ 'has-value': digit !== '' }"
                            x-model="digits[index]"
                            @input="handleInput(index, $event)"
                            @keydown="handleKeydown(index, $event)"
                            @paste="handlePaste($event)"
                            @focus="$event.target.select()"
                            :data-index="index"
                            :autofocus="index === 0"
                        >
                    </template>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full" :disabled="!isComplete || isSubmitting">
                <span x-show="!isSubmitting">Verify & Continue</span>
                <span x-show="isSubmitting" x-cloak class="btn-loading">
                    <svg class="spinner" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-dasharray="60" stroke-dashoffset="20"/>
                    </svg>
                    Verifying...
                </span>
            </button>
        </form>

        <div class="otp-footer">
            <!-- Live Countdown Timer -->
            <div x-data="countdownTimer()" class="countdown-wrapper">
                <div x-show="!isExpired" class="countdown-active">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Code expires in <strong x-text="formattedTime"></strong></span>
                </div>
                <div x-show="isExpired" x-cloak class="countdown-expired">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>Code has expired. Please request a new one.</span>
                </div>
            </div>

            <div class="form-divider"></div>

            <!-- Resend with Cooldown -->
            <div x-data="resendCooldown()" class="resend-wrapper">
                <form method="POST" action="{{ route('resend.otp') }}" @submit="handleResend($event)">
                    @csrf
                    <button
                        type="submit"
                        :disabled="cooldownActive"
                        class="resend-button"
                        :class="{ 'disabled': cooldownActive }"
                    >
                        <span x-show="!cooldownActive">Didn't receive the code? <strong>Resend</strong></span>
                        <span x-show="cooldownActive" x-cloak>Resend available in <strong x-text="cooldownSeconds + 's'"></strong></span>
                    </button>
                </form>
            </div>

            <!-- Security badge -->
            <div class="security-badge">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span>Your verification is secure and encrypted</span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function otpForm() {
    return {
        digits: ['', '', '', '', '', ''],
        isSubmitting: false,

        get otpValue() {
            return this.digits.join('');
        },

        get isComplete() {
            return this.digits.every(d => d !== '');
        },

        handleInput(index, event) {
            const value = event.target.value;

            // Only allow digits
            if (!/^\d*$/.test(value)) {
                this.digits[index] = '';
                return;
            }

            // Take only last character if multiple entered
            this.digits[index] = value.slice(-1);

            // Auto-advance to next input
            if (value && index < 5) {
                this.$nextTick(() => {
                    const nextInput = document.querySelector(`[data-index="${index + 1}"]`);
                    if (nextInput) nextInput.focus();
                });
            }

            // Auto-submit when complete (with small delay for UX)
            if (this.isComplete) {
                setTimeout(() => {
                    if (this.isComplete && !this.isSubmitting) {
                        this.submitForm();
                    }
                }, 300);
            }
        },

        handleKeydown(index, event) {
            // Handle backspace
            if (event.key === 'Backspace') {
                if (this.digits[index] === '' && index > 0) {
                    // Move to previous input
                    const prevInput = document.querySelector(`[data-index="${index - 1}"]`);
                    if (prevInput) {
                        prevInput.focus();
                        this.digits[index - 1] = '';
                    }
                } else {
                    this.digits[index] = '';
                }
                event.preventDefault();
            }

            // Handle left arrow
            if (event.key === 'ArrowLeft' && index > 0) {
                const prevInput = document.querySelector(`[data-index="${index - 1}"]`);
                if (prevInput) prevInput.focus();
            }

            // Handle right arrow
            if (event.key === 'ArrowRight' && index < 5) {
                const nextInput = document.querySelector(`[data-index="${index + 1}"]`);
                if (nextInput) nextInput.focus();
            }
        },

        handlePaste(event) {
            event.preventDefault();
            const pastedData = (event.clipboardData || window.clipboardData).getData('text');
            const digits = pastedData.replace(/\D/g, '').slice(0, 6).split('');

            digits.forEach((digit, i) => {
                this.digits[i] = digit;
            });

            // Focus the next empty input or last input
            const nextEmptyIndex = this.digits.findIndex(d => d === '');
            const focusIndex = nextEmptyIndex === -1 ? 5 : nextEmptyIndex;
            this.$nextTick(() => {
                const input = document.querySelector(`[data-index="${focusIndex}"]`);
                if (input) input.focus();
            });

            // Auto-submit if complete
            if (this.isComplete) {
                setTimeout(() => {
                    if (this.isComplete && !this.isSubmitting) {
                        this.submitForm();
                    }
                }, 300);
            }
        },

        submitForm() {
            if (!this.isComplete || this.isSubmitting) return;
            this.isSubmitting = true;
            document.getElementById('otp-form').submit();
        }
    };
}

function countdownTimer() {
    return {
        // OTP expires in 10 minutes (600 seconds)
        // Start at ~9 minutes to account for email delivery time
        remainingSeconds: 540,
        isExpired: false,

        get formattedTime() {
            const minutes = Math.floor(this.remainingSeconds / 60);
            const seconds = this.remainingSeconds % 60;
            return `${minutes}:${seconds.toString().padStart(2, '0')}`;
        },

        init() {
            this.startCountdown();
        },

        startCountdown() {
            const interval = setInterval(() => {
                if (this.remainingSeconds <= 0) {
                    this.isExpired = true;
                    clearInterval(interval);
                } else {
                    this.remainingSeconds--;
                }
            }, 1000);
        }
    };
}

function resendCooldown() {
    return {
        cooldownActive: false,
        cooldownSeconds: 60,

        handleResend(event) {
            if (this.cooldownActive) {
                event.preventDefault();
                return;
            }

            // Start cooldown after form submits
            this.startCooldown();
        },

        startCooldown() {
            this.cooldownActive = true;
            this.cooldownSeconds = 60;

            const interval = setInterval(() => {
                if (this.cooldownSeconds <= 0) {
                    this.cooldownActive = false;
                    clearInterval(interval);
                } else {
                    this.cooldownSeconds--;
                }
            }, 1000);
        }
    };
}

// Auto-focus first OTP input on page load
document.addEventListener('DOMContentLoaded', function() {
    const firstInput = document.querySelector('[data-index="0"]');
    if (firstInput) {
        setTimeout(() => firstInput.focus(), 100);
    }
});
</script>
@endpush

@push('styles')
<style>
[x-cloak] { display: none !important; }

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
    display: flex;
    align-items: center;
    justify-content: center;
}

.progress-step.active .progress-dot {
    background-color: #667eea;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
}

.progress-step.completed .progress-dot {
    background-color: #10b981;
    border-color: #10b981;
    width: 18px;
    height: 18px;
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

.progress-step.completed span {
    color: #10b981;
}

.progress-line {
    flex: 1;
    height: 2px;
    background-color: rgba(63, 63, 70, 0.3);
    max-width: 60px;
    margin-bottom: 18px;
}

.progress-line.active {
    background: linear-gradient(90deg, #10b981, rgba(63, 63, 70, 0.3));
}

/* OTP hero section */
.otp-hero {
    text-align: center;
    margin-bottom: 32px;
}

.otp-icon-container {
    position: relative;
    width: 72px;
    height: 72px;
    margin: 0 auto 20px;
}

.otp-icon-ring {
    position: absolute;
    inset: -4px;
    border-radius: 50%;
    border: 2px solid rgba(102, 126, 234, 0.2);
    animation: ringPulse 3s ease-in-out infinite;
}

@keyframes ringPulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.05); opacity: 0.3; }
}

.otp-icon-bg {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
}

.otp-description {
    color: var(--color-text-secondary, #a1a1aa);
    font-size: 14px;
    line-height: 1.6;
}

.otp-hint {
    color: var(--color-text-muted, #71717a);
    font-size: 13px;
}

.otp-label {
    text-align: center;
    display: block;
    margin-bottom: 12px;
}

/* OTP footer */
.otp-footer {
    margin-top: 28px;
}

.countdown-wrapper {
    text-align: center;
    margin-bottom: 16px;
}

.countdown-active {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--color-text-muted, #71717a);
    font-size: 14px;
}

.countdown-active strong {
    color: #667eea;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}

.countdown-expired {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #ef4444;
    font-size: 14px;
}

.resend-wrapper {
    text-align: center;
    margin-bottom: 20px;
}

.resend-button {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
    color: var(--color-text-muted, #71717a);
    transition: color 0.2s ease;
    font-family: inherit;
}

.resend-button:not(.disabled):hover {
    color: var(--color-text-primary, #efeff1);
}

.resend-button strong {
    color: #667eea;
    font-weight: 600;
}

.resend-button.disabled {
    cursor: not-allowed;
    opacity: 0.7;
}

.resend-button.disabled strong {
    color: inherit;
}

/* Security badge */
.security-badge {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    color: var(--color-text-muted, #52525b);
    font-size: 11px;
    padding-top: 16px;
    border-top: 1px solid rgba(63, 63, 70, 0.2);
}

.security-badge svg {
    color: #10b981;
}

/* Reduced motion preference */
@media (prefers-reduced-motion: reduce) {
    .otp-icon-ring {
        animation: none;
    }
    .progress-dot {
        transition: none;
    }
}
</style>
@endpush
