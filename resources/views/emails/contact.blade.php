@component("mail::message")
# New contact message

**From:** {{ $senderName }} ({{ $senderEmail }})
**Subject:** {{ ucfirst($contactSubject) }}

---

{{ $messageBody }}

@component("mail::button", ["url" => "mailto:" . $senderEmail])
Reply to {{ $senderName }}
@endcomponent

@endcomponent
