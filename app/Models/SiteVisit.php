<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per storefront web pageview, recorded by TrackSiteVisit. Insert
 * -only (no updated_at). `country_code` starts null and is filled in later,
 * out of the request path, by the ResolveSiteVisitCountries command — see
 * that command's docblock for why country resolution isn't done inline.
 */
class SiteVisit extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'visitor_id', 'user_id', 'vendor_id', 'path', 'ip_address', 'country_code',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
