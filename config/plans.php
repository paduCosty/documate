<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Subscription Plans
    |--------------------------------------------------------------------------
    | Single source of truth for all plan data.
    |
    | - price_monthly / price_yearly : display prices in EUR (must match Stripe)
    | - stripe_monthly / stripe_yearly: Stripe Price IDs from env vars
    |
    | To verify your Price IDs go to Stripe Dashboard → Products.
    | To add a new plan: add an entry here + a checkout route + a frontend page.
    */

    "plans" => [

        "free" => [
            "id"             => "free",
            "name"           => "Free",
            "price_monthly"  => 0,
            "price_yearly"   => 0,
            "stripe_monthly" => null,
            "stripe_yearly"  => null,
            "popular"        => false,
            "features"       => [
                "3 operations/day",
                "10MB max file size",
                "Merge & Compress PDF",
                "Word/Excel/PPT to PDF",
                "No account required",
            ],
        ],

        "pro" => [
            "id"             => "pro",
            "name"           => "Pro",
            // Prices in EUR — must match what is configured in Stripe Dashboard
            "price_monthly"  => 7,        // EUR charged every month
            "price_yearly"   => 67,       // EUR charged once per year (~5.60/mo)
            "stripe_monthly" => env("STRIPE_PRICE_PRO_MONTHLY"),
            "stripe_yearly"  => env("STRIPE_PRICE_PRO_YEARLY"),
            "popular"        => true,
            "features"       => [
                "Unlimited PDF operations per day",
                "Up to 100MB per file",
                "All 7 tools — Merge, Split, Compress, Convert & more",
                "30-day file history",
                "Priority support",
            ],
        ],

        // Add more plans here — one entry = one plan.
        // "business" => [
        //     "id"             => "business",
        //     "name"           => "Business",
        //     "price_monthly"  => 19,
        //     "price_yearly"   => 180,
        //     "stripe_monthly" => env("STRIPE_PRICE_BUSINESS_MONTHLY"),
        //     "stripe_yearly"  => env("STRIPE_PRICE_BUSINESS_YEARLY"),
        //     "popular"        => false,
        //     "features"       => [...],
        // ],

    ],

];
