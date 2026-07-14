<?php

namespace App\Actions\Payment;

use App\Enums\PaymentLogDirection;
use App\Exceptions\InvalidWebhookSignatureException;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\WebhookEvent;
use App\Services\Payment\PaystackGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * "Duplicate callbacks can't duplicate payments" (Phase 13 gate): the
 * unique (gateway, event_id) row is inserted inside the same transaction
 * that processes the webhook, so a replayed delivery hits the constraint
 * and is acknowledged (200, required by gateways) with zero additional
 * side effects — see the webhook_events migration and
 * docs/architecture/10-security-architecture.md "Payment Security".
 * Rather than trust the webhook payload's own status field, this always
 * re-verifies server-to-server via VerifyPaymentAction, which is itself
 * idempotent — so a webhook arriving before, after, or interleaved with
 * the browser callback for the same payment can never double-apply the
 * "paid" side effects.
 */
class HandlePaymentWebhookAction
{
    public function __construct(
        private readonly PaystackGateway $gateway,
        private readonly VerifyPaymentAction $verifyPayment,
    ) {}

    public function handle(Request $request): void
    {
        if (! $this->gateway->verifyWebhookSignature($request)) {
            PaymentLog::create([
                'payment_id' => null,
                'gateway' => PaystackGateway::CODE,
                'direction' => PaymentLogDirection::Error,
                'payload' => ['message' => 'Invalid webhook signature.'],
            ]);

            throw new InvalidWebhookSignatureException('Invalid webhook signature.');
        }

        $payload = $request->json()->all();
        $eventId = $this->gateway->eventIdFromWebhookPayload($payload);

        if ($eventId === null) {
            return;
        }

        DB::transaction(function () use ($payload, $eventId) {
            $webhookEvent = WebhookEvent::firstOrCreate(
                ['gateway' => PaystackGateway::CODE, 'event_id' => $eventId],
                ['payload' => $payload],
            );

            if (! $webhookEvent->wasRecentlyCreated) {
                return;
            }

            $reference = $this->gateway->referenceFromWebhookPayload($payload);
            $payment = $reference ? Payment::where('gateway_reference', $reference)->first() : null;

            PaymentLog::create([
                'payment_id' => $payment?->id,
                'gateway' => PaystackGateway::CODE,
                'direction' => PaymentLogDirection::Webhook,
                'payload' => $payload,
            ]);

            if ($payment) {
                $this->verifyPayment->handle($payment);
            }

            $webhookEvent->update(['processed_at' => now()]);
        });
    }
}
