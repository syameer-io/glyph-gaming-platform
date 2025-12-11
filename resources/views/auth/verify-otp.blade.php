@extends('layouts.app')

@section('title', 'Verify OTP - Glyph')

@section('content')
<div class="auth-container">
    <div class="auth-box">
        <div class="logo">
            <h1 class="font-gristela text-5xl">Glyph</h1>
            <p style="color: var(--color-text-secondary); margin-top: 8px;">Two-Factor Authentication</p>
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

        <div style="text-align: center; margin-bottom: 32px;">
            <div style="width: 80px; height: 80px; margin: 0 auto 16px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <svg width="40" height="40" fill="none" stroke="white" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <p style="color: var(--color-text-secondary);">We've sent a verification code to your email address.<br>Please enter it below to continue.</p>
        </div>

        <form method="POST" action="{{ route('verify.otp') }}" x-data="otpForm()" @submit.prevent="submitForm" id="otp-form">
            @csrf

            <!-- Hidden input for form submission -->
            <input type="hidden" name="otp" x-model="otpValue">

            <div class="form-group">
                <label style="text-align: center; display: block;">Verification Code</label>
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

        <div class="text-center mt-4">
            <!-- Live Countdown Timer -->
            <div x-data="countdownTimer()" style="color: var(--color-text-muted); font-size: 14px; margin-bottom: 16px;">
                <span x-show="!isExpired">
                    Code expires in <span x-text="formattedTime" style="color: #667eea; font-weight: 600;"></span>
                </span>
                <span x-show="isExpired" x-cloak style="color: #ef4444;">
                    Code has expired. Please request a new one.
                </span>
            </div>

            <!-- Resend with Cooldown -->
            <div x-data="resendCooldown()">
                <form method="POST" action="{{ route('resend.otp') }}" @submit="handleResend($event)">
                    @csrf
                    <button
                        type="submit"
                        :disabled="cooldownActive"
                        style="background: none; border: none; cursor: pointer; font-size: 14px; transition: all 0.2s;"
                        :style="cooldownActive ? 'color: #52525b; cursor: not-allowed;' : 'color: #667eea; text-decoration: underline;'"
                    >
                        <span x-show="!cooldownActive">Didn't receive the code? Resend</span>
                        <span x-show="cooldownActive" x-cloak>Resend available in <span x-text="cooldownSeconds"></span>s</span>
                    </button>
                </form>
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
</style>
@endpush
