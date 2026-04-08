<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\Dashboard\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $data = $this->dashboardService->getDashboardData($user);

        $isSubscribed = $user->subscribed('default') && !$user->subscription('default')?->canceled();

        return Inertia::render('dashboard/page', [
            'user'                  => $user->only(['name']),
            'stats'                 => $data['stats'] ?? [],
            'recentFiles'           => $data['recentFiles'] ?? [],
            'usage'                 => $data['usage'] ?? [],
            'hasActiveSubscription' => $isSubscribed,
            'creditBalance'         => $user->creditBalance(),
        ]);
    }
}
