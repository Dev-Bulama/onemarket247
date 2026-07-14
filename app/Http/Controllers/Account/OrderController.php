<?php

namespace App\Http\Controllers\Account;

use App\Actions\Order\CancelOrderAction;
use App\Enums\OrderNoteVisibility;
use App\Exceptions\InvalidOrderTransitionException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()->orders()
            ->with('vendorOrders')
            ->orderByDesc('placed_at')
            ->paginate(10);

        return view('account.orders.index', ['orders' => $orders]);
    }

    public function show(Order $order): View
    {
        Gate::authorize('view', $order);

        $order->load([
            'vendorOrders.orderItems',
            'notes' => fn ($query) => $query->where('visibility', OrderNoteVisibility::Customer)->latest(),
            'shippingCountry', 'shippingState', 'shippingCity',
            'invoice',
        ]);

        return view('account.orders.show', ['order' => $order]);
    }

    public function track(Order $order): View
    {
        Gate::authorize('view', $order);

        $order->load(['vendorOrders.vendor', 'vendorOrders.shipments.carrier', 'vendorOrders.shipments.events', 'vendorOrders.shipments.pickupStation']);

        return view('account.orders.track', ['order' => $order]);
    }

    public function cancel(Request $request, Order $order, CancelOrderAction $action): RedirectResponse
    {
        Gate::authorize('view', $order);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        try {
            $action->handle($order, $validated['reason'], $request->user());
        } catch (InvalidOrderTransitionException $e) {
            return back()->withErrors(['cancel' => $e->getMessage()]);
        }

        return redirect()->route('account.orders.show', $order)->with('status', 'order-cancelled');
    }
}
