<?php

namespace App\Http\Controllers;

use App\Http\Middleware\GateMiddleware;
use App\Models\AccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class GateController extends Controller
{
    public function show(): InertiaResponse
    {
        return Inertia::render('gate/page', [
            'status' => session('gate_status'),
        ]);
    }

    public function enterPassword(Request $request): RedirectResponse
    {
        $request->validate(['password' => 'required|string']);

        $configured = config('gate.password');

        if (!$configured || !hash_equals($configured, $request->input('password'))) {
            return back()->withErrors(['password' => 'Incorrect password.'])->withInput();
        }

        return redirect('/')->withCookie(
            cookie()->forever('_documate_gate', GateMiddleware::expectedCookieValue())
        );
    }

    public function requestAccess(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email|max:255']);

        $email = strtolower(trim($request->input('email')));

        // Already approved — resend their access link
        $approved = AccessRequest::where('email', $email)
            ->where('status', 'approved')
            ->latest()
            ->first();

        if ($approved) {
            $this->sendAccessGranted($email, $approved->user_token);
            return back()->with('gate_status', 'link_resent');
        }

        // Already pending
        if (AccessRequest::where('email', $email)->where('status', 'pending')->exists()) {
            return back()->with('gate_status', 'already_pending');
        }

        $accessRequest = AccessRequest::create([
            'email'       => $email,
            'admin_token' => Str::random(64),
            'status'      => 'pending',
        ]);

        $this->sendAccessRequested($accessRequest);

        return back()->with('gate_status', 'request_sent');
    }

    public function approve(string $adminToken): InertiaResponse
    {
        $accessRequest = AccessRequest::where('admin_token', $adminToken)
            ->where('status', 'pending')
            ->firstOrFail();

        $userToken = Str::random(64);

        $accessRequest->update([
            'status'      => 'approved',
            'user_token'  => $userToken,
            'approved_at' => now(),
        ]);

        $this->sendAccessGranted($accessRequest->email, $userToken);

        return Inertia::render('gate/approved', [
            'email' => $accessRequest->email,
        ]);
    }

    public function grantAccess(string $userToken): RedirectResponse
    {
        AccessRequest::where('user_token', $userToken)
            ->where('status', 'approved')
            ->firstOrFail();

        return redirect('/')->withCookie(
            cookie()->forever('_documate_gate', GateMiddleware::expectedCookieValue())
        );
    }

    private function sendAccessRequested(AccessRequest $accessRequest): void
    {
        $approveUrl = url('/gate/approve/' . $accessRequest->admin_token);
        $email      = $accessRequest->email;

        $mailable = new class($email, $approveUrl) extends Mailable {
            use Queueable, SerializesModels;

            public function __construct(
                private string $requesterEmail,
                private string $approveUrl,
            ) {}

            public function envelope(): Envelope
            {
                return new Envelope(subject: 'New Access Request — Documate');
            }

            public function content(): Content
            {
                return new Content(
                    markdown: 'emails.access-requested',
                    with: [
                        'email'      => $this->requesterEmail,
                        'approveUrl' => $this->approveUrl,
                    ],
                );
            }
        };

        Mail::to(config('gate.admin_email'))->send($mailable);
    }

    private function sendAccessGranted(string $email, string $userToken): void
    {
        $accessUrl = url('/gate/access/' . $userToken);

        $mailable = new class($accessUrl) extends Mailable {
            use Queueable, SerializesModels;

            public function __construct(private string $accessUrl) {}

            public function envelope(): Envelope
            {
                return new Envelope(subject: 'Your Documate Access Is Ready');
            }

            public function content(): Content
            {
                return new Content(
                    markdown: 'emails.access-granted',
                    with: ['accessUrl' => $this->accessUrl],
                );
            }
        };

        Mail::to($email)->send($mailable);
    }
}
