@component('mail::message')
# Welcome to Documate, {{ $user->name }}! 🎉

Thanks for signing up. Your account is ready to go.

Here's what you can do on the **free plan**:

- Merge, compress, split, and convert PDF files
- Up to **3 operations per day**
- Files up to **10 MB**
- No watermarks, ever

@component('mail::button', ['url' => config('app.url') . '/dashboard'])
Go to your dashboard
@endcomponent

---

When you're ready to do more, **Documate Pro** gives you unlimited operations, files up to 100 MB, and 30-day file history — for just €7/month.

[See pricing]({{ config('app.url') }}/pricing)

The Documate Team

@endcomponent
