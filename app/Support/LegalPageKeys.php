<?php

namespace App\Support;

/**
 * Central registry of every LegalPage "key" — mirrors
 * App\Support\Mail\EmailTemplateKeys's rationale: named constants instead
 * of ad-hoc string literals scattered across the controller, the seeder,
 * and tests.
 */
final class LegalPageKeys
{
    public const string Terms = 'terms';

    public const string Privacy = 'privacy';
}
