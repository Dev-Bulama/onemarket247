<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Enums\VendorDocumentStatus;
use App\Enums\VendorDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\VendorDocumentResource;
use App\Models\VendorDocument;
use App\Support\Api\ApiResponse;
use App\Support\Api\Paginated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Post-onboarding document upload — see App\Filament\Vendor\Resources\
 * VendorDocuments\Schemas\VendorDocumentForm / CreateVendorDocument for the
 * exact fields and vendor_id injection this mirrors. VendorDocument's
 * BelongsToVendorScope only auto-filters for the Filament ("vendor" guard)
 * panel, not sanctum-authenticated API requests, so index() filters
 * explicitly, same as every other vendor API controller.
 */
class VendorDocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $documents = VendorDocument::withoutGlobalScopes()
            ->where('vendor_id', $request->user()->actingVendorId())
            ->latest()
            ->paginate(20);

        return Paginated::response($documents, VendorDocumentResource::class);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', VendorDocument::class);

        $data = $request->validate([
            'type' => ['required', Rule::enum(VendorDocumentType::class)],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $vendorId = $request->user()->actingVendorId();
        $path = $request->file('file')->store("vendor-documents/{$vendorId}", 'local');

        $document = VendorDocument::create([
            'vendor_id' => $vendorId,
            'type' => $data['type'],
            'file_path' => $path,
            'status' => VendorDocumentStatus::Pending,
        ]);

        return ApiResponse::success(new VendorDocumentResource($document), status: 201);
    }
}
