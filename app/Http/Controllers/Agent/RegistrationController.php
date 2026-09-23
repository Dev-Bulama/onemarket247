<?php

namespace App\Http\Controllers\Agent;

use App\Actions\Agent\SubmitAgentApplicationAction;
use App\Enums\AgentDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\AgentApplicationRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(): View
    {
        return view('agent.onboarding.register', [
            'countries' => Country::orderBy('name')->get(),
            'states' => State::orderBy('name')->get(['id', 'name', 'country_id']),
            'cities' => City::orderBy('name')->get(['id', 'name', 'state_id']),
        ]);
    }

    public function store(AgentApplicationRequest $request, SubmitAgentApplicationAction $action): RedirectResponse
    {
        $data = $request->safe()->except(['identity_document', 'proof_of_address_document', 'terms']);

        $action->handle($data, [
            AgentDocumentType::Identity->value => $request->file('identity_document'),
            AgentDocumentType::ProofOfAddress->value => $request->file('proof_of_address_document'),
        ]);

        return redirect()->route('agent.apply.submitted');
    }
}
