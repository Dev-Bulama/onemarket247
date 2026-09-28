<?php

namespace App\Http\Controllers;

use App\Actions\Disclaimer\RecordDisclaimerAcceptanceAction;
use App\Models\Disclaimer;
use App\Support\VisitorIdentifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * One endpoint for dismissing/accepting any disclaimer, regardless of
 * which trigger it targets — see
 * App\Actions\Disclaimer\RecordDisclaimerAcceptanceAction for what
 * "accepted" actually means for each trigger type. No auth required: a
 * guest dismissing a first-visit notice is exactly as valid as a logged
 * in customer accepting one before checkout.
 */
class DisclaimerAcceptanceController extends Controller
{
    public function store(Request $request, Disclaimer $disclaimer): RedirectResponse
    {
        $user = Auth::guard('web')->user();
        $guestIdentifier = $user ? null : ($request->attributes->get('visitor_id') ?? VisitorIdentifier::resolve($request));

        app(RecordDisclaimerAcceptanceAction::class)->handle($disclaimer, $user, $guestIdentifier, $request);

        return redirect()->back();
    }
}
