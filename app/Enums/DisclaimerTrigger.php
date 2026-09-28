<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Where/how often a disclaimer is shown (Priority 9 item 22). The first
 * three are general, site-wide placements that only differ in how long
 * "seen" is remembered (App\Actions\Disclaimer\ResolveActiveDisclaimerAction);
 * the last three are specific checkpoints in a particular flow, each
 * gated server-side before the corresponding submission is allowed to
 * proceed (checkout, vendor application, agent application).
 */
enum DisclaimerTrigger: string implements HasLabel
{
    case FirstVisit = 'first_visit';
    case OncePerSession = 'once_per_session';
    case OncePerUser = 'once_per_user';
    case BeforeCheckout = 'before_checkout';
    case BeforeVendorApplication = 'before_vendor_application';
    case BeforeAgentApplication = 'before_agent_application';

    public function getLabel(): string
    {
        return match ($this) {
            self::FirstVisit => 'On first visit',
            self::OncePerSession => 'Once per session',
            self::OncePerUser => 'Once per user',
            self::BeforeCheckout => 'Before checkout',
            self::BeforeVendorApplication => 'Before vendor application submission',
            self::BeforeAgentApplication => 'Before agent application submission',
        };
    }

    /**
     * The general, site-wide triggers share one resolution slot (only one
     * of them is ever shown at a time, on any storefront page) — see
     * ResolveActiveDisclaimerAction::forGeneralBrowsing().
     */
    public static function generalTriggers(): array
    {
        return [self::FirstVisit, self::OncePerSession, self::OncePerUser];
    }

    /**
     * Whether "seen" should persist beyond the current PHP session
     * (first_visit/once_per_user and the three checkpoint triggers all
     * mean "don't show this again once accepted"), as opposed to
     * once_per_session which should reappear next session.
     */
    public function isPersistent(): bool
    {
        return $this !== self::OncePerSession;
    }
}
