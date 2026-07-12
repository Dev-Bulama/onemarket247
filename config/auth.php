<?php

use App\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | "web" is the customer-facing storefront guard. Administrators and
    | vendors authenticate through the dedicated "admin" and "vendor" guards
    | below — see docs/architecture/01-system-architecture.md §3. Each guard
    | uses a ScopedEloquentUserProvider so a credential/session/remember
    | token belonging to a user outside that guard's allowed user_types is
    | never resolved, regardless of which route or panel is hit.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'customers'),
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'customers',
        ],

        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],

        'vendor' => [
            'driver' => 'session',
            'provider' => 'vendors',
        ],

        'sanctum' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | "users" is the unscoped provider used only by the Sanctum API guard,
    | where actor-type separation is enforced via token abilities
    | (customer:*, vendor:*) rather than provider-level scoping — see
    | docs/architecture/08-api-endpoints.md. Every session-based guard uses
    | a "scoped" provider instead.
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', User::class),
        ],

        'admins' => [
            'driver' => 'scoped',
            'model' => env('AUTH_MODEL', User::class),
            'allowed_user_types' => ['super_admin', 'admin', 'staff'],
        ],

        'vendors' => [
            'driver' => 'scoped',
            'model' => env('AUTH_MODEL', User::class),
            'allowed_user_types' => ['vendor_owner', 'vendor_staff'],
        ],

        'customers' => [
            'driver' => 'scoped',
            'model' => env('AUTH_MODEL', User::class),
            'allowed_user_types' => ['customer'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | One broker per guard, all backed by the same password_reset_tokens
    | table — users.email is globally unique across every user_type, so
    | there is no collision risk sharing the table.
    |
    */

    'passwords' => [
        'admins' => [
            'provider' => 'admins',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],

        'vendors' => [
            'provider' => 'vendors',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],

        'customers' => [
            'provider' => 'customers',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the number of seconds before a password confirmation
    | window expires and users are asked to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
