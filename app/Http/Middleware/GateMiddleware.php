<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GateMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('gate.enabled', false)) {
            return $next($request);
        }

        $adminEmail = config('gate.admin_email');
        if ($adminEmail && $request->user()?->email === $adminEmail) {
            return $next($request);
        }

        if ($this->hasValidCookie($request)) {
            return $next($request);
        }

        return redirect()->route('gate');
    }

    private function hasValidCookie(Request $request): bool
    {
        $value = $request->cookie('_documate_gate');

        if (!$value) {
            return false;
        }

        return hash_equals($this->expectedCookieValue(), $value);
    }

    public static function expectedCookieValue(): string
    {
        return hash_hmac('sha256', 'gate_access_v1', config('app.key'));
    }
}
