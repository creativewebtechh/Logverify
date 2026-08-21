<?php

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ReceiptController extends Controller
{
    public function show(Transaction $transaction): View
    {
        $user = auth()->user();

        if ($transaction->user_id !== $user->getAuthIdentifier() && ! $user->isAdmin()) {
            throw new AccessDeniedHttpException('You cannot view this receipt.');
        }

        return view('receipt', ['transaction' => $transaction]);
    }
}
