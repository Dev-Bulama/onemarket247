<?php

namespace App\Http\Controllers\Api\V1\Agent;

use App\Actions\Agent\SubmitAgentApplicationAction;
use App\Enums\AgentDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\AgentApplicationRequest;
use App\Http\Resources\Api\V1\AgentApplicationResource;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Public (unauthenticated) entry point for someone applying to become an
 * agent from the mobile app — mirrors App\Http\Controllers\Agent\
 * RegistrationController::store() (the web application form) field-for-
 * field and Action-for-Action, just returning JSON instead of a redirect.
 */
class AgentApplicationController extends Controller
{
    public function store(AgentApplicationRequest $request, SubmitAgentApplicationAction $action): JsonResponse
    {
        $data = $request->safe()->except(['identity_document', 'proof_of_address_document', 'terms']);

        $application = $action->handle($data, [
            AgentDocumentType::Identity->value => $request->file('identity_document'),
            AgentDocumentType::ProofOfAddress->value => $request->file('proof_of_address_document'),
        ]);

        return ApiResponse::success(
            new AgentApplicationResource($application),
            message: 'Your application has been submitted and is pending review. We will email you once a decision is made.',
            status: 201,
        );
    }
}
