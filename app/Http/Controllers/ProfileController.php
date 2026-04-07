<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty("email")) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return back()->with("status", "profile-updated");
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $settings = $request->validate([
            "email"    => "boolean",
            "weekly"   => "boolean",
            "product"  => "boolean",
            "security" => "boolean",
        ]);

        $request->user()->update(["notification_settings" => $settings]);

        return back()->with("status", "notifications-updated");
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Social-auth users may have no password — skip password check for them
        if (!$user->social_provider) {
            $request->validate([
                "password" => ["required", "current_password"],
            ]);
        } else {
            $request->validate([
                "password" => ["nullable"],
            ]);
        }

        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect("/");
    }
}
