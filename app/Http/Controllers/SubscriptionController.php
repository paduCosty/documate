<?php

namespace App\Http\Controllers;

use App\Jobs\CompressPdfJob;
use App\Jobs\MergePdfJob;
use App\Jobs\OfficeToPdfJob;
use App\Jobs\PdfToJpgJob;
use App\Jobs\SplitPdfJob;
use App\Models\User;
use App\Models\UserFile;
use App\Services\Guest\GuestService;
use App\Services\Credits\CreditService;
use App\Services\Subscription\PlanService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Cashier\Cashier;

class SubscriptionController extends Controller
{
    public function __construct(
        private PlanService  $planService,
        private CreditService $creditService,
        private GuestService $guests,
    ) {}

    public function index(): \Inertia\Response
    {
        return Inertia::render('pricing/page', [
            'plans'       => $this->planService->getAllPlans(),
            'creditPacks' => $this->creditService->packs(),
        ]);
    }

    // ── Checkout ─────────────────────────────────────────────────────────────

    public function checkout(Request $request, string $plan)
    {
        $priceId = $this->planService->getStripePriceId($plan);
        abort_if(!$priceId, 404, 'Invalid subscription plan.');

        $successUrl = route('billing.success') . '?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl  = route('billing.cancel');

        // Carry the pending file UUID into Stripe metadata (backup if session is lost)
        $pendingUuid = session()->get('pending_file_uuid');

        // ── Authenticated user: use Cashier ───────────────────────────────────
        if ($user = $request->user()) {
            $meta = ['user_id' => $user->id, 'plan' => $plan];
            if ($pendingUuid) $meta['pending_file_uuid'] = $pendingUuid;

            $checkout = $user->newSubscription('default', $priceId)
                ->allowPromotionCodes()
                ->checkout([
                    'success_url' => $successUrl,
                    'cancel_url'  => $cancelUrl,
                    'metadata'    => $meta,
                ]);

            return Inertia::location($checkout->url);
        }

        // ── Guest: create a Stripe Checkout session directly ──────────────────
        $guestId = $this->guests->getGuestId($request) ?? $this->guests->generateGuestId();

        $stripe = Cashier::stripe();

        $meta = ['guest_id' => $guestId, 'plan' => $plan];
        if ($pendingUuid) $meta['pending_file_uuid'] = $pendingUuid;

        $sessionParams = [
            'mode'                  => 'subscription',
            'payment_method_types'  => ['card'],
            'allow_promotion_codes' => true,
            'line_items'            => [['price' => $priceId, 'quantity' => 1]],
            'success_url'           => $successUrl,
            'cancel_url'            => $cancelUrl,
            'metadata'              => $meta,
        ];

        if ($email = $request->user()?->email) {
            $sessionParams['customer_email'] = $email;
        }

        $session = $stripe->checkout->sessions->create($sessionParams);

        return Inertia::location($session->url);
    }

    // ── Success handler ───────────────────────────────────────────────────────

    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');
        abort_if(!$sessionId, 400);

        $user = null;

        try {
            $stripe  = Cashier::stripe();
            $session = $stripe->checkout->sessions->retrieve($sessionId, [
                'expand' => ['subscription.items.data.price', 'customer'],
            ]);

            $guestId     = $session->metadata->guest_id ?? null;
            $userId      = $session->metadata->user_id  ?? null;
            $pendingUuid = session()->pull('pending_file_uuid')
                        ?? ($session->metadata->pending_file_uuid ?? null);

            if ($guestId && !$request->user()) {
                $user = $this->handleGuestSuccess($session, $guestId);
            } elseif ($userId) {
                $user = User::findOrFail($userId);
            } else {
                $user = $request->user();
            }

            if ($user) {
                $this->syncSubscription($user, $session);
            }

            // Dispatch the pending job if the user had a file waiting for payment
            if ($pendingUuid) {
                $file = UserFile::where('uuid', $pendingUuid)->first();
                if ($file && $file->status === 'awaiting_payment') {
                    // Reassign to authenticated user if migrated from guest
                    if ($user && !$file->user_id) {
                        $file->refresh();
                    }
                    $this->dispatchPendingJob($file);

                    return redirect()->route('tools.status', $file->uuid)
                        ->with('success', 'Payment successful! Your file is being processed.');
                }
            }
        } catch (\Exception $e) {
            Log::error('Stripe success handler failed: ' . $e->getMessage());
        }

