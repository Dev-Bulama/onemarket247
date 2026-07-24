<?php

use App\Filament\Pages\HeroBanner;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function heroBannerAdminUser(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $user;
}

test('an admin can load the hero banner page', function () {
    $admin = heroBannerAdminUser();

    $this->actingAs($admin, 'admin')->get('/admin/hero-banner')->assertOk();
});

test('an admin without settings.manage cannot access the hero banner page', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Support Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/hero-banner')->assertForbidden();
});

test('an admin can upload a hero photo and it lands at the exact path the homepage looks for', function () {
    Storage::fake('public');
    $admin = heroBannerAdminUser();

    Livewire::actingAs($admin, 'admin')
        ->test(HeroBanner::class)
        ->fillForm([
            'image' => [UploadedFile::fake()->image('hero.jpg')->store('tmp-hero-banner', 'public')],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    Storage::disk('public')->assertExists('hero/slide-1.jpg');
});

test('uploading a new hero photo replaces the old one', function () {
    Storage::fake('public');
    Storage::disk('public')->put('hero/slide-1.jpg', 'old-photo-bytes');
    $admin = heroBannerAdminUser();

    Livewire::actingAs($admin, 'admin')
        ->test(HeroBanner::class)
        ->fillForm([
            'image' => [UploadedFile::fake()->image('hero.jpg')->store('tmp-hero-banner', 'public')],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Storage::disk('public')->get('hero/slide-1.jpg'))->not->toBe('old-photo-bytes');
});

test('an admin can remove the current hero photo', function () {
    Storage::fake('public');
    Storage::disk('public')->put('hero/slide-1.jpg', 'photo-bytes');
    $admin = heroBannerAdminUser();

    Livewire::actingAs($admin, 'admin')
        ->test(HeroBanner::class)
        ->call('removeImage');

    Storage::disk('public')->assertMissing('hero/slide-1.jpg');
});
