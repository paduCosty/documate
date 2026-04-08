<?php

namespace App\Services\Subscription;

/**
 * Thin wrapper around config/plans.php.
 * All plan data (prices, Stripe IDs, features) lives in that config file.
 */
class PlanService
{
    /**
     * All plans as an array ready for the frontend.
     */
    public function getAllPlans(): array
    {
        return array_values(config("plans.plans", []));
    }

    /**
     * Map a plan key like "pro_monthly" or "pro_yearly" to a Stripe Price ID.
     * Returns null if the plan is not found or has no Stripe price configured.
     */
    public function getStripePriceId(string $planKey): ?string
    {
        [$planId, $cycle] = array_pad(explode("_", $planKey, 2), 2, null);

        $plan = config("plans.plans.{$planId}");

        if (!$plan) {
            return null;
        }

        return match ($cycle) {
            "monthly" => $plan["stripe_monthly"] ?? null,
            "yearly"  => $plan["stripe_yearly"]  ?? null,
            default    => null,
        };
    }

    /**
     * Get active plan label and amount for the billing page.
     * Builds the reverse map from config so it stays in sync automatically.
     */
    public function getActivePlanInfo($subscription): array
    {
        if (!$subscription || !$subscription->stripe_price) {
            return ["active_plan" => "Free", "amount" => 0, "currency" => "EUR", "label" => "Free Plan"];
        }

        foreach (config("plans.plans", []) as $planId => $plan) {
            foreach (["monthly", "yearly"] as $cycle) {
                $priceId = $plan["stripe_" . $cycle] ?? null;
                if ($priceId && $priceId === $subscription->stripe_price) {
                    $amount = $cycle === "monthly"
                        ? $plan["price_monthly"] * 100
                        : $plan["price_yearly"]  * 100;
                    $label  = $plan["name"] . " " . ucfirst($cycle);
                    return [
                        "active_plan" => $label,
                        "amount"      => $amount,
                        "currency"    => "EUR",
                        "label"       => $label,
                        "cycle"       => $cycle,
                    ];
                }
            }
        }

        return ["active_plan" => "Pro", "amount" => 0, "currency" => "EUR", "label" => "Pro"];
    }

    /**
     * Get product details for the custom checkout page.
     */
    public function getProductForCheckout(string $planKey): ?array
    {
        [$planId, $cycle] = array_pad(explode("_", $planKey, 2), 2, "monthly");

        $plan = config("plans.plans.{$planId}");
        if (!$plan) {
            return null;
        }

        $price = $cycle === "yearly" ? $plan["price_yearly"] : $plan["price_monthly"];

        return [
            "id"           => $planKey,
            "name"         => $plan["name"],
            "priceInCents" => $price * 100,
            "interval"     => $cycle === "yearly" ? "year" : "month",
            "features"     => $plan["features"],
        ];
    }
}
