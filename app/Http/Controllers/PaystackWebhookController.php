<?php

namespace App\Http\Controllers;

use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request, WebhookService $webhooks): JsonResponse
    {
        return $webhooks->handle('paystack', $request);
    }
}
