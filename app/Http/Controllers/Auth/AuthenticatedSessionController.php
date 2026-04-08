<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Guest\GuestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status'           => session('status'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();
        return redirect()->intended(route('dashboard.index', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            // How many free ops did this user consume today?
            // Cap at the free tier limit so Pro users don't get an unfair handicap as guests.
            $freeOpsUsed = min(
                $user->todayUsage()->operations_count ?? 0,
                GuestService::OPS_PER_DAY,
            );

            if ($freeOpsUsed > 0) {
                // Create a fresh guest session that inherits today's usage.
                // This prevents the "register → logout → 3 free ops reset" attack.
                $guests  = app(GuestService::class);
                $guestId = $guests->generateGuestId();
                $guests->getOrCreateSession($guestId, $request);

                $guests->todayUsage($guestId)
                    ->update(['operations_count' => $freeOpsUsed]);

                Cookie::queue($guests->makeCookie($guestId));
            }
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
