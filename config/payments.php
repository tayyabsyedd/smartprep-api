<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JazzCash (Mobilink Microfinance Bank)
    |--------------------------------------------------------------------------
    | Filled once the client provides the registered business merchant
    | account. Credentials live in .env — never commit them.
    */
    'jazzcash' => [
        'merchant_id' => env('JAZZCASH_MERCHANT_ID'),
        'password' => env('JAZZCASH_PASSWORD'),
        'integrity_salt' => env('JAZZCASH_INTEGRITY_SALT'),
        'return_url' => env('JAZZCASH_RETURN_URL'),
        'sandbox' => env('JAZZCASH_SANDBOX', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | EasyPaisa (Telenor Microfinance Bank)
    |--------------------------------------------------------------------------
    */
    'easypaisa' => [
        'store_id' => env('EASYPAISA_STORE_ID'),
        'hash_key' => env('EASYPAISA_HASH_KEY'),
        'account_number' => env('EASYPAISA_ACCOUNT_NUMBER'),
        'sandbox' => env('EASYPAISA_SANDBOX', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Manual bank transfer (BR-26 fallback)
    |--------------------------------------------------------------------------
    | Shown to students by BankTransferDriver::initiate().
    */
    'bank' => [
        'bank_name' => env('BANK_NAME'),
        'account_title' => env('BANK_ACCOUNT_TITLE'),
        'account_number' => env('BANK_ACCOUNT_NUMBER'),
        'iban' => env('BANK_IBAN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Play Billing (post-beta, M4)
    |--------------------------------------------------------------------------
    */
    'play_billing' => [
        'service_account_json' => env('PLAY_SERVICE_ACCOUNT_JSON'),
        'package_name' => env('PLAY_PACKAGE_NAME'),
    ],

];
