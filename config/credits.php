<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Credit Packs
    |--------------------------------------------------------------------------
    | One-time purchasable credit bundles. Each operation costs 1 credit.
    | price_cents is what Stripe charges (EUR). Add Stripe product IDs
    | via the dashboard and reference them here if you use hosted prices;
    | we generate price_data inline so no Stripe price IDs are required.
    */
    'packs' => [
        'starter' => [
            'id'          => 'starter',
            'name'        => 'Starter',
            'credits'     => 10,
            'price_cents' => 500,       // €5.00
            'price_label' => '€5',
            'per_credit'  => '€0.50',
            'badge'       => null,
        ],
        'value' => [
            'id'          => 'value',
            'name'        => 'Value Pack',
            'credits'     => 30,
            'price_cents' => 1200,      // €12.00
            'price_label' => '€12',
            'per_credit'  => '€0.40',
            'badge'       => 'Popular',
        ],
        'power' => [
            'id'          => 'power',
            'name'        => 'Power Pack',
            'credits'     => 100,
            'price_cents' => 3500,      // €35.00
            'price_label' => '€35',
            'per_credit'  => '€0.35',
            'badge'       => 'Best value',
        ],
    ],

    /*
    | Limits applied to users who have a credit balance but no subscription.
    | They pay per op, so daily op count is not enforced, but file size is
    | slightly relaxed versus the free tier.
    */
    'limits' => [
        'max_file_size_mb'    => 25,
        'operations_per_day'  => 999999,
        'total_bytes_per_day' => 999999 * 1024 * 1024,
    ],
];
