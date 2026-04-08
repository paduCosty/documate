<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Social-auth users have no current password
        $rules = [
            "password" => ["required", Password::defaults(), "confirmed"],
        ];
        if (!$user->social_provider) {
            $rules["current_password"] = ["required", "current_password"];
        }

        $validated = $request->validate($rules);

        $user->update(["password" => Hash::make($validated["password"])]);

        return back()->with("status", "password-updated");
    }
}
