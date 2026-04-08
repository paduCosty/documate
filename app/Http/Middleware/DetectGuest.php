<?php

namespace App\Http\Middleware;

use App\Services\Guest\GuestService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class DetectGuest
{
    public function __construct(private GuestService $guests) {}

    public function handle(Request $request, Closure $next): Response
    {
        $guestId = $this->guests->getGuestId($request);

        // Authenticated user carrying a guest cookie → migrate then clear cookie
        if ($request->user() && $guestId) {
            $this->guests->migrateToUser($guestId, $request->user());
            Cookie::queue(Cookie::forget(GuestService::COOKIE_NAME));
            return $next($request);
        }

        // Unauthenticated: ensure a valid guest_id cookie exists
        if (!$request->user()) {
            if (!$guestId) {
                $guestId = $this->guests->generateGuestId();
            }

            $this->guests->getOrCreateSession($guestId, $request);
            $this->guests->touchSession($guestId);

            // Queue the cookie — works for ALL response types including BinaryFileResponse
            Cookie::queue($this->guests->makeCookie($guestId));
        }

        return $next($request);
    }
}
