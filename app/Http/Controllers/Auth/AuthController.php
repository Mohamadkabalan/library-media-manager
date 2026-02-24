<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'provider' => 'local',
        ]);

        $user->assignRole('member');
        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Welcome to Library & Media Manager!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    // ─── SSO ─────────────────────────────────────────────────────────────────

    public function redirectToProvider(string $provider)
    {
        abort_unless(in_array($provider, ['google', 'github']), 404);
        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback(string $provider)
    {
        abort_unless(in_array($provider, ['google', 'github']), 404);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'SSO failed: ' . $e->getMessage());
        }

        // Guard: Google sometimes returns no email
        if (!$socialUser->getEmail()) {
            return redirect()->route('login')->with('error', 'No email returned from ' . $provider . '. Please try again.');
        }

        try {
            // Check if user already linked this SSO provider
            $user = User::where('provider', $provider)
              ->where('provider_id', $socialUser->getId())
              ->first();

            if (!$user) {
                // Check if email already exists (link SSO to existing account)
                $user = User::where('email', $socialUser->getEmail())->first();

                if ($user) {
                    // Link SSO to existing account
                    $user->update([
                      'provider'       => $provider,
                      'provider_id'    => $socialUser->getId(),
                      'provider_token' => substr($socialUser->token, 0, 1000), // 👈 Truncate safely
                      'avatar'         => $user->avatar ?? $socialUser->getAvatar(),
                    ]);
                } else {
                    // Create brand new user
                    $user = User::create([
                      'name'              => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                      'email'             => $socialUser->getEmail(),
                      'provider'          => $provider,
                      'provider_id'       => $socialUser->getId(),
                      'provider_token'    => substr($socialUser->token, 0, 1000), // 👈 Truncate safely
                      'avatar'            => $socialUser->getAvatar(),
                      'email_verified_at' => now(),
                      'is_active'         => true,
                    ]);

                    // Assign default role
                    $user->assignRole('member');
                }
            } else {
                // User exists — just refresh their token
                $user->update([
                  'provider_token' => substr($socialUser->token, 0, 1000), // 👈 Truncate safely
                  'avatar'         => $socialUser->getAvatar() ?? $user->avatar,
                ]);
            }

            // Check account is active
            if (!$user->is_active) {
                return redirect()->route('login')->with('error', 'Your account has been deactivated.');
            }

            Auth::login($user, true);

            return redirect()->intended(route('dashboard'));

        } catch (\Exception $e) {
            // Log the real error so you can debug
            \Log::error('SSO Login Error: ' . $e->getMessage(), [
              'provider'   => $provider,
              'trace'      => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')->with('error', 'Login failed: ' . $e->getMessage());
        }
    }
}
