@component('mail::message')
# You're in

Your access to Documate has been approved.

Click the button below to enter. The link works once — after that, your browser remembers you automatically.

@component('mail::button', ['url' => $accessUrl])
Enter Documate
@endcomponent

— Documate
@endcomponent
