<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Profile;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use App\Mail\PasswordResetMail;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users',
            'username' => 'required|string|min:3|max:20|unique:users|regex:/^[a-zA-Z0-9_]+$/',
            'display_name' => 'required|string|min:3|max:30',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'email' => $request->email,
            'username' => $request->username,
            'display_name' => $request->display_name,
            'password' => Hash::make($request->password),
        ]);

        // Create profile
        Profile::create([
            'user_id' => $user->id,
            'avatar_url' => 'https://ui-avatars.com/api/?name=' . urlencode($user->display_name) . '&background=7289DA&color=fff',
            'bio' => '',
            'status' => 'offline',
        ]);

        // Generate and send OTP
        $otp = $this->generateOtp($user);
        $emailMessage = $this->sendOtpEmail($user, $otp);

        session(['otp_user_id' => $user->id]);

        return redirect()->route('verify.otp')->with('success', 'Registration successful! ' . $emailMessage);
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['username' => 'Invalid credentials'])->withInput();
        }

        // Generate and send OTP
        $otp = $this->generateOtp($user);
        $message = $this->sendOtpEmail($user, $otp);

        session(['otp_user_id' => $user->id]);

        return redirect()->route('verify.otp')->with('success', $message);
    }

    public function showVerifyOtp()
    {
        if (!session('otp_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.verify-otp');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $userId = session('otp_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (!$user || $user->otp_code !== $request->otp) {
            return back()->withErrors(['otp' => 'Invalid OTP code']);
        }

        if ($user->otp_expires_at < now()) {
            return back()->withErrors(['otp' => 'OTP has expired. Please login again.']);
        }

        // Clear OTP
        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        // Update profile status
        $user->profile->update(['status' => 'online']);

        // Login user
        Auth::login($user, true);
        session()->forget('otp_user_id');

        // Dispatch Steam refresh job if user has Steam linked and data is stale
        if ($user->steam_id && $user->isSteamDataStale()) {
            \App\Jobs\RefreshSteamDataJob::dispatch($user->id, 'login');
        }

        return redirect()->route('dashboard');
    }

    public function logout()
    {
        $user = Auth::user();
        
        // Update profile status
        if ($user && $user->profile) {
            $user->profile->update(['status' => 'offline']);
        }

        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login');
    }

    public function resendOtp(Request $request)
    {
        $userId = session('otp_user_id');
        
        if (!$userId) {
            return redirect()->route('login')->with('error', 'Session expired. Please try logging in again.');
        }

        $user = User::find($userId);
        
        if (!$user) {
            return redirect()->route('login')->with('error', 'User not found. Please try logging in again.');
        }

        // Generate and send new OTP
        $otp = $this->generateOtp($user);
        $message = $this->sendOtpEmail($user, $otp);

        return redirect()->route('verify.otp')->with('success', 'New code sent! ' . $message);
    }

    private function generateOtp(User $user)
    {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        return $otp;
    }

    /**
     * Send OTP email with proper error handling and logging.
     *
     * @param User $user
     * @param string $otp
     * @return string User-friendly message
     */
    private function sendOtpEmail(User $user, string $otp): string
    {
        try {
            Mail::to($user->email)->send(new OtpMail($otp));

            Log::info('OTP email sent successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'mailer' => config('mail.default'),
            ]);

            return 'Please check your email for the verification code.';

        } catch (\Exception $e) {
            // ALWAYS log the full error for debugging
            Log::error('OTP email failed to send', [
                'user_id' => $user->id,
                'email' => $user->email,
                'mailer' => config('mail.default'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // ONLY expose OTP when BOTH local environment AND debug mode are enabled
            if (config('app.env') === 'local' && config('app.debug') === true) {
                Log::warning('Exposing OTP in local development mode', [
                    'user_id' => $user->id,
                ]);
                return "Email service unavailable. For testing, your code is: {$otp}";
            }

            // Production: Show user-friendly message, OTP is NOT exposed
            return 'Verification code sent. Please check your email (including spam folder).';
        }
    }

    /**
     * Show the forgot password form.
     */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a password reset link to the given email.
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        // Always return success message to prevent email enumeration
        $successMessage = 'If an account exists with that email, you will receive a password reset link shortly.';

        if (!$user) {
            Log::info('Password reset requested for non-existent email', [
                'email' => $request->email,
            ]);
            return back()->with('success', $successMessage);
        }

        // Generate reset token using Laravel's Password facade
        $token = Password::createToken($user);
        $resetUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);

        // Send the reset email
        $this->sendPasswordResetEmail($user, $resetUrl);

        Log::info('Password reset link sent', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return back()->with('success', $successMessage);
    }

    /**
     * Show the password reset form.
     */
    public function showResetPassword(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Reset the user's password.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->update([
                    'password' => Hash::make($password),
                ]);

                Log::info('Password reset successful', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'Your password has been reset. Please login with your new password.');
        }

        // Map Laravel's status to user-friendly messages
        $errorMessages = [
            Password::INVALID_TOKEN => 'This password reset link is invalid or has expired.',
            Password::INVALID_USER => 'We could not find a user with that email address.',
            Password::THROTTLED => 'Please wait before retrying.',
        ];

        return back()->withErrors([
            'email' => $errorMessages[$status] ?? 'Unable to reset password. Please try again.',
        ])->withInput(['email' => $request->email]);
    }

    /**
     * Send password reset email with proper error handling and logging.
     *
     * @param User $user
     * @param string $resetUrl
     * @return string User-friendly message
     */
    private function sendPasswordResetEmail(User $user, string $resetUrl): string
    {
        try {
            Mail::to($user->email)->send(new PasswordResetMail($resetUrl, 60));

            Log::info('Password reset email sent successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'mailer' => config('mail.default'),
            ]);

            return 'Password reset link sent to your email.';

        } catch (\Exception $e) {
            Log::error('Password reset email failed to send', [
                'user_id' => $user->id,
                'email' => $user->email,
                'mailer' => config('mail.default'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // In local development with debug mode, expose the reset URL for testing
            if (config('app.env') === 'local' && config('app.debug') === true) {
                Log::warning('Exposing password reset URL in local development mode', [
                    'user_id' => $user->id,
                    'reset_url' => $resetUrl,
                ]);
                session()->flash('debug_reset_url', $resetUrl);
            }

            return 'Email service encountered an issue, but your reset link was generated.';
        }
    }
}