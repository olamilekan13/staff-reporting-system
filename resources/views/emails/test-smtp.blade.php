<x-mail::message>
# SMTP Configuration Test

Hello {{ $recipient->first_name }},

This is a test email to confirm that your SMTP settings are configured correctly.

If you received this email, your email configuration is working properly.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
