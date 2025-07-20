@extends('layouts.app')

@section('title', 'Verify OTP - Glyph')

@section('content')
<div class="auth-container">
    <div class="auth-box">
        <div class="logo">
            <h1>Glyph</h1>
            <p style="color: #b3b3b5; margin-top: 8px;">Two-Factor Authentication</p>
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
            <div style="width: 80px; height: 80px; margin: 0 auto 16px; background-color: #667eea; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <p style="color: #b3b3b5;">We've sent a verification code to your email address. Please enter it below to continue.</p>
        </div>

        <form method="POST" action="{{ route('verify.otp') }}">
            @csrf
            
            <div class="form-group">
                <label for="otp">Verification Code</label>
                <input type="text" id="otp" name="otp" maxlength="6" placeholder="000000" style="text-align: center; font-size: 24px; letter-spacing: 8px;" required autofocus>
            </div>

            <button type="submit" class="btn btn-primary">Verify & Continue</button>
        </form>

        <div class="text-center mt-4">
            <p style="color: #71717a; font-size: 14px; margin-bottom: 16px;">
                Code expires in 10 minutes
            </p>
            
            <form method="POST" action="{{ route('resend.otp') }}" style="display: inline;">
                @csrf
                <button type="submit" style="background: none; border: none; color: #667eea; text-decoration: underline; cursor: pointer; font-size: 14px;">
                    Didn't receive the code? Resend
                </button>
            </form>
        </div>
    </div>
</div>
@endsection