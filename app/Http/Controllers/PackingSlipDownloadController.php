<?php

namespace App\Http\Controllers;

use App\Models\VendorOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class PackingSlipDownloadController extends Controller
{
    public function __invoke(VendorOrder $vendorOrder): Response
    {
        Gate::authorize('view', $vendorOrder);

        $vendorOrder->loadMissing(['orderItems', 'order', 'packingSlip']);

        $pdf = Pdf::loadView('pdf.packing-slip', ['vendorOrder' => $vendorOrder]);

        return $pdf->download("{$vendorOrder->vendor_order_number}.pdf");
    }
}
