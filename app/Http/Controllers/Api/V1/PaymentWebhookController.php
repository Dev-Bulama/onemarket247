<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Payment\HandlePaymentWebhookAction;
use App\Exceptions\InvalidWebhookSignatureException;
use App\Http\Controllers\Controller;
use App\Services\Payment\PaystackGateway;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Unauthenticated by design (a gateway can't hold a session/token) and
 * signature-verified instead — see docs/architecture/08-api-endpoints.md
 * "POST /api/v1/webhooks/payments/{gateway}" and
 * docs/architecture/10-security-architecture.md "Payment Security".
 */
class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, string $gateway, HandlePaymentWebhookAction $action): JsonResponse
    {
        if ($gateway !== PaystackGateway::CODE) {
            return ApiResponse::error('Unsupported gateway.', [], 'NOT_FOUND', 404);
        }

        try {
            $action->handle($request);
        } catch (InvalidWebhookSignatureException $e) {
            return ApiResponse::error($e->getMessage(), [], 'INVALID_SIGNATURE', 401);
        }

        return ApiResponse::success(null, [], 'Webhook processed.');
    }
}
