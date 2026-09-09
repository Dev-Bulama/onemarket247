<?php

use App\Filament\Resources\LegalPages\Pages\EditLegalPage;
use App\Models\LegalPage;
use App\Models\User;
use App\Support\LegalPageKeys;
use Database\Seeders\LegalPageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
    (new LegalPageSeeder)->run();
});

function legalPagesAdmin(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $user;
}

test('the seeder creates exactly the terms and privacy pages', function () {
    expect(LegalPage::count())->toBe(2)
        ->and(LegalPage::where('key', LegalPageKeys::Terms)->exists())->toBeTrue()
        ->and(LegalPage::where('key', LegalPageKeys::Privacy)->exists())->toBeTrue();
});

test('re-running the seeder does not overwrite an admin-edited page', function () {
    LegalPage::where('key', LegalPageKeys::Terms)->update(['body' => 'Custom edited terms.']);

    (new LegalPageSeeder)->run();

    expect(LegalPage::where('key', LegalPageKeys::Terms)->first()->body)->toBe('Custom edited terms.');
});

test('an admin can edit the terms of service page', function () {
    $admin = legalPagesAdmin();
    $page = LegalPage::where('key', LegalPageKeys::Terms)->firstOrFail();

    Livewire::actingAs($admin, 'admin')
        ->test(EditLegalPage::class, ['record' => $page->getRouteKey()])
        ->fillForm(['title' => 'Terms of Service (Updated)', 'body' => '<p>New terms content.</p>'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($page->fresh()->title)->toBe('Terms of Service (Updated)')
        ->and($page->fresh()->body)->toBe('<p>New terms content.</p>');
});

test('an admin without cms.manage cannot access legal pages', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Support Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/legal-pages')->assertForbidden();
});
