<?php

namespace App\Http\Controllers;

use App\Actions\Location\RecordLocationPingAction;
use App\Actions\Location\UpdateLocationConsentAction;
use App\Enums\Gender;
use App\Exceptions\LocationSharingDisabledException;
use App\Models\Currency;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function dashboard(): View
    {
        return view('account.dashboard');
    }

    public function security(Request $request): View
    {
        return view('account.security', [
            'sessions' => $request->user()->deviceSessions()->orderByDesc('last_used_at')->get(),
            'currentSessionId' => $request->session()->getId(),
            'locationConsent' => $request->user()->locationConsent,
        ]);
    }

    /**
     * The browser's own geolocation API only reports a position while this
     * page is open (see resources/views/account/security.blade.php) — a
     * deliberate, no-native-dependency stand-in for continuous background
     * tracking until a future phase adds a real location SDK to the mobile
     * app (see Priority 8's scope decision on this feature).
     */
    public function updateLocationConsent(Request $request): RedirectResponse
    {
        $data = $request->validate(['enabled' => ['required', 'boolean']]);

        app(UpdateLocationConsentAction::class)->handle($request->user(), $data['enabled']);

        return redirect()->route('account.security')->with('status', $data['enabled'] ? 'location-sharing-enabled' : 'location-sharing-disabled');
    }

    public function reportLocation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            app(RecordLocationPingAction::class)->handle(
                $request->user(),
                $data['latitude'],
                $data['longitude'],
                $data['accuracy'] ?? null,
            );
        } catch (LocationSharingDisabledException) {
            // Sharing was switched off in another tab/device between page
            // load and this submission — silently drop it rather than
            // error, since the toggle itself is the source of truth.
        }

        return redirect()->route('account.security');
    }

    public function editProfile(Request $request): View
    {
        return view('account.profile', [
            'customerProfile' => $request->user()->customerProfile,
            'languages' => Language::where('is_active', true)->orderBy('name')->get(),
            'currencies' => Currency::where('is_active', true)->orderBy('name')->get(),
            'genders' => Gender::cases(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'preferred_language_id' => ['nullable', 'exists:languages,id'],
            'preferred_currency_id' => ['nullable', 'exists:currencies,id'],
            'marketing_opt_in' => ['nullable', 'boolean'],
        ]);

        $request->user()->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
        ]);

        $request->user()->customerProfile()->updateOrCreate([], [
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'preferred_language_id' => $validated['preferred_language_id'] ?? null,
            'preferred_currency_id' => $validated['preferred_currency_id'] ?? null,
            'marketing_opt_in' => $validated['marketing_opt_in'] ?? false,
        ]);

        return redirect()->route('account.profile.edit')->with('status', 'profile-updated');
    }
}
