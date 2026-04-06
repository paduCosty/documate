<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\Subscription\PlanService;
use Laravel\Cashier\Cashier;
use Illuminate\Support\Facades\Log;

/**
 * Controller responsible for handling subscription-related pages and actions.
 */
class SubscriptionController extends Controller
{
    private PlanService $planService;

    public function __construct(PlanService $planService)
    {
        $this->planService = $planService;
    }

    public function index(): Response
    {
        $plans = $this->planService->getAllPlans();
        return Inertia::render("pricing/page", ["plans" => $plans]);
    }

    public function checkout(Request $request, string $plan)
    {
        $user = $request->user();

        if (!$user) {
            abort(403, "You must be logged in to subscribe.");
        }

        $priceId = $this->planService->getStripePriceId($plan);

        if (!$priceId) {
            abort(404, "Invalid subscription plan.");
        }

        $checkout = $user->newSubscription("default", $priceId)
            ->allowPromotionCodes()
            ->checkout([
                "success_url" => route("billing.success") . "?session_id={CHECKOUT_SESSION_ID}",
                "cancel_url"  => route("billing.cancel"),
                "metadata"    => ["user_id" => $user->id],
            ]);

        return Inertia::location($checkout->url);
    }

    /**
     * Handle successful payment return from Stripe Checkout.
     * Manually syncs the subscription since webhooks cannot reach local dev servers.
     */
    public function success(Request $request)
    {
        $user = $request->user();
        $sessionId = $request->query("session_id");

        if ($sessionId && $user) {
            try {
                $stripe = Cashier::stripe();

                // Retrieve session with subscription and customer expanded
                $session = $stripe->checkout->sessions->retrieve($sessionId, [
                    "expand" => ["subscription.items.data.price", "customer"],
                ]);

                // Sync customer stripe_id on the user
                if ($session->customer && !$user->stripe_id) {
                    $customerId = is_string($session->customer)
                        ? $session->customer
                        : $session->customer->id;
                    $user->forceFill(["stripe_id" => $customerId])->save();
                }

                // Sync the subscription into our DB
                if ($session->subscription) {
                    $sub = $session->subscription;
                    $item = $sub->items->data[0] ?? null;

                    $localSub = $user->subscriptions()->updateOrCreate(
                        ["stripe_id" => $sub->id],
                        [
                            "type"          => "default",
                            "stripe_status" => $sub->status,
                            "stripe_price"  => $item?->price->id,
                            "quantity"      => $item?->quantity ?? 1,
                            "trial_ends_at" => $sub->trial_end
                                ? \Carbon\Carbon::createFromTimestamp($sub->trial_end)
                                : null,
                            "ends_at"       => null,
                        ]
                    );

                    // Sync subscription item
                    if ($item) {
                        $localSub->items()->updateOrCreate(
                            ["stripe_id" => $item->id],
                            [
                                "stripe_product" => is_string($item->price->product)
                                    ? $item->price->product
                                    : $item->price->product->id,
                                "stripe_price"   => $item->price->id,
                                "quantity"       => $item->quantity ?? 1,
                            ]
                        );
                    }

                    Log::info("Subscription synced from Stripe session", [
                        "user_id"    => $user->id,
                        "stripe_sub" => $sub->id,
                        "status"     => $sub->status,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Failed to sync subscription from Stripe session: " . $e->getMessage());
            }
        }

        return redirect()
            ->route("billing")
            ->with("success", "Subscription activated successfully!");
    }

    public function cancel()
    {
        return Inertia::render("dashboard/billing/page", ["cancelled" => true]);
    }

    public function cancelSubscription(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->subscribed("default")) {
            return redirect()
                ->route("billing")
                ->with("error", "You do not have an active subscription to cancel.");
        }

        $user->subscription("default")->cancelNow();

        return redirect()
            ->route("billing")
            ->with("success", "Subscription has been canceled immediately.");
    }

    public function billing()
    {
        $user = auth()->user();
        $subscription = $user->subscription("default");
        $activePlanInfo = $this->planService->getActivePlanInfo($subscription);

        return Inertia::render("dashboard/billing/page", [
            "subscription" => $subscription ? [
                "name"          => $subscription->type,
                "stripe_status" => $subscription->stripe_status,
                "stripe_price"  => $subscription->stripe_price,
                "trial_ends_at" => $subscription->trial_ends_at?->toDateTimeString(),
                "ends_at"       => $subscription->ends_at?->toDateTimeString(),
                "canceled"      => $subscription->canceled(),
                ...$activePlanInfo,
            ] : null,
            "invoices" => $user->invoices(),
            "plans"    => $this->planService->getAllPlans(),
        ]);
    }

    public function showCheckout(Request $request, string $plan)
    {
        $product = $this->planService->getProductForCheckout($plan);

        if (!$product) {
            abort(404, "Plan not found.");
        }

        return Inertia::render("checkout/page", ["product" => $product]);
    }
}
