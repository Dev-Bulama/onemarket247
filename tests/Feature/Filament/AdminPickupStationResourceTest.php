<?php

use App\Filament\Resources\PickupStations\Pages\ListPickupStations;
use App\Models\City;
use App\Models\PickupStation;
use App\Models\State;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

// Named distinctly from AdminShippingResourcesTest.php's identical helper
// — Pest test-file-scoped functions are actually global in PHP, so two
// files declaring the same name collide.
function pickupStationAdminUser(): User
{
    $admin = User::factory()->admin()->create();
    $admin->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $admin;
}

/**
 * Regression coverage for a real gap: city_id/phone are editable on
 * PickupStationForm, but PickupStationsTable only ever showed the
 * country — an admin with several pickup points in the same country
 * (common) had no way to tell them apart, or to call the station,
 * from the list. Same class of gap already fixed for
 * VendorsTable/StoresTable (state/city) elsewhere in this app.
 */
test('the pickup station list shows the city and phone, not just the country', function () {
    $admin = pickupStationAdminUser();
    $state = State::factory()->create(['name' => 'Lagos']);
    $city = City::factory()->create(['state_id' => $state->id, 'name' => 'Ikeja']);
    PickupStation::factory()->create([
        'state_id' => $state->id,
        'city_id' => $city->id,
        'phone' => '+234 800 000 0000',
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test(ListPickupStations::class)
        ->assertSee('Ikeja')
        ->assertSee('+234 800 000 0000');
});
