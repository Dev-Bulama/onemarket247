<?php

namespace App\Http\Controllers;

use App\Models\VendorDocument;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Vendor documents live on the private "local" disk (see Phase 5
 * migrations) — never a publicly reachable URL. Access is gated by
 * VendorDocumentPolicy::view() so only the owning vendor/staff or an admin
 * with vendors.view can download a given file.
 */
class VendorDocumentDownloadController extends Controller
{
    public function __invoke(VendorDocument $vendorDocument): StreamedResponse
    {
        Gate::authorize('view', $vendorDocument);

        return Storage::disk('local')->download($vendorDocument->file_path);
    }
}
