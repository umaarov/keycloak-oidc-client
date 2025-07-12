<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function redirectToProvider(Request $request)
    {
        $clientId = config('services.keycloak.client_id');
        $redirectUri = config('services.keycloak.redirect');
        $baseUrl = config('services.keycloak.base_url');
        $realm = config('services.keycloak.realm');

        $state = Str::random(40);
        $request->session()->put('state', $state);

        $url = "{$baseUrl}/realms/{$realm}/protocol/openid-connect/auth" .
            "?client_id=" . urlencode($clientId) .
            "&redirect_uri=" . urlencode($redirectUri) .
            "&scope=openid email profile" .
            "&response_type=code" .
            "&state=" . urlencode($state);

        return Redirect::to($url);
    }

    public function handleProviderCallback(Request $request)
    {
        try {
            $baseUrl = config('services.keycloak.base_url');
            $realm = config('services.keycloak.realm');
            $tokenUrl = "{$baseUrl}/realms/{$realm}/protocol/openid-connect/token";

            $tokenResponse = Http::asForm()->post($tokenUrl, [
                'grant_type' => 'authorization_code',
                'client_id' => config('services.keycloak.client_id'),
                'client_secret' => config('services.keycloak.client_secret'),
                'redirect_uri' => config('services.keycloak.redirect'),
                'code' => $request->code,
            ]);

            if ($tokenResponse->failed()) {
                throw new Exception('Failed to get access token from Keycloak.');
            }

            $accessToken = $tokenResponse->json('access_token');

            $userInfoUrl = "{$baseUrl}/realms/{$realm}/protocol/openid-connect/userinfo";
            $userInfoResponse = Http::withToken($accessToken)->get($userInfoUrl);

            if ($userInfoResponse->failed()) {
                throw new Exception('Failed to get user info from Keycloak.');
            }

            $keycloakUser = $userInfoResponse->json();

            $user = User::updateOrCreate(
                [
                    'email' => $keycloakUser['email'],
                ],
                [
                    'keycloak_id' => $keycloakUser['sub'],
                    'name' => $keycloakUser['name'],
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
        $redirectUri = route('login');

        $baseUrl = config('services.keycloak.base_url');
        $realm = config('services.keycloak.realm');
        $clientId = config('services.keycloak.client_id');

        $keycloakLogoutUrl = "{$baseUrl}/realms/{$realm}/protocol/openid-connect/logout" .
            "?client_id=" . urlencode($clientId) .
            "&post_logout_redirect_uri=" . urlencode($redirectUri);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect($keycloakLogoutUrl);
    }
}
