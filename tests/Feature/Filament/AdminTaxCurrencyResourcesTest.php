<?php

use App\Filament\Pages\TranslationManagerPage;
use App\Filament\Resources\Currencies\Pages\CreateCurrency;
use App\Filament\Resources\Currencies\Pages\EditCurrency;
use App\Filament\Resources\TaxClasses\Pages\EditTaxClass;
use App\Filament\Resources\TaxClasses\RelationManagers\RatesRelationManager;
use App\Models\Country;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Language;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\TaxClass;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function taxAdminUser(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $user;
}

test('an admin can load the tax classes, tax rates and currencies index and create pages', function () {
    $admin = taxAdminUser();

    foreach (['tax-classes', 'tax-rates', 'currencies'] as $slug) {
        $this->actingAs($admin, 'admin')->get("/admin/{$slug}")->assertOk();
        $this->actingAs($admin, 'admin')->get("/admin/{$slug}/create")->assertOk();
    }
});

test('an admin without taxes.manage cannot access the tax classes resource', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Catalog Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/tax-classes')->assertForbidden();
});

test('an admin can add a rate to a tax class through its relation manager', function () {
    $admin = taxAdminUser();
    $taxClass = TaxClass::factory()->create();
    $country = Country::factory()->create();

    Filament::setCurrentPanel('admin');

    Livewire::actingAs($admin, 'admin')
        ->test(EditTaxClass::class, ['record' => $taxClass->getRouteKey()])
        ->assertOk();

    Livewire::actingAs($admin, 'admin')
        ->test(RatesRelationManager::class, [
            'ownerRecord' => $taxClass,
            'pageClass' => EditTaxClass::class,
        ])
        ->callTableAction('create', data: [
            'name' => 'Standard rate',
            'country_id' => $country->id,
            'rate_percent' => 7.5,
        ]);

    expect($taxClass->fresh()->rates)->toHaveCount(1)
        ->and((float) $taxClass->fresh()->rates->first()->rate_percent)->toBe(7.5);
});

test('creating a currency also creates its exchange rate row', function () {
    $admin = taxAdminUser();

    Filament::setCurrentPanel('admin');

    Livewire::actingAs($admin, 'admin')
        ->test(CreateCurrency::class)
        ->fillForm([
            'name' => 'Japanese Yen',
            'code' => 'JPY',
            'symbol' => '¥',
            'symbol_position' => 'before',
            'decimal_places' => 0,
            'thousand_separator' => ',',
            'decimal_separator' => '.',
            'is_default' => false,
            'is_active' => true,
            'exchange_rate' => 150,
            'exchange_rate_is_manual' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $currency = Currency::where('code', 'JPY')->firstOrFail();

    expect((float) $currency->exchangeRate->rate)->toBe(150.0)
        ->and($currency->exchangeRate->is_manual)->toBeTrue();
});

test('editing a currency updates its existing exchange rate row', function () {
    $admin = taxAdminUser();
    $currency = Currency::factory()->create(['code' => 'GBP']);
    ExchangeRate::factory()->create(['currency_id' => $currency->id, 'rate' => 0.79]);

    Filament::setCurrentPanel('admin');

    Livewire::actingAs($admin, 'admin')
        ->test(EditCurrency::class, ['record' => $currency->getRouteKey()])
        ->assertFormSet(['exchange_rate' => 0.79])
        ->fillForm(['exchange_rate' => 0.81])
        ->call('save')
        ->assertHasNoFormErrors();

    expect((float) $currency->exchangeRate()->first()->rate)->toBe(0.81)
        ->and(ExchangeRate::where('currency_id', $currency->id)->count())->toBe(1);
});

test('an admin can view the translation manager report and see missing languages', function () {
    $admin = taxAdminUser();

    Language::factory()->create(['code' => 'en', 'is_default' => true, 'is_active' => true]);
    $fr = Language::factory()->create(['code' => 'fr', 'is_active' => true]);

    $product = Product::factory()->create(['sku' => 'SKU-1']);
    ProductTranslation::factory()->for($product)->for($fr, 'language')->create();

    Filament::setCurrentPanel('admin');

    Livewire::actingAs($admin, 'admin')
        ->test(TranslationManagerPage::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$product]);
});

test('an admin can import product translations from a csv', function () {
    $admin = taxAdminUser();

    Language::factory()->create(['code' => 'en', 'is_default' => true, 'is_active' => true]);
    $fr = Language::factory()->create(['code' => 'fr', 'is_active' => true]);
    $product = Product::factory()->create(['sku' => 'SKU-IMPORT']);

    Storage::fake('local');

    $csv = "sku,language_code,name,short_description,description,seo_title,seo_description\nSKU-IMPORT,fr,Nom Français,,,,\n";
    $file = UploadedFile::fake()->createWithContent('translations.csv', $csv);
    $path = $file->store('tmp-translation-imports', 'local');

    Filament::setCurrentPanel('admin');

    Livewire::actingAs($admin, 'admin')
        ->test(TranslationManagerPage::class)
        ->callTableAction('importTranslations', data: ['file' => [$path]]);

    expect($product->translations()->count())->toBe(1)
        ->and($product->translatedName('fr'))->toBe('Nom Français');
});

test('an admin without products.update cannot access the translation manager page', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Support Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/translation-manager-page')->assertForbidden();
});
