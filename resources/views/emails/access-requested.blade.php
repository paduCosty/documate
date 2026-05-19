@component('mail::message')
# New access request

**{{ $email }}** wants access to Documate.

@component('mail::button', ['url' => $approveUrl, 'color' => 'green'])
Approve access
@endcomponent

Clicking approve will send them an access link by email.

If you don't recognise this request, ignore this email.

— Documate
@endcomponent
