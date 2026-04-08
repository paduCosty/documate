<?php

namespace App\Services\Credits;

use App\Models\CreditTransaction;
use App\Models\User;
use App\Models\UserCredit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditService
{
    /**
     * Atomically add credits to a user's balance.
     * Uses stripe_session_id as an idempotency key — safe to call from both
     * the success-URL handler and a Stripe webhook without double-crediting.
     */
    public function addCredits(
        User    $user,
        int     $amount,
        string  $description,
        ?string $stripeSessionId = null,
    ): bool {
        // Idempotency check — skip if this Stripe session already credited
        if ($stripeSessionId) {
            $exists = CreditTransaction::where('stripe_session_id', $stripeSessionId)->exists();
            if ($exists) {
                Log::info("Credits already applied for session {$stripeSessionId}");
                return false;
            }
        }

        DB::transaction(function () use ($user, $amount, $description, $stripeSessionId) {
            // Ensure the row exists, then increment atomically
            UserCredit::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'total_purchased' => 0],
            );

            DB::table('user_credits')
                ->where('user_id', $user->id)
                ->update([
                    'balance'         => DB::raw("balance + {$amount}"),
                    'total_purchased' => DB::raw("total_purchased + {$amount}"),
                ]);

            CreditTransaction::create([
                'user_id'           => $user->id,
                'amount'            => $amount,
                'type'              => 'purchase',
                'description'       => $description,
                'stripe_session_id' => $stripeSessionId,
            ]);
        });

        // Refresh the cached relation so the caller sees the new balance
        $user->unsetRelation('credits');

        return true;
    }

    /** All available packs from config. */
    public function packs(): array
    {
        return config('credits.packs', []);
    }

    public function getPack(string $id): ?array
    {
        return config("credits.packs.{$id}");
    }
}