        return redirect()->route('billing')->with('success', 'Subscription activated successfully!');
    }

    /**
     * Dispatch the correct job for a file that was saved while awaiting payment.
     */
    private function dispatchPendingJob(UserFile $file): void
    {
        $meta    = $file->metadata ?? [];
        $pending = $meta['pending_job'] ?? null;

        if (!$pending) return;

        $inputPaths = $pending['input_paths'];
        $tempPath   = $pending['temp_path'];

        // Validate files still exist on disk
        foreach ($inputPaths as $path) {
            if (!file_exists($path)) {
                Log::error("Pending job file missing: {$path} for UserFile {$file->uuid}");
                $file->update(['status' => 'failed', 'metadata' => array_merge($meta, ['error' => 'Uploaded files expired before payment was completed.'])]);
                return;
            }
        }

        $file->update(['status' => 'pending']);

        match ($file->operation_type) {
            'merge_pdf'    => MergePdfJob::dispatch($file, $inputPaths, $tempPath),
            'compress_pdf' => CompressPdfJob::dispatch($file, $inputPaths[0], $tempPath),
            'split_pdf'    => SplitPdfJob::dispatch($file, $inputPaths[0], $tempPath),
            'pdf-to-jpg'   => PdfToJpgJob::dispatch($file, $inputPaths[0], $tempPath),
            default        => OfficeToPdfJob::dispatch($file, $inputPaths, $tempPath, $file->operation_type),
        };
    }

    /**
     * Create a new user from the Stripe session, migrate guest data, log them in.
     */
    private function handleGuestSuccess($session, string $guestId): User
    {
        $email = is_string($session->customer)
            ? Cashier::stripe()->customers->retrieve($session->customer)->email
            : ($session->customer->email ?? null);

        if (!$email && !$session->customer_email) {
            throw new \Exception('No email available from Stripe session.');
        }

        $email = $email ?? $session->customer_email;

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'              => explode('@', $email)[0],
                'password'          => Hash::make(Str::random(24)),
                'email_verified_at' => now(),
            ]
        );

        if ($session->customer && !$user->stripe_id) {
            $customerId = is_string($session->customer)
                ? $session->customer
                : $session->customer->id;
            $user->forceFill(['stripe_id' => $customerId])->save();
        }

        $this->guests->migrateToUser($guestId, $user);

        Auth::login($user, remember: true);

        return $user;
    }

    private function syncSubscription(User $user, $session): void
    {
        if (!$session->subscription) return;

        $sub  = $session->subscription;
        $item = $sub->items->data[0] ?? null;

        $localSub = $user->subscriptions()->updateOrCreate(
            ['stripe_id' => $sub->id],
            [
                'type'          => 'default',
                'stripe_status' => $sub->status,
                'stripe_price'  => $item?->price->id,
                'quantity'      => $item?->quantity ?? 1,
                'trial_ends_at' => $sub->trial_end
                    ? Carbon::createFromTimestamp($sub->trial_end) : null,
                'ends_at'       => null,
            ]
        );

        if ($item) {
            $localSub->items()->updateOrCreate(
                ['stripe_id' => $item->id],
                [
                    'stripe_product' => is_string($item->price->product)
                        ? $item->price->product
                        : $item->price->product->id,
                    'stripe_price'   => $item->price->id,
                    'quantity'       => $item->quantity ?? 1,
                ]
            );
        }
    }

    // ── Cancel subscription ───────────────────────────────────────────────────

    public function cancel()
    {
        return redirect()->route('billing')->with('info', 'Checkout was cancelled. No charge was made.');
    }

    public function cancelSubscription(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->subscribed('default')) {
            return redirect()->route('billing')->with('error', 'No active subscription found.');
        }

        $user->subscription('default')->cancelNow();

        return redirect()->route('billing')->with('success', 'Subscription cancelled.');
    }

    // ── Billing page ──────────────────────────────────────────────────────────

    public function billing()
    {
        $user         = auth()->user();
        $subscription = $user->subscription('default');
        $activePlan   = $this->planService->getActivePlanInfo($subscription);

        return Inertia::render('dashboard/billing/page', [
            'subscription' => $subscription ? [
                'name'          => $subscription->type,
                'stripe_status' => $subscription->stripe_status,
                'stripe_price'  => $subscription->stripe_price,
                'trial_ends_at' => $subscription->trial_ends_at?->toDateTimeString(),
                'ends_at'       => $subscription->ends_at?->toDateTimeString(),
                'canceled'      => $subscription->canceled(),
                ...$activePlan,
            ] : null,
            'invoices' => $user->invoices(),
            'plans'    => $this->planService->getAllPlans(),
        ]);
    }

    public function showCheckout(Request $request, string $plan)
    {
        $product = $this->planService->getProductForCheckout($plan);
        abort_if(!$product, 404);
        return Inertia::render('checkout/page', ['product' => $product]);
    }
}
