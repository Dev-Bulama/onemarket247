<?php

namespace App\Actions\Wallet\Concerns;

use App\Models\Vendor;
use App\Models\VendorWallet;

/**
 * Every wallet mutation must run inside DB::transaction() and lock its
 * VendorWallet row via SELECT ... FOR UPDATE before reading/writing a
 * balance column — the same concurrency-safety shape as Phase 7's
 * LocksWarehouseStock.
 */
trait LocksVendorWallet
{
    private function lockedWallet(Vendor $vendor): VendorWallet
    {
        $wallet = VendorWallet::firstOrCreate(['vendor_id' => $vendor->id]);

        return VendorWallet::whereKey($wallet->id)->lockForUpdate()->firstOrFail();
    }
}
