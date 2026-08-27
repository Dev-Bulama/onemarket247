<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AddressRequest;
use App\Http\Resources\Api\V1\AddressResource;
use App\Models\Address;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()->addresses()
            ->with(['country', 'state', 'city'])
            ->orderByDesc('is_default_shipping')
            ->get();

        return ApiResponse::success(AddressResource::collection($addresses));
    }

    public function store(AddressRequest $request): JsonResponse
    {
        $data = $this->withDefaultFlags($request);

        $address = $request->user()->addresses()->create($data);

        $this->applyDefaults($request->user(), $address, $data);

        return ApiResponse::success(new AddressResource($address->fresh(['country', 'state', 'city'])), status: 201);
    }

    public function update(AddressRequest $request, Address $address): JsonResponse
    {
        $data = $this->withDefaultFlags($request);

        $address->update($data);

        $this->applyDefaults($request->user(), $address, $data);

        return ApiResponse::success(new AddressResource($address->fresh(['country', 'state', 'city'])));
    }

    public function destroy(Request $request, Address $address): JsonResponse
    {
        Gate::authorize('delete', $address);

        $address->delete();

        return ApiResponse::success(message: 'Address deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function withDefaultFlags(AddressRequest $request): array
    {
        return [
            ...$request->validated(),
            'is_default_shipping' => $request->boolean('is_default_shipping'),
            'is_default_billing' => $request->boolean('is_default_billing'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyDefaults(mixed $user, Address $address, array $data): void
    {
        if (! empty($data['is_default_shipping'])) {
            $user->addresses()->where('id', '!=', $address->id)->update(['is_default_shipping' => false]);
        }

        if (! empty($data['is_default_billing'])) {
            $user->addresses()->where('id', '!=', $address->id)->update(['is_default_billing' => false]);
        }
    }
}
