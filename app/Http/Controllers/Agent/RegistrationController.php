<?php

namespace App\Http\Controllers\Agent;

use App\Actions\Agent\SubmitAgentApplicationAction;
use App\Actions\Disclaimer\ResolveActiveDisclaimerAction;
use App\Enums\AgentDocumentType;
use App\Enums\DisclaimerTrigger;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\AgentApplicationRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\Disclaimer;
use App\Models\State;
use App\Support\VisitorIdentifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(Request $request): View
    {
        return view('agent.onboarding.register', [
            'countries' => Country::orderBy('name')->get(),
            'states' => State::orderBy('name')->get(['id', 'name', 'country_id']),
            'cities' => City::orderBy('name')->get(['id', 'name', 'state_id']),
            'pageDisclaimer' => $this->beforeApplicationDisclaimer($request),
        ]);
    }

    public function store(AgentApplicationRequest $request, SubmitAgentApplicationAction $action): RedirectResponse
    {
        if ($this->beforeApplicationDisclaimer($request)) {
            return redirect()->route('agent.apply')
                ->withErrors(['application' => 'Please review and accept the notice before continuing.']);
        }

        $data = $request->safe()->except(['identity_document', 'proof_of_address_document', 'terms']);

        $action->handle($data, [
            AgentDocumentType::Identity->value => $request->file('identity_document'),
            AgentDocumentType::ProofOfAddress->value => $request->file('proof_of_address_document'),
        ]);

        return redirect()->route('agent.apply.submitted');
    }

    private function beforeApplicationDisclaimer(Request $request): ?Disclaimer
    {
        $guestIdentifier = $request->attributes->get('visitor_id') ?? VisitorIdentifier::resolve($request);

        return app(ResolveActiveDisclaimerAction::class)->handle(DisclaimerTrigger::BeforeAgentApplication, null, $guestIdentifier);
    }
}
