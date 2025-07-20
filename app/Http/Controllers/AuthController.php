<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Profile;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        
        try {
            Mail::to($user->email)->send(new OtpMail($otp));
            $message = 'Registration successful! Please check your email for the OTP.';
        } catch (\Exception $e) {
            // For development: show OTP in flash message if email fails
            if (config('app.env') === 'local') {
                $message = "Registration successful! Email failed to send. Your OTP is: {$otp}";
            } else {
                $message = 'Registration successful! Please check your email for the OTP.';
            }
        }

        session(['otp_user_id' => $user->id]);

        return redirect()->route('verify.otp')->with('success', $message);
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
        
        try {
            Mail::to($user->email)->send(new OtpMail($otp));
            $message = 'Please check your email for the OTP.';
        } catch (\Exception $e) {
            // For development: show OTP in flash message if email fails
            if (config('app.env') === 'local') {
                $message = "Email failed to send. Your OTP is: {$otp}";
            } else {
                $message = 'Please check your email for the OTP.';
            }
        }

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
        
        try {
            Mail::to($user->email)->send(new OtpMail($otp));
            $message = 'New verification code sent to your email.';
        } catch (\Exception $e) {
            // For development: show OTP in flash message if email fails
            if (config('app.env') === 'local') {
                $message = "Email failed to send. Your new OTP is: {$otp}";
            } else {
                $message = 'New verification code sent to your email.';
            }
        }

        return redirect()->route('verify.otp')->with('success', $message);
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
}