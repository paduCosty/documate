<?php

namespace App\Http\Controllers;

use App\Mail\ContactMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class ContactController extends Controller
{
    public function send(Request $request): RedirectResponse
    {
        $key = "contact:" . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->with("error", "Too many messages sent. Please wait before trying again.");
        }
        RateLimiter::hit($key, 3600);

        $data = $request->validate([
            "name"    => ["required", "string", "max:100"],
            "email"   => ["required", "email", "max:200"],
            "subject" => ["required", "in:general,billing,technical,feature"],
            "message" => ["required", "string", "min:10", "max:2000"],
        ]);

        Mail::to(config("mail.from.address"))
            ->send(new ContactMail(
                senderName:     $data["name"],
                senderEmail:    $data["email"],
                contactSubject: $data["subject"],
                messageBody:    $data["message"],
            ));

        return back()->with("success", "Message sent! We will get back to you within 24 hours.");
    }
}
