<?php

namespace App\Support\Mail;

/**
 * Central registry of every EmailTemplate "key" a notification will look
 * up. Keeping these as named constants (instead of ad-hoc string literals
 * scattered across notifications, the seeder, and tests) means a typo in
 * one place fails to compile-time-match rather than silently never firing.
 */
final class EmailTemplateKeys
{
    public const string VendorApplicationApproved = 'vendor_application_approved';

    public const string VendorApplicationRejected = 'vendor_application_rejected';

    public const string CustomerWelcome = 'customer_welcome';

    public const string OrderConfirmation = 'order_confirmation';

    public const string MarketingSample = 'marketing_sample';
}
