<?php

use App\Models\Product;
use App\Models\ProductDigitalFile;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
    Storage::fake('local');
});

function makeDigitalFile(Product $product): ProductDigitalFile
{
    $path = 'product-digital-files/'.$product->id.'/secret.pdf';
    Storage::disk('local')->put($path, 'secret contents');

    return ProductDigitalFile::factory()->for($product)->create([
        'file_path' => $path,
        'name' => 'secret.pdf',
    ]);
}

test('the owning vendor can download their own digital file', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $product = Product::factory()->for($vendor)->digital()->create();
    $file = makeDigitalFile($product);

    $this->actingAs($vendor->user, 'vendor')
        ->get(route('product-digital-files.download', $file))
        ->assertOk();
});

test('a different vendor cannot download someone elses digital file', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $product = Product::factory()->for($vendor)->digital()->create();
    $file = makeDigitalFile($product);

    $otherVendor = Vendor::factory()->create();
    Store::factory()->for($otherVendor)->create();

    $this->actingAs($otherVendor->user, 'vendor')
        ->get(route('product-digital-files.download', $file))
        ->assertForbidden();
});

test('an admin with products.view can download any digital file', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $product = Product::factory()->for($vendor)->digital()->create();
    $file = makeDigitalFile($product);

    $admin = User::factory()->admin()->create();
    $admin->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    $this->actingAs($admin, 'admin')
        ->get(route('product-digital-files.download', $file))
        ->assertOk();
});

test('an admin without products.view cannot download a digital file', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $product = Product::factory()->for($vendor)->digital()->create();
    $file = makeDigitalFile($product);

    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Support Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')
        ->get(route('product-digital-files.download', $file))
        ->assertForbidden();
});

test('a guest is redirected to login', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $product = Product::factory()->for($vendor)->digital()->create();
    $file = makeDigitalFile($product);

    $this->get(route('product-digital-files.download', $file))
        ->assertRedirect();
});
