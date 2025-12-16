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
}