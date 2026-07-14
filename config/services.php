<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Login (Phase 3)
    |--------------------------------------------------------------------------
    |
    | Google is fully implemented via Socialite's built-in driver. Facebook
    | uses the same driver/controller and activates automatically once its
    | credentials are set (see SocialAuthController::isConfigured()).
    |
    | Apple ("architecture" only per the Phase 3 brief) is intentionally not
    | wired to a working driver: real Sign in with Apple needs a JWT-signed
    | client secret (private key + key id + team id) and the
    | socialiteproviders/apple package, neither of which are installed here.
    | The route/controller branch exists and returns a clear "not configured"
    | response so the integration point is unambiguous when it's built out.
    |
    */

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
    ],

    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'client_secret' => env('APPLE_CLIENT_SECRET'),
        'redirect' => env('APPLE_REDIRECT_URI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways (Phase 13)
    |--------------------------------------------------------------------------
    |
    | These env values are only the seed source for the payment_gateways
    | table's 'paystack' row (see database/seeders/PaymentGatewaySeeder.php)
    | — App\Services\Payment\PaystackGateway always reads its keys from that
    | encrypted DB row at runtime, never from this config array directly,
    | so an admin can rotate keys via PaymentGatewayResource without a
    | redeploy.
    |
    */

    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
    ],

];
