<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductDigitalFile;
use App\Models\Scopes\BelongsToVendorScope;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Digital product files live on the private "local" disk, never a publicly
 * reachable URL. Access is gated by ProductPolicy::view() so only the
 * owning vendor/staff or an admin with products.view can download a given
 * file — see the product_digital_files migration for why there is no
 * customer-facing entitlement/download here yet (deferred to Phase 12).
 * The product relation is loaded without BelongsToVendorScope because
 * that scope is a query-time isolation filter, not the authorization
 * check; ProductPolicy performs the real ownership check regardless of
 * which guard is authenticated.
 */
class ProductDigitalFileDownloadController extends Controller
{
    public function __invoke(ProductDigitalFile $productDigitalFile): StreamedResponse
    {
        /** @var Product $product */
        $product = $productDigitalFile->product()->withoutGlobalScope(BelongsToVendorScope::class)->firstOrFail();

        Gate::authorize('view', $product);

        return Storage::disk('local')->download($productDigitalFile->file_path, $productDigitalFile->name);
    }
}
