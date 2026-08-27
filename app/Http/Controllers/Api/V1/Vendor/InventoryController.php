<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Actions\Inventory\AdjustStockAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WarehouseStockResource;
use App\Models\WarehouseStock;
use App\Support\Api\ApiResponse;
use App\Support\Api\Paginated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vendorId = $request->user()->actingVendorId();

        $stock = WarehouseStock::whereHas('warehouse', fn (Builder $query) => $query->where('vendor_id', $vendorId))
            ->with(['warehouse', 'product'])
            ->paginate(20);

        return Paginated::response($stock, WarehouseStockResource::class);
    }

    public function adjust(Request $request, WarehouseStock $warehouseStock, AdjustStockAction $action): JsonResponse
    {
        $vendorId = $request->user()->actingVendorId();

        abort_unless($warehouseStock->warehouse->vendor_id === $vendorId, 403);

        $data = $request->validate([
            'delta' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $sellable = $warehouseStock->product_id ? $warehouseStock->product : $warehouseStock->variation;

        $updated = $action->handle($warehouseStock->warehouse, $sellable, $data['delta'], $data['reason'], $request->user());

        return ApiResponse::success(new WarehouseStockResource($updated->load(['warehouse', 'product'])));
    }
}
