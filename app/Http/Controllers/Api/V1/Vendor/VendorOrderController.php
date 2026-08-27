<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Actions\Order\CancelVendorOrderAction;
use App\Actions\Order\UpdateVendorOrderStatusAction;
use App\Enums\VendorOrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\VendorOrderResource;
use App\Models\VendorOrder;
use App\Support\Api\ApiResponse;
use App\Support\Api\Paginated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VendorOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vendorId = $request->user()->actingVendorId();

        $orders = VendorOrder::where('vendor_id', $vendorId)
            ->with('order')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->value()))
            ->latest()
            ->paginate(20);

        return Paginated::response($orders, VendorOrderResource::class);
    }

    public function show(Request $request, VendorOrder $vendorOrder): JsonResponse
    {
        $this->assertOwns($request, $vendorOrder);

        $vendorOrder->load(['orderItems', 'order', 'shipments.carrier', 'shipments.events']);

        return ApiResponse::success(new VendorOrderResource($vendorOrder));
    }

    public function updateStatus(Request $request, VendorOrder $vendorOrder, UpdateVendorOrderStatusAction $action): JsonResponse
    {
        $this->assertOwns($request, $vendorOrder);

        $allowed = UpdateVendorOrderStatusAction::nextStatusesFor($vendorOrder->status);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_map(fn ($s) => $s->value, $allowed))],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $vendorOrder = $action->handle($vendorOrder, VendorOrderStatus::from($data['status']), $data['note'] ?? null, $request->user());

        return ApiResponse::success(new VendorOrderResource($vendorOrder->load('orderItems')));
    }

    public function cancel(Request $request, VendorOrder $vendorOrder, CancelVendorOrderAction $action): JsonResponse
    {
        $this->assertOwns($request, $vendorOrder);

        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        try {
            $vendorOrder = $action->handle($vendorOrder, $data['reason'], $request->user());
        } catch (InvalidOrderTransitionException $e) {
            return ApiResponse::error($e->getMessage(), [], 'INVALID_TRANSITION');
        }

        return ApiResponse::success(new VendorOrderResource($vendorOrder->load('orderItems')));
    }

    private function assertOwns(Request $request, VendorOrder $vendorOrder): void
    {
        abort_unless($vendorOrder->vendor_id === $request->user()->actingVendorId(), 403);
    }
}
