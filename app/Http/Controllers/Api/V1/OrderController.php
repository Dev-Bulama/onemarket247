<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Order\CancelOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Support\Api\ApiResponse;
use App\Support\Api\Paginated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    /**
     * Requires auth:sanctum (see routes/api.php) — a guest has no account
     * to list orders "for", only individual orders they hold the link to.
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()->orders()
            ->with('vendorOrders')
            ->orderByDesc('placed_at')
            ->paginate(10);

        return Paginated::response($orders, OrderResource::class);
    }

    /**
     * No auth required: a guest order's public_id UUID is itself the
     * credential (see OrderPolicy::view's docblock) — Gate::forUser is
     * used explicitly rather than Gate::authorize so a guest (null user)
     * and a Sanctum-authenticated customer are both handled correctly
     * without requiring the auth:sanctum middleware here.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        Gate::forUser($request->user('sanctum'))->authorize('view', $order);

        $order->load(['vendorOrders.orderItems', 'payments', 'shippingCountry', 'shippingState', 'shippingCity']);

        return ApiResponse::success(new OrderResource($order));
    }

    public function track(Request $request, Order $order): JsonResponse
    {
        Gate::forUser($request->user('sanctum'))->authorize('view', $order);

        $order->load([
            'vendorOrders.orderItems',
            'vendorOrders.statusHistories',
            'vendorOrders.vendor.store',
            'vendorOrders.shipments.carrier',
            'vendorOrders.shipments.events',
            'vendorOrders.shipments.pickupStation',
        ]);

        return ApiResponse::success(new OrderResource($order));
    }

    public function cancel(Request $request, Order $order, CancelOrderAction $action): JsonResponse
    {
        Gate::forUser($request->user('sanctum'))->authorize('view', $order);

        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $order = $action->handle($order, $data['reason'], $request->user('sanctum'));
        $order->load(['vendorOrders.orderItems', 'payments']);

        return ApiResponse::success(new OrderResource($order));
    }
}
