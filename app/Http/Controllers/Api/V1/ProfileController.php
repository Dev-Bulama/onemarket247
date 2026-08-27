<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Gender;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('customerProfile');

        return ApiResponse::success($this->payload($user));
    }

    public function update(Request $request): JsonResponse
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

        $user = $request->user();

        $user->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
        ]);

        $user->customerProfile()->updateOrCreate([], [
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'preferred_language_id' => $validated['preferred_language_id'] ?? null,
            'preferred_currency_id' => $validated['preferred_currency_id'] ?? null,
            'marketing_opt_in' => $validated['marketing_opt_in'] ?? false,
        ]);

        return ApiResponse::success($this->payload($user->fresh('customerProfile')));
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password:sanctum'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update(['password' => Hash::make($validated['password'])]);

        return ApiResponse::success(message: 'Password updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'date_of_birth' => $user->customerProfile?->date_of_birth,
            'gender' => $user->customerProfile?->gender?->value,
            'preferred_language_id' => $user->customerProfile?->preferred_language_id,
            'preferred_currency_id' => $user->customerProfile?->preferred_currency_id,
            'marketing_opt_in' => $user->customerProfile?->marketing_opt_in ?? false,
        ];
    }
}
