<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\Disclaimer\ResolveActiveDisclaimerAction;
use App\Actions\Vendor\SubmitVendorApplicationAction;
use App\Enums\AgentStatus;
use App\Enums\DisclaimerTrigger;
use App\Enums\VendorDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\VendorApplicationRequest;
use App\Models\Agent;
use App\Models\City;
use App\Models\Country;
use App\Models\Disclaimer;
use App\Models\State;
use App\Models\VendorSubscriptionPlan;
use App\Support\VisitorIdentifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(Request $request): View
    {
        return view('vendor.onboarding.register', [
            'countries' => Country::orderBy('name')->get(),
            'states' => State::orderBy('name')->get(['id', 'name', 'country_id']),
            'cities' => City::orderBy('name')->get(['id', 'name', 'state_id']),
            'plans' => VendorSubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get(),
            'agents' => Agent::where('status', AgentStatus::Approved)->orderBy('full_name')->get(['id', 'full_name']),
            'pageDisclaimer' => $this->beforeApplicationDisclaimer($request),
        ]);
    }

    public function store(VendorApplicationRequest $request, SubmitVendorApplicationAction $action): RedirectResponse
    {
        if ($this->beforeApplicationDisclaimer($request)) {
            return redirect()->route('vendor.register')
                ->withErrors(['application' => 'Please review and accept the notice before continuing.']);
        }

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

    private function beforeApplicationDisclaimer(Request $request): ?Disclaimer
    {
        $guestIdentifier = $request->attributes->get('visitor_id') ?? VisitorIdentifier::resolve($request);

        return app(ResolveActiveDisclaimerAction::class)->handle(DisclaimerTrigger::BeforeVendorApplication, null, $guestIdentifier);
    }
}
