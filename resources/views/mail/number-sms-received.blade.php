<x-mail::message>
# SMS code received

Hello,

Your SMS verification code for **{{ $order->title }}** has arrived.

**Phone number:** {{ $order->phone_number }}

# {{ $order->sms_code }}

This activation is now complete. You can view the details in your account at any time.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
