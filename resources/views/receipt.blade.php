@php
    $status = $transaction->status;
    $statusTone = $status === 'success' ? '#059669' : ($status === 'pending' ? '#d97706' : '#e11d48');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $transaction->reference }} · {{ config('app.name') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #0f172a;
            background: #f1f5f9;
            padding: 32px 16px;
        }
        .sheet {
            max-width: 640px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgb(2 6 23 / .08);
            overflow: hidden;
        }
        .head {
            background: #0b2a6b;
            color: #fff;
            padding: 28px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .head h1 { font-size: 20px; font-weight: 800; }
        .head p { font-size: 12px; color: #c7d2fe; margin-top: 2px; }
        .stamp {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            border: 2px solid currentColor;
            border-radius: 8px;
            padding: 6px 12px;
            color: {{ $statusTone }};
        }
        .body { padding: 32px; }
        .status {
            text-align: center;
            margin-bottom: 28px;
        }
        .status .amount { font-size: 36px; font-weight: 800; color: {{ $statusTone }}; }
        .status .label { font-size: 13px; color: #64748b; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; }
        tr { border-bottom: 1px solid #f1f5f9; }
        tr:last-child { border-bottom: none; }
        th, td { padding: 12px 4px; text-align: left; font-size: 14px; vertical-align: top; }
        th { color: #64748b; font-weight: 500; width: 38%; }
        td { font-weight: 600; color: #0f172a; }
        td .sub { font-weight: 400; color: #94a3b8; font-size: 12px; }
        .foot {
            display: flex;
            gap: 12px;
            margin-top: 32px;
        }
        .foot a {
            flex: 1;
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            border-radius: 10px;
            padding: 12px;
        }
        .foot .print { background: #0b2a6b; color: #fff; }
        .foot .back { background: #f1f5f9; color: #334155; }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { box-shadow: none; border-radius: 0; max-width: none; }
            .foot { display: none; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="head">
            <div>
                <h1>{{ config('app.name') }}</h1>
                <p>Wallet funding receipt</p>
            </div>
            <span class="stamp">{{ $status === 'success' ? 'Paid' : $status }}</span>
        </div>

        <div class="body">
            <div class="status">
                <p class="amount">{{ $transaction->amount !== null ? \App\Support\Money::format($transaction->amount) : '—' }}</p>
                <p class="label">{{ $transaction->status === 'success' ? 'Payment successful' : 'Payment '.$transaction->status }}</p>
            </div>

            <table>
                <tr>
                    <th>Reference</th>
                    <td>{{ $transaction->reference }}</td>
                </tr>
                <tr>
                    <th>Gateway reference</th>
                    <td>{{ $transaction->gateway_reference ?: '—' }}</td>
                </tr>
                <tr>
                    <th>Gateway</th>
                    <td>{{ ucfirst((string) $transaction->gateway) }}</td>
                </tr>
                <tr>
                    <th>Payment method</th>
                    <td>{{ ucwords(str_replace('_', ' ', (string) $transaction->payment_method)) }}</td>
                </tr>
                <tr>
                    <th>Currency</th>
                    <td>{{ $transaction->currency ?? \App\Support\Money::symbol() }}</td>
                </tr>
                @if ($transaction->fee !== null)
                    <tr>
                        <th>Gateway fee</th>
                        <td>{{ \App\Support\Money::format($transaction->fee) }}</td>
                    </tr>
                @endif
                <tr>
                    <th>Customer</th>
                    <td>{{ $transaction->user?->name }}<br>
                        <span class="sub">{{ $transaction->user?->email }}</span>
                    </td>
                </tr>
                <tr>
                    <th>Date</th>
                    <td>{{ ($transaction->paid_at ?? $transaction->created_at)?->format('M j, Y g:i A T') }}</td>
                </tr>
                @if ($transaction->payment_status)
                    <tr>
                        <th>Payment status</th>
                        <td>{{ ucwords(str_replace('_', ' ', $transaction->payment_status)) }}</td>
                    </tr>
                @endif
            </table>

            <div class="foot">
                <a href="#" class="print" onclick="window.print(); return false;">Download / Print</a>
                <a href="{{ auth()->user()?->isAdmin() ? route('admin.transactions') : route('wallet.transactions') }}" class="back">Back</a>
            </div>
        </div>
    </div>
</body>
</html>
