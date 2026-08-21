<x-mail::message>
# Payment received

Hi {{ $transaction->user->name }},

Your wallet has been funded successfully.

**Amount:** {{ \App\Support\Money::format($transaction->amount) }}
**Reference:** {{ $transaction->reference }}
**Gateway:** {{ ucfirst($transaction->gateway) }}
**Method:** {{ $transaction->payment_method }}
**Date:** {{ $transaction->paid_at?->toDayDateTimeString() }}

View your full transaction history anytime:

[View transactions]({{ route('wallet.transactions') }})

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
