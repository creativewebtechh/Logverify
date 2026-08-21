<?php

return [
    'public_key' => env('PAYSTACK_PUBLIC_KEY'),
    'secret_key' => env('PAYSTACK_SECRET_KEY'),
    'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
    'test_mode' => env('PAYSTACK_TEST_MODE', true),
];
