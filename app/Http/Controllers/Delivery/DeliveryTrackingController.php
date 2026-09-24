<?php

namespace App\Http\Controllers\Delivery;

use App\Actions\Delivery\AdvanceDeliveryAssignmentAction;
use App\Actions\Shipping\RecordDeliveryEvidenceAction;
use App\Enums\DeliveryAssignmentStatus;
use App\Enums\DeliveryEvidenceType;
use App\Exceptions\InvalidDeliveryAssignmentTransitionException;
use App\Http\Controllers\Controller;
use App\Models\DeliveryAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The no-login page a delivery partner uses to progress their own
 * assignment (pickup -> in transit -> delivered) after accepting it —
 * see App\Actions\Delivery\AdvanceDeliveryAssignmentAction for why this
 * automatically advances the shipment/vendor order in step.
 */
class DeliveryTrackingController extends Controller
{
    public function show(DeliveryAssignment $assignment): View
    {
        $assignment->loadMissing(['shipment.vendorOrder.order', 'evidence']);

        $nextStatuses = AdvanceDeliveryAssignmentAction::nextStatusesFor($assignment->status);

        return view('delivery.tracking', ['assignment' => $assignment, 'nextStatuses' => $nextStatuses]);
    }

    public function advance(Request $request, DeliveryAssignment $assignment): RedirectResponse
    {
        $allowed = AdvanceDeliveryAssignmentAction::nextStatusesFor($assignment->status);

        $data = $request->validate([
            'status' => ['required', Rule::enum(DeliveryAssignmentStatus::class)->only($allowed)],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $newStatus = DeliveryAssignmentStatus::from($data['status']);

        try {
            app(AdvanceDeliveryAssignmentAction::class)->handle($assignment, $newStatus);
        } catch (InvalidDeliveryAssignmentTransitionException $exception) {
            return redirect()->route('deliveries.track', $assignment->tracking_token)->with('error', $exception->getMessage());
        }

        if ($newStatus === DeliveryAssignmentStatus::Delivered && $request->hasFile('photo')) {
            app(RecordDeliveryEvidenceAction::class)->handle(
                $assignment->fresh(),
                DeliveryEvidenceType::Photo,
                $request->file('photo'),
                $data['recipient_name'] ?? null,
            );
        }

        return redirect()->route('deliveries.track', $assignment->tracking_token)->with('status', 'Status updated.');
    }
}
