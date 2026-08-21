<x-mail::message>
# Activation expired

Hello,

The SMS activation for **{{ $order->title }}** expired before a verification code was received.

Your payment of **{{ $order->total }}** has been fully refunded to your wallet.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
