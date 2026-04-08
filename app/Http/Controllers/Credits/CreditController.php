<?php

namespace App\Http\Controllers\Credits;

use App\Http\Controllers\Controller;
use App\Jobs\CompressPdfJob;
use App\Jobs\MergePdfJob;
use App\Jobs\OfficeToPdfJob;
use App\Jobs\PdfToJpgJob;
use App\Jobs\SplitPdfJob;
use App\Models\User;
use App\Models\UserFile;
use App\Services\Credits\CreditService;
use App\Services\Guest\GuestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Cashier\Cashier;

class CreditController extends Controller
{
    public function __construct(
        private CreditService $credits,
        private GuestService  $guests,
    ) {}

    // ── Checkout ─────────────────────────────────────────────────────────────

    /**
     * Create a Stripe Checkout session in "payment" mode (one-time purchase).
     * Works for both authenticated users and guests.
     */
    public function checkout(Request $request, string $pack)
    {
        $packConfig = $this->credits->getPack($pack);
        abort_if(!$packConfig, 404, 'Unknown credit pack.');

        $successUrl = route('credits.success') . '?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl  = route('pricing');
        $pendingUuid = session()->get('pending_file_uuid');

        $meta = [
            'type'    => 'credits',
            'pack'    => $pack,
            'credits' => $packConfig['credits'],
        ];
        if ($pendingUuid) $meta['pending_file_uuid'] = $pendingUuid;

        if ($user = $request->user()) {
            $meta['user_id'] = $user->id;
        } else {
            $guestId = $this->guests->getGuestId($request) ?? $this->guests->generateGuestId();
            $meta['guest_id'] = $guestId;
        }

        $stripe = Cashier::stripe();

        $sessionParams = [
            'mode'       => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency'     => 'eur',
                    'product_data' => [
                        'name'        => "Documate — {$packConfig['name']} ({$packConfig['credits']} credits)",
                        'description' => "{$packConfig['credits']} PDF operation credits",
                    ],
                    'unit_amount' => $packConfig['price_cents'],
                ],
                'quantity' => 1,
            ]],
            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
            'metadata'    => $meta,
        ];

        // Pre-fill email for logged-in users (guests have no email yet)
        if ($request->user()?->email) {
            $sessionParams['customer_email'] = $request->user()->email;
        }

        $session = $stripe->checkout->sessions->create($sessionParams);

        return Inertia::location($session->url);
    }

    // ── Success ───────────────────────────────────────────────────────────────

    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');
        abort_if(!$sessionId, 400);

        $user = null;

        try {
            $stripe  = Cashier::stripe();
            $session = $stripe->checkout->sessions->retrieve($sessionId);

            // Only process completed payments
            if ($session->payment_status !== 'paid') {
                return redirect()->route('pricing')->with('error', 'Payment not confirmed yet. Please wait a moment and try again.');
            }

            $guestId     = $session->metadata->guest_id   ?? null;
            $userId      = $session->metadata->user_id    ?? null;
            $packId      = $session->metadata->pack        ?? null;
            $creditAmt   = (int) ($session->metadata->credits ?? 0);
            $pendingUuid = session()->pull('pending_file_uuid')
                        ?? ($session->metadata->pending_file_uuid ?? null);

            // Resolve the user (create from guest if needed)
            if ($guestId && !$request->user()) {
                $user = $this->createUserFromGuest($session, $guestId);
            } elseif ($userId) {
                $user = User::findOrFail($userId);
            } else {
                $user = $request->user();
            }

            abort_if(!$user, 400, 'Could not resolve user account.');

            // Add credits (idempotent via stripe_session_id)
            $pack = $this->credits->getPack($packId ?? '');
            $desc = $pack ? "{$pack['name']} ({$creditAmt} credits)" : "{$creditAmt} credits";
            $added = $this->credits->addCredits($user, $creditAmt, $desc, $sessionId);

            if (!$added) {
                // Already processed — redirect without error
                return redirect()->route('dashboard')->with('info', 'Credits already applied to your account.');
            }

            // If the user had a file waiting, dispatch it now
            if ($pendingUuid) {
                $file = UserFile::where('uuid', $pendingUuid)->first();
                if ($file && $file->status === 'awaiting_payment') {
                    $file->refresh(); // pick up migrated user_id
                    $this->dispatchPendingJob($file);

                    return redirect()->route('tools.status', $file->uuid)
                        ->with('success', "{$creditAmt} credits added! Your file is being processed.");
                }
            }

            return redirect()->route('dashboard')
                ->with('success', "{$creditAmt} credits added to your account!");

        } catch (\Exception $e) {
            Log::error('Credit success handler failed: ' . $e->getMessage());
            return redirect()->route('pricing')->with('error', 'Something went wrong. Please contact support.');
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Create a new user account from a guest Stripe session, migrate data, log in. */
    private function createUserFromGuest($session, string $guestId): User
    {
        // Resolve email from Stripe customer object or customer_email field
        $email = null;
        if ($session->customer) {
            $stripe   = Cashier::stripe();
            $customer = is_string($session->customer)
                ? $stripe->customers->retrieve($session->customer)
                : $session->customer;
            $email = $customer->email ?? null;
        }
        $email = $email ?? $session->customer_email ?? null;

        if (!$email) {
            throw new \Exception('No email available from Stripe session.');
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'              => explode('@', $email)[0],
                'password'          => Hash::make(Str::random(24)),
                'email_verified_at' => now(),
            ]
        );

        // Migrate all guest files + usage to the new account
        $this->guests->migrateToUser($guestId, $user);

        Auth::login($user, remember: true);

        return $user;
    }

    /** Dispatch the correct job for a file saved in "awaiting_payment" state. */
    private function dispatchPendingJob(UserFile $file): void
    {
        $meta    = $file->metadata ?? [];
        $pending = $meta['pending_job'] ?? null;

        if (!$pending) return;

        $inputPaths = $pending['input_paths'];
        $tempPath   = $pending['temp_path'];

        foreach ($inputPaths as $path) {
            if (!file_exists($path)) {
                Log::error("Pending file missing on disk: {$path} (UserFile {$file->uuid})");
                $file->update(['status' => 'failed', 'metadata' => array_merge($meta, [
                    'error' => 'Uploaded files expired before payment was completed.',
                ])]);
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
}
