<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Actions\Withdrawal\CancelWithdrawalAction;
use App\Actions\Withdrawal\RequestWithdrawalAction;
use App\Enums\WithdrawalStatus;
use App\Exceptions\InsufficientWalletBalanceException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WithdrawalResource;
use App\Models\Vendor;
use App\Models\Withdrawal;
use App\Models\WithdrawalMethod;
use App\Support\Api\ApiResponse;
use App\Support\Api\Paginated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $withdrawals = Withdrawal::where('vendor_id', $request->user()->actingVendorId())
            ->with('withdrawalMethod')
            ->latest()
            ->paginate(20);

        return Paginated::response($withdrawals, WithdrawalResource::class);
    }

    public function addMethod(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bank_name' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $method = WithdrawalMethod::create([
            'vendor_id' => $request->user()->actingVendorId(),
            'bank_name' => $data['bank_name'],
            'account_name' => $data['account_name'],
            'account_number' => $data['account_number'],
            'is_default' => $data['is_default'] ?? false,
        ]);

        return ApiResponse::success([
            'id' => $method->id,
            'bank_name' => $method->bank_name,
            'account_name' => $method->account_name,
            'account_number' => $method->account_number,
            'is_default' => $method->is_default,
        ], status: 201);
    }

    public function store(Request $request, RequestWithdrawalAction $action): JsonResponse
    {
        $vendorId = $request->user()->actingVendorId();

        $data = $request->validate([
            'withdrawal_method_id' => ['required'],
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        $method = WithdrawalMethod::where('vendor_id', $vendorId)->findOrFail($data['withdrawal_method_id']);
        $vendor = Vendor::findOrFail($vendorId);

        try {
            $withdrawal = $action->handle($vendor, $method, $data['amount']);
        } catch (InsufficientWalletBalanceException $e) {
            return ApiResponse::error($e->getMessage(), [], 'INSUFFICIENT_BALANCE');
        }

        return ApiResponse::success(new WithdrawalResource($withdrawal->load('withdrawalMethod')), status: 201);
    }

    public function cancel(Request $request, Withdrawal $withdrawal, CancelWithdrawalAction $action): JsonResponse
    {
        abort_unless($withdrawal->vendor_id === $request->user()->actingVendorId(), 403);
        abort_unless($withdrawal->status === WithdrawalStatus::Pending, 422, 'Only a pending withdrawal can be cancelled.');

        $action->handle($withdrawal);

        return ApiResponse::success(new WithdrawalResource($withdrawal->fresh('withdrawalMethod')));
    }
}
