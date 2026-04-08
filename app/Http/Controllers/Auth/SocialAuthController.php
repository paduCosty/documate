<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Whitelist of enabled providers.
     * To add a new provider: add it here + add credentials to config/services.php + .env
     */
    private const PROVIDERS = ['google'];

    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS), 404);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS), 404);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route("login")->with("error", "Social login failed. Please try again.");
        }

        // 1. Find by provider + social_id (returning user who already linked)
        $user = User::where("social_provider", $provider)
                    ->where("social_id", $socialUser->getId())
                    ->first();

        // 2. Find by email (existing account — link it automatically)
        if (!$user) {
            $user = User::where("email", $socialUser->getEmail())->first();
            if ($user) {
                $user->update([
                    "social_provider" => $provider,
                    "social_id"       => $socialUser->getId(),
                ]);
            }
        }

        // 3. Brand-new user — create account
        if (!$user) {
            $user = User::create([
                "name"               => $socialUser->getName() ?? $socialUser->getNickname() ?? "User",
                "email"              => $socialUser->getEmail(),
                "password"           => null,
                "social_provider"    => $provider,
                "social_id"          => $socialUser->getId(),
                "email_verified_at"  => now(),
            ]);
            event(new Registered($user));
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route("dashboard.index"));
    }
}
