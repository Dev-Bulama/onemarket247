<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * The single source of truth for resolving a browser's anonymous visitor
 * id — originally just App\Http\Middleware\TrackSiteVisit's own
 * analytics cookie, now also the guest identity
 * App\Actions\Disclaimer\RecordDisclaimerAcceptanceAction keys guest
 * acceptances on. Kept in one place so both features can never disagree
 * on what id "this browser" actually has.
 */
class VisitorIdentifier
{
    public const COOKIE = 'visitor_id';

    public static function resolve(Request $request): string
    {
        return $request->cookie(self::COOKIE) ?? (string) Str::uuid();
    }
}
