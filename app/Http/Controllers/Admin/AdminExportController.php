<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\XlsxExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminExportController extends Controller
{
    /**
     * Streaming CSV export of wallet transactions for accounting/record-keeping.
     */
    public function transactions(Request $request): StreamedResponse
    {
        $query = $this->filteredQuery($request);

        $filename = 'transactions-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, $this->headers());

            $query->latest()->chunkById(500, function ($transactions) use ($handle) {
                foreach ($transactions as $t) {
                    fputcsv($handle, $this->row($t));
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Excel (.xlsx) variant of the same export.
     */
    public function transactionsExcel(Request $request, XlsxExporter $xlsx): StreamedResponse
    {
        $query = $this->filteredQuery($request);

        $filename = 'transactions-'.now()->format('Y-m-d-His').'.xlsx';

        $rows = $query->latest()->lazy()->map(fn (Transaction $t) => $this->row($t));

        return $xlsx->stream($filename, $this->headers(), $rows);
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = Transaction::with('user');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('gateway')) {
            $query->where('gateway', $request->string('gateway'));
        }

        if ($request->filled('paymentStatus')) {
            $query->where('payment_status', $request->string('paymentStatus'));
        }

        if ($request->filled('search')) {
            $term = $request->string('search');
            $query->where(function ($q) use ($term) {
                $q->where('reference', 'like', "%{$term}%")
                    ->orWhere('gateway_reference', 'like', "%{$term}%")
                    ->orWhereHas('user', function ($u) use ($term) {
                        $u->where('name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%");
                    });
            });
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->string('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->string('to'));
        }

        return $query;
    }

    /** @return string[] */
    private function headers(): array
    {
        return [
            'Reference', 'Gateway Reference', 'Customer', 'Email', 'Type', 'Amount',
            'Currency', 'Fee', 'Balance After', 'Status', 'Payment Status', 'Gateway',
            'Payment Method', 'Description', 'Created At',
        ];
    }

    /** @return array<int, mixed> */
    private function row(Transaction $t): array
    {
        return [
            $t->reference,
            $t->gateway_reference ?? '',
            $t->user?->name,
            $t->user?->email,
            $t->type,
            (float) $t->amount,
            $t->currency ?? '',
            $t->fee !== null ? (float) $t->fee : '',
            $t->balance_after !== null ? (float) $t->balance_after : '',
            $t->status,
            $t->payment_status ?? '',
            $t->gateway ?? '',
            $t->payment_method ?? '',
            $t->description ?? '',
            $t->created_at?->toDateTimeString(),
        ];
    }
}
