<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WalletTransactionResource;
use App\Models\VendorWallet;
use App\Models\VendorWalletTransaction;
use App\Support\Api\ApiResponse;
use App\Support\Api\Money;
use App\Support\Api\Paginated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EarningsController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $wallet = VendorWallet::firstOrCreate(['vendor_id' => $request->user()->actingVendorId()]);

        return ApiResponse::success([
            'pending_balance' => Money::make($wallet->pending_balance),
            'available_balance' => Money::make($wallet->available_balance),
            'reserved_balance' => Money::make($wallet->reserved_balance),
            'withdrawn_balance' => Money::make($wallet->withdrawn_balance),
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $vendorId = $request->user()->actingVendorId();

        $transactions = VendorWalletTransaction::whereHas('wallet', fn (Builder $query) => $query->where('vendor_id', $vendorId))
            ->with('vendorOrder')
            ->latest()
            ->paginate(20);

        return Paginated::response($transactions, WalletTransactionResource::class);
    }
}
