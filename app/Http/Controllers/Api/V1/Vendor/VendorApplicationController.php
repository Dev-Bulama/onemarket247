<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Actions\Vendor\SubmitVendorApplicationAction;
use App\Enums\VendorApplicationStatus;
use App\Enums\VendorDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\VendorApplicationRequest;
use App\Http\Resources\Api\V1\VendorApplicationResource;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Public (unauthenticated) entry point for someone applying to become a
 * vendor from the mobile app — mirrors App\Http\Controllers\Vendor\
 * RegistrationController::store() (the web onboarding wizard) field-for-
 * field and Action-for-Action, just returning JSON instead of a redirect.
 */
class VendorApplicationController extends Controller
{
    public function store(VendorApplicationRequest $request, SubmitVendorApplicationAction $action): JsonResponse
    {
        $data = $request->safe()->except([
            'identity_document', 'business_registration_document', 'tax_certificate_document', 'terms',
        ]);
        $data['store_slug'] = $request->storeSlug();

        $application = $action->handle($data, [
            VendorDocumentType::Identity->value => $request->file('identity_document'),
            VendorDocumentType::BusinessRegistration->value => $request->file('business_registration_document'),
            VendorDocumentType::TaxCertificate->value => $request->file('tax_certificate_document'),
        ]);

        $message = $application->status === VendorApplicationStatus::Approved
            ? 'Your application was approved automatically — you can log in to your vendor account now.'
            : 'Your application has been submitted and is pending review. We will email you once a decision is made.';

        return ApiResponse::success(new VendorApplicationResource($application), message: $message, status: 201);
    }
}
