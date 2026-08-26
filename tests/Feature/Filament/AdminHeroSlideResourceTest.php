<?php

use App\Filament\Resources\HeroSlides\Pages\CreateHeroSlide;
use App\Filament\Resources\HeroSlides\Pages\EditHeroSlide;
use App\Filament\Resources\HeroSlides\Pages\ListHeroSlides;
use App\Models\HeroSlide;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function heroSlideAdminUser(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $user;
}

test('an admin can load the hero slides list page', function () {
    $admin = heroSlideAdminUser();

    $this->actingAs($admin, 'admin')->get('/admin/hero-slides')->assertOk();
});

test('an admin without settings.manage cannot access hero slides', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Support Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/hero-slides')->assertForbidden();
});

test('an admin can add a new hero slide', function () {
    Storage::fake('public');
    $admin = heroSlideAdminUser();

    Livewire::actingAs($admin, 'admin')
        ->test(CreateHeroSlide::class)
        ->fillForm([
            'image_path' => UploadedFile::fake()->image('hero.jpg'),
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(HeroSlide::query()->count())->toBe(1);
});

test('adding multiple hero slides keeps each in its own sort position', function () {
    Storage::fake('public');
    $admin = heroSlideAdminUser();

    foreach (range(1, 3) as $i) {
        Livewire::actingAs($admin, 'admin')
            ->test(CreateHeroSlide::class)
            ->fillForm([
                'image_path' => UploadedFile::fake()->image("hero-{$i}.jpg"),
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    expect(HeroSlide::query()->orderBy('sort_order')->pluck('sort_order')->all())->toBe([0, 1, 2]);
});

test('an admin can deactivate a hero slide without deleting it', function () {
    Storage::fake('public');
    $slide = HeroSlide::factory()->create(['is_active' => true]);
    Storage::disk('public')->put($slide->image_path, 'fake-bytes');
    $admin = heroSlideAdminUser();

    Livewire::actingAs($admin, 'admin')
        ->test(EditHeroSlide::class, ['record' => $slide->getKey()])
        ->fillForm(['is_active' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($slide->fresh()->is_active)->toBeFalse();
});

test('an admin can delete a hero slide', function () {
    Storage::fake('public');
    $slide = HeroSlide::factory()->create();
    $admin = heroSlideAdminUser();

    Livewire::actingAs($admin, 'admin')
        ->test(ListHeroSlides::class)
        ->callTableAction('delete', $slide);

    expect(HeroSlide::query()->find($slide->id))->toBeNull();
});
