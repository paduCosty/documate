<?php

namespace App\Services\Subscription;

use Illuminate\Support\Collection;

/**
 * Centralized service for managing subscription plans, pricing and Stripe mappings.
 *
 * This keeps all plan-related data and logic in one place (Single Source of Truth).
 */
class PlanService
{
    /**
     * Return all available plans for the pricing page.
     */
    public function getAllPlans(): array
    {
        return [
            [
                'id'            => 'free',
                'name'          => 'Free',
                'price_monthly' => 0,
                'price_yearly'  => 0,
                'popular'       => false,
                'features'      => [
                    "3 operations/day",
                    "10MB max file size",
                    "Merge & Compress PDF",
                    "Word/Excel/PPT to PDF",
                    "No account required",
                ],
            ],
            [
                'id'            => 'pro',
                'name'          => 'Pro',
                'price_monthly' => 9,        // €9
                'price_yearly'  => 84,       // €7/monthly billed annually
                'popular'       => true,
                'features'      => [
                    "Unlimited PDF operations",
                    "Up to 100MB per file",
                    "All 7 tools + OCR + Sign",
                    "30-day file history",
                    "Priority support"
                ],
            ],
            // [
            //     'id'            => 'business',
            //     'name'          => 'Business',
            //     'price_monthly' => 19,
            //     'price_yearly'  => 180,
            //     'popular'       => false,
            //     'features'      => [
            //         "Everything in Pro",
            //         "Up to 500MB per file",
            //         "AI Chat with PDF",
            //         "Public API access",
            //         "1-year file history",
            //         "Dedicated support"
            //     ],
            // ],
        ];
    }

    /**
     * Map internal plan keys to real Stripe Price IDs.
     */
    public function getStripePriceId(string $planKey): ?string
    {
        $mapping = [
            'pro_monthly'       => 'price_1THNO7PrtZkTUd5yjnEcp7uk',
            'pro_yearly'        => 'price_1THNL3PrtZkTUd5yZskTHj34',
            // 'business_monthly'  => 'price_1XXXXXXXXXXXXXXX',
            // 'business_yearly' => '...',
        ];

        return $mapping[$planKey] ?? null;
    }

    /**
     * Get active plan information for billing page.
     */
    public function getActivePlanInfo($subscription): array
    {
        if (!$subscription || !$subscription->stripe_price) {
            return [
                'active_plan' => 'Free',
                'amount'      => 0,
                'currency'    => 'EUR',
                'label'       => 'Free Plan',
            ];
        }

        $planMap = [
            'price_1THNO7PrtZkTUd5yjnEcp7uk' => [
                'label'    => 'Pro Monthly',
                'amount'   => 900,           // în cenți
                'currency' => 'EUR'
            ],
            'price_1THNL3PrtZkTUd5yZskTHj34' => [
                'label'    => 'Pro Yearly',
                'amount'   => 8400,
                'currency' => 'EUR'
            ],
            // adaugă și pentru Business
        ];

        $data = $planMap[$subscription->stripe_price] ?? [
            'label'    => 'Unknown Plan',
            'amount'   => 0,
            'currency' => 'EUR'
        ];

        return [
            'active_plan' => $data['label'],
            'amount'      => $data['amount'],
            'currency'    => $data['currency'],
            'label'       => $data['label'],
        ];
    }

    /**
     * Get product details for the custom checkout page.
     */
    public function getProductForCheckout(string $plan): ?array
    {
        $products = [
            'pro_monthly' => [
                'id'           => 'pro_monthly',
                'name'         => 'Pro',
                'priceInCents' => 900,
                'interval'     => 'month',
                'features'     => [/* ... */],
            ],
            // ... restul planurilor
        ];

        return $products[$plan] ?? null;
    }
}
