<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\Subscription\PlanService;

/**
 * Controller responsible for handling subscription-related pages and actions.
 *
 * This includes:
 * - Displaying pricing plans
 * - Initiating Stripe checkout
 * - Managing user billing and subscription status
 * - Handling subscription cancellation
 */
class SubscriptionController extends Controller
{
    private PlanService $planService;

    public function __construct(PlanService $planService)
    {
        $this->planService = $planService;
    }

    /**
     * Display the pricing page with all available plans.
     */
    public function index(): Response
    {
        $plans = $this->planService->getAllPlans();

        return Inertia::render('pricing/page', [
            'plans' => $plans,
        ]);
    }

    /**
     * Initiate Stripe Checkout for a specific plan.
     *
     * @param Request $request
     * @param string  $plan  // ex: pro_monthly, pro_yearly, business_monthly
     */
    public function checkout(Request $request, string $plan)
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'You must be logged in to subscribe.');
        }

        $priceId = $this->planService->getStripePriceId($plan);

        if (!$priceId) {
            abort(404, 'Invalid subscription plan.');
        }

        $checkout = $user->newSubscription('default', $priceId)
            ->allowPromotionCodes()
            ->checkout([
                'success_url' => route('billing.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('billing.cancel'),
                'metadata'    => ['user_id' => $user->id],
            ]);

        // For Inertia + Stripe Checkout we must use Inertia::location()
        return Inertia::location($checkout->url);
    }

    /**
     * Handle successful subscription after returning from Stripe.
     */
    public function success(Request $request)
    {
        return redirect()
            ->route('billing')
            ->with([
                'flash' => [
                    'success' => 'Congratulations! Your subscription has been activated successfully.'
                ],
                'justSubscribed' => true,
            ]);
    }

    /**
     * Handle user cancellation from Stripe Checkout (cancel URL).
     */
    public function cancel()
    {
        return Inertia::render('dashboard/billing/page', [
            'cancelled' => true,
        ]);
    }

    /**
     * Cancel the active subscription immediately.
     */
    public function cancelSubscription(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->subscribed('default')) {
            return redirect()
                ->route('billing')
                ->with('error', 'You do not have an active subscription to cancel.');
        }

        $user->subscription('default')->cancelNow();

        return redirect()
            ->route('billing')
            ->with('success', 'Subscription has been canceled immediately. Your access has been revoked.');
    }

    /**
     * Display the main billing / subscription management page.
     */
    public function billing()
    {
        $user = auth()->user();
        $subscription = $user->subscription('default');

        $activePlanInfo = $this->planService->getActivePlanInfo($subscription);

        return Inertia::render('dashboard/billing/page', [
            'subscription' => $subscription ? [
                'name'           => $subscription->name,
                'stripe_status'  => $subscription->stripe_status,
                'stripe_price'   => $subscription->stripe_price,
                'trial_ends_at'  => $subscription->trial_ends_at?->toDateTimeString(),
                'ends_at'        => $subscription->ends_at?->toDateTimeString(),
                'canceled'       => $subscription->canceled(),
                ...$activePlanInfo,                    // active_plan, amount, currency, label etc.
            ] : null,

            'invoices' => $user->invoices(),
            'plans'    => $this->planService->getAllPlans(),
        ]);
    }

    /**
     * Show custom checkout page (if you want a pre-checkout summary before Stripe).
     */
    public function showCheckout(Request $request, string $plan)
    {
        $product = $this->planService->getProductForCheckout($plan);

        if (!$product) {
            abort(404, 'Plan not found.');
        }

        return Inertia::render('checkout/page', [
            'product' => $product,
        ]);
    }
}