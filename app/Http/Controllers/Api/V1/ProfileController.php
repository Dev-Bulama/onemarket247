<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Account\DeleteAccountAction;
use App\Enums\Gender;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

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
     * Self-service account deletion — see DeleteAccountAction's docblock
     * for exactly what this does and doesn't remove. Vendor accounts have
     * their own business considerations (active store, orders, wallet
     * balance) and are deliberately not covered by this endpoint; they're
     * pointed to support instead.
     */
    public function destroy(Request $request, DeleteAccountAction $action): JsonResponse
    {
        $user = $request->user();

        if ($user->user_type !== UserType::Customer) {
            return ApiResponse::error(
                'Vendor accounts can\'t be deleted here — contact support to close a vendor account.',
                status: 422,
            );
        }

        // A social-login-only account never had a password it could
        // confirm — being authenticated at all (a valid Sanctum token) is
        // the only confirmation it's able to give.
        if (! $user->socialAccounts()->exists()) {
            $validated = $request->validate(['current_password' => ['required', 'string']]);

            if (! Hash::check($validated['current_password'], $user->password)) {
                throw ValidationException::withMessages(['current_password' => 'The password is incorrect.']);
            }
        }

        $action->handle($user);

        return ApiResponse::success(message: 'Your account has been deleted.');
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
