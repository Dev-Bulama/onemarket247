<?php

namespace App\Filament\Vendor\Resources\Coupons\Pages;

use App\Enums\CouponType;
use App\Filament\Vendor\Resources\Coupons\CouponResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCoupon extends EditRecord
{
    protected static string $resource = CouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $type = $data['type'] instanceof CouponType ? $data['type'] : CouponType::from($data['type']);

        if ($type === CouponType::Fixed && $data['value'] !== null) {
            $data['value'] = $data['value'] / 100;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $type = $data['type'] instanceof CouponType ? $data['type'] : CouponType::from($data['type']);

        if ($type === CouponType::Fixed) {
            $data['value'] = (int) round(((float) $data['value']) * 100);
        }

        return $data;
    }
}
