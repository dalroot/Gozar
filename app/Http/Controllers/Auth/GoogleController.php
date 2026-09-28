<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class GoogleController extends Controller
{
    /**
     * Redirect user to Google OAuth consent screen.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle callback from Google OAuth.
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            if (!$googleUser || !$googleUser->getEmail()) {
                return redirect()->route('login')->withErrors(['email' => 'خطا در دریافت اطلاعات از حساب گوگل.']);
            }

            // Find existing user by google_id or email
            $user = User::where('google_id', $googleUser->getId())
                        ->orWhere('email', $googleUser->getEmail())
                        ->first();

            if ($user) {
                // Update google_id if not linked yet
                if (empty($user->google_id)) {
                    $user->google_id = $googleUser->getId();
                    $user->save();
                }

                Auth::login($user, true);
                request()->session()->regenerate();
                return redirect()->intended('/dashboard');
            } else {
                // Create new user instantly
                $newUser = User::create([
                    'name' => $googleUser->getName() ?? $googleUser->getNickname() ?? 'کاربر گوگل',
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => Hash::make(Str::random(24)),
                    'email_verified_at' => now(),
                ]);

                Auth::login($newUser, true);
                request()->session()->regenerate();
                return redirect()->intended('/dashboard');
            }
        } catch (Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'خطا در احراز هویت با گوگل. لطفاً مجدداً تلاش کنید.']);
        }
    }
}
