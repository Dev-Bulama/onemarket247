<?php

use App\Enums\CommissionScopeType;
use App\Enums\CommissionType;
use App\Filament\Resources\CommissionRules\Pages\CreateCommissionRule;
use App\Filament\Resources\CommissionRules\Pages\EditCommissionRule;
use App\Models\CommissionRule;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function financeAdminUser(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Finance Staff')->where('guard_name', 'admin')->first());

    return $user;
}

test('finance staff can load the commission rule index, create, and edit pages', function () {
    $admin = financeAdminUser();
    $rule = CommissionRule::factory()->global()->create();

    $this->actingAs($admin, 'admin')->get('/admin/commission-rules')->assertOk();
    $this->actingAs($admin, 'admin')->get('/admin/commission-rules/create')->assertOk();
    $this->actingAs($admin, 'admin')->get("/admin/commission-rules/{$rule->id}/edit")->assertOk();
});

test('an admin without commissions.manage cannot access commission rules', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Catalog Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/commission-rules')->assertForbidden();
});

test('finance staff can create a global commission rule', function () {
    $admin = financeAdminUser();

    Livewire::actingAs($admin, 'admin')
        ->test(CreateCommissionRule::class)
        ->fillForm([
            'scope_type' => CommissionScopeType::Global->value,
            'rate_type' => CommissionType::Percentage->value,
            'rate_value' => 12,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $rule = CommissionRule::where('scope_type', CommissionScopeType::Global)->firstOrFail();
    expect((float) $rule->rate_value)->toBe(12.0);
});

test('finance staff can create a vendor-scoped commission rule through the reactive form', function () {
    $admin = financeAdminUser();
    $vendor = Vendor::factory()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(CreateCommissionRule::class)
        // Two steps to mirror the real UX: selecting "Vendor" scope first
        // reveals the vendor_id field, which is then filled in.
        ->fillForm(['scope_type' => CommissionScopeType::Vendor->value])
        ->fillForm([
            'vendor_id' => $vendor->id,
            'rate_type' => CommissionType::Percentage->value,
            'rate_value' => 12,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $rule = CommissionRule::where('vendor_id', $vendor->id)->firstOrFail();
    expect($rule->scope_type)->toBe(CommissionScopeType::Vendor)
        ->and((float) $rule->rate_value)->toBe(12.0);
});

test('finance staff can edit an existing commission rule', function () {
    $admin = financeAdminUser();
    $rule = CommissionRule::factory()->global()->create(['rate_value' => 10]);

    Livewire::actingAs($admin, 'admin')
        ->test(EditCommissionRule::class, ['record' => $rule->getRouteKey()])
        ->fillForm(['rate_value' => 15])
        ->call('save')
        ->assertHasNoFormErrors();

    expect((float) $rule->fresh()->rate_value)->toBe(15.0);
});
