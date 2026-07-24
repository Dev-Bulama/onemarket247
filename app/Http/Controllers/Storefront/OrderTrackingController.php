<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderTrackingController extends Controller
{
    public function show(Request $request): View
    {
        $order = null;
        $notFound = false;
        $orderNumber = trim((string) $request->string('order_number'));
        $email = trim((string) $request->string('email'));

        if ($orderNumber !== '' && $email !== '') {
            $order = Order::where('order_number', $orderNumber)
                ->where(function ($query) use ($email) {
                    $query->where('guest_email', $email)
                        ->orWhereHas('customer', fn ($q) => $q->where('email', $email));
                })
                ->with(['vendorOrders.vendor', 'vendorOrders.shipments.carrier', 'vendorOrders.shipments.events'])
                ->first();

            $notFound = ! $order;
        }

        return view('storefront.pages.track-order', [
            'order' => $order,
            'notFound' => $notFound,
            'orderNumber' => $orderNumber,
            'email' => $email,
        ]);
    }
}
