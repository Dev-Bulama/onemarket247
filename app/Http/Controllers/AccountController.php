<?php

namespace App\Http\Controllers;

use App\Enums\Gender;
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
        ]);
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
