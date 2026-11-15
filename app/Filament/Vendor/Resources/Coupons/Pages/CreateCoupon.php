<?php

namespace App\Filament\Vendor\Resources\Coupons\Pages;

use App\Enums\CouponType;
use App\Filament\Vendor\Resources\Coupons\CouponResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateCoupon extends CreateRecord
{
    protected static string $resource = CouponResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $vendorId = auth()->user()->actingVendorId();

        // A coupon with no vendor_id is a platform-wide one — never let a
        // vendor create one of those by accident (e.g. staff with the
        // permission but no active store assignment right now).
        if ($vendorId === null) {
            throw ValidationException::withMessages(['code' => 'Your account isn\'t linked to an active store right now.']);
        }

        $data['vendor_id'] = $vendorId;

        // 'value' is typed as a plain percentage (1-100) or, for a fixed
        // discount, a major-currency amount (e.g. 5000 for ₦5,000) — only
        // the fixed case needs converting to the minor units the column
        // stores, matching every other price field in this app. The
        // Select's bound state can come back as either the raw string or
        // the enum case itself, so this normalizes before comparing.
        $type = $data['type'] instanceof CouponType ? $data['type'] : CouponType::from($data['type']);

        if ($type === CouponType::Fixed) {
            $data['value'] = (int) round(((float) $data['value']) * 100);
        }

        return $data;
    }
}
