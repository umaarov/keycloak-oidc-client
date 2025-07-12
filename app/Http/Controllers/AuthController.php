<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function redirectToProvider()
    {
        return Socialite::driver('keycloak')->redirect();
    }

    public function handleProviderCallback()
    {
        try {
            $keycloakUser = Socialite::driver('keycloak')->user();
            $user = User::updateOrCreate(
                [
                    'keycloak_id' => $keycloakUser->getId(),
                ],
                [
                    'name' => $keycloakUser->getName(),
                    'email' => $keycloakUser->getEmail(),
                    'password' => Hash::make(Str::random(24)),
                    'email_verified_at' => now(),
                ]
            );

            Auth::login($user, true);

            return redirect()->intended('/dashboard');
        } catch (Exception $e) {
            report($e);
            return redirect('/login')->with('error', 'Login failed. Please try again.');
        }
    }

    public function logout(Request $request)
    {
        $redirectUri = route('home');
        $keycloakLogoutUrl = Socialite::driver('keycloak')->getLogoutUrl($redirectUri);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect($keycloakLogoutUrl);
    }

}
