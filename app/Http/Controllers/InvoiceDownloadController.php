<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rendered on demand from live order data rather than a stored file —
 * an order's contents never change after placement, so there's nothing
 * to gain from persisting a PDF alongside the invoices row, which exists
 * purely to hold the invoice_number and issued_at.
 *
 * This route carries no auth:{guard} middleware (it must stay reachable by
 * guests holding only the unguessable order link), so — unlike a route
 * behind auth:admin,vendor — nothing ever calls Auth::shouldUse() to flip
 * the request's default guard. Without that, both Gate::authorize()'s user
 * resolution AND Spatie Permission's guard-scoped lookup of 'orders.view'
 * fall back to the config default ('web'), so a logged-in admin or vendor
 * (authenticated only on their own guard) would wrongly be treated as a
 * guest. Mirroring what Illuminate\Auth\Middleware\Authenticate does for a
 * guarded route — find whichever guard actually has a session and make it
 * the default for the rest of this request — fixes both at once.
 */
class InvoiceDownloadController extends Controller
{
    public function __invoke(Order $order): Response
    {
        foreach (['web', 'admin', 'vendor'] as $guard) {
            if (Auth::guard($guard)->check()) {
                Auth::shouldUse($guard);

                break;
            }
        }

        Gate::authorize('view', $order);

        $order->loadMissing(['vendorOrders.orderItems', 'invoice', 'shippingCountry', 'shippingState', 'shippingCity', 'currency']);

        $pdf = Pdf::loadView('pdf.invoice', ['order' => $order]);

        return $pdf->download("{$order->invoice->invoice_number}.pdf");
    }
}
