<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\Vendor\SubmitVendorApplicationAction;
use App\Enums\VendorDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\VendorApplicationRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\VendorSubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(): View
    {
        return view('vendor.onboarding.register', [
            'countries' => Country::orderBy('name')->get(),
            'states' => State::orderBy('name')->get(['id', 'name', 'country_id']),
            'cities' => City::orderBy('name')->get(['id', 'name', 'state_id']),
            'plans' => VendorSubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(VendorApplicationRequest $request, SubmitVendorApplicationAction $action): RedirectResponse
    {
        $data = $request->safe()->except([
            'identity_document', 'business_registration_document', 'tax_certificate_document', 'terms',
        ]);
        $data['store_slug'] = $request->storeSlug();

        $action->handle($data, [
            VendorDocumentType::Identity->value => $request->file('identity_document'),
            VendorDocumentType::BusinessRegistration->value => $request->file('business_registration_document'),
            VendorDocumentType::TaxCertificate->value => $request->file('tax_certificate_document'),
        ]);

        return redirect()->route('vendor.register.submitted');
    }
}
