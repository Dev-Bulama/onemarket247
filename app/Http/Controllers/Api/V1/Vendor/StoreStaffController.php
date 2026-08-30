<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Actions\Vendor\InviteStoreStaffAction;
use App\Enums\StoreStaffStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StoreStaffResource;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Support\Api\ApiResponse;
use App\Support\Api\Paginated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

/**
 * Store staff management is owner-only — see App\Policies\StoreStaffPolicy's
 * docblock — Gate::authorize() below is what actually enforces that (a
 * staff member's own account never has ->vendor set, so create/viewAny
 * already fail for them regardless of granted permissions).
 */
class StoreStaffController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', StoreStaff::class);

        $vendorId = $request->user()->actingVendorId();

        $staff = StoreStaff::whereHas('store', fn ($query) => $query->where('vendor_id', $vendorId))
            ->with('user')
            ->latest()
            ->paginate(20);

        return Paginated::response($staff, StoreStaffResource::class);
    }

    public function store(Request $request, InviteStoreStaffAction $action): JsonResponse
    {
        Gate::authorize('create', StoreStaff::class);

        $validPermissions = Permission::where('guard_name', 'vendor')->pluck('name');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in($validPermissions)],
        ]);

        $vendorId = $request->user()->actingVendorId();
        $vendorStore = Store::withoutGlobalScopes()->where('vendor_id', $vendorId)->firstOrFail();

        $staff = $action->handle($vendorStore, $data['name'], $data['email'], $data['permissions'] ?? []);

        return ApiResponse::success(new StoreStaffResource($staff->load('user')), status: 201);
    }

    public function update(Request $request, StoreStaff $storeStaff): JsonResponse
    {
        Gate::authorize('update', $storeStaff);

        $validPermissions = Permission::where('guard_name', 'vendor')->pluck('name');

        $data = $request->validate([
            'status' => ['required', Rule::in([StoreStaffStatus::Active->value, StoreStaffStatus::Suspended->value])],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in($validPermissions)],
        ]);

        $storeStaff->update(['status' => $data['status']]);

        $storeStaff->user->syncPermissions(
            Permission::where('guard_name', 'vendor')->whereIn('name', $data['permissions'] ?? [])->get()
        );

        return ApiResponse::success(new StoreStaffResource($storeStaff->fresh()->load('user')));
    }

    public function destroy(Request $request, StoreStaff $storeStaff): JsonResponse
    {
        Gate::authorize('delete', $storeStaff);

        $storeStaff->delete();

        return ApiResponse::success(message: 'Staff member removed.');
    }
}
