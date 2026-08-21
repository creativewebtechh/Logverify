<?php

return [
    'client_key' => env('MONNIFY_API_KEY') ?: env('MONNIFY_CLIENT_KEY'),
    'client_secret' => env('MONNIFY_SECRET_KEY') ?: env('MONNIFY_CLIENT_SECRET'),
    'contract_code' => env('MONNIFY_CONTRACT_CODE'),
    'test_mode' => env('MONNIFY_TEST_MODE', true),

    'base_url' => env('MONNIFY_BASE_URL') ?: (env('MONNIFY_TEST_MODE', true)
        ? 'https://sandbox.monnify.com'
        : 'https://api.monnify.com'),
];
