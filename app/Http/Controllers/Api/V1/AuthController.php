<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\RegisterCustomerAction;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ApiLoginRequest;
use App\Http\Requests\Auth\RegisterCustomerRequest;
use App\Models\LoginHistory;
use App\Models\User;
use App\Services\Auth\TwoFactorAuthenticationService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly TwoFactorAuthenticationService $twoFactor) {}

    public function register(RegisterCustomerRequest $request, RegisterCustomerAction $action): JsonResponse
    {
        $user = $action->handle($request->validated());

        $token = $user->createToken('api-register', [$this->abilityFor($user)]);

        return ApiResponse::success([
            'token' => $token->plainTextToken,
            'user' => $this->userPayload($user),
        ], status: 201);
    }

    public function login(ApiLoginRequest $request): JsonResponse
    {
        $user = $request->resolveUser();

        if ($user->hasTwoFactorEnabled()) {
            $code = $request->string('two_factor_code');

            if ($code->isEmpty()) {
                return ApiResponse::error(
                    'Two-factor code required.',
                    [],
                    'TWO_FACTOR_REQUIRED',
                    409,
                );
            }

            if (! $this->twoFactor->verify($user->twoFactorCredential->secret, $code)) {
                throw ValidationException::withMessages(['two_factor_code' => 'That code is invalid.']);
            }
        }

        $token = $user->createToken($request->string('device_name'), [$this->abilityFor($user)]);

        LoginHistory::create([
            'user_id' => $user->id,
            'guard' => 'sanctum',
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'device_fingerprint' => sha1($request->ip().'|'.$request->userAgent()),
            'is_new_device' => false,
            'successful' => true,
        ]);

        return ApiResponse::success([
            'token' => $token->plainTextToken,
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(message: 'Logged out.');
    }

    public function sessions(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()->orderByDesc('last_used_at')->get([
            'id', 'name', 'abilities', 'last_used_at', 'created_at',
        ]);

        return ApiResponse::success($tokens);
    }

    public function destroySession(Request $request, int $tokenId): JsonResponse
    {
        $deleted = $request->user()->tokens()->where('id', $tokenId)->delete();

        if (! $deleted) {
            return ApiResponse::error('Token not found.', [], 'NOT_FOUND', 404);
        }

        return ApiResponse::success(message: 'Session revoked.');
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::broker('customers')->sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? ApiResponse::success(message: __($status))
            : ApiResponse::error(__($status), [], 'RESET_LINK_FAILED', 422);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed'],
        ]);

        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill(['password' => Hash::make($request->string('password'))])->save();
            },
        );

        return $status === Password::PASSWORD_RESET
            ? ApiResponse::success(message: __($status))
            : ApiResponse::error(__($status), [], 'RESET_FAILED', 422);
    }

    private function abilityFor(User $user): string
    {
        return in_array($user->user_type, [UserType::VendorOwner, UserType::VendorStaff], true)
            ? 'vendor:*'
            : 'customer:*';
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'user_type' => $user->user_type->value,
            'email_verified' => $user->hasVerifiedEmail(),
        ];
    }
}
