<?php

use App\Actions\Inventory\AdjustStockAction;
use App\Enums\DisclaimerTrigger;
use App\Enums\ShippingRateType;
use App\Enums\StockStatus;
use App\Models\AgentApplication;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Disclaimer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\ShippingZoneLocation;
use App\Models\VendorApplication;
use App\Models\Warehouse;
use Database\Seeders\SettingsSeeder;
use Database\Seeders\VendorSubscriptionPlanSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('the API rejects checkout completion until the before_checkout disclaimer is accepted', function () {
    Currency::factory()->create(['code' => 'NGN', 'is_default' => true]);
    $country = Country::factory()->create();
    $zone = ShippingZone::factory()->create();
    ShippingZoneLocation::factory()->create(['shipping_zone_id' => $zone->id, 'country_id' => $country->id]);
    ShippingRate::factory()->create(['shipping_zone_id' => $zone->id, 'rate_type' => ShippingRateType::Free, 'base_amount' => 0]);
    $product = Product::factory()->create(['price' => 1000, 'manage_stock' => true, 'stock_status' => StockStatus::InStock]);
    $warehouse = Warehouse::factory()->create(['vendor_id' => $product->vendor_id]);
    app(AdjustStockAction::class)->handle($warehouse, $product, 10, 'seed');
    $disclaimer = Disclaimer::factory()->create(['trigger' => DisclaimerTrigger::BeforeCheckout]);

    $add = $this->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertCreated();
    $guestToken = $add->json('data.guest_token');

    $init = $this->postJson("/api/v1/checkout/init?cart_token={$guestToken}")->assertOk();
    $sessionKey = $init->json('data.checkout_session_key');

    $payload = [
        'checkout_session_key' => $sessionKey,
        'cart_token' => $guestToken,
        'guest_id' => 'guest-1',
        'email' => 'guest@example.com',
        'full_name' => 'Jane Guest',
        'address_line_1' => '1 Main St',
        'country_id' => $country->id,
    ];

    $blocked = $this->postJson('/api/v1/checkout/complete', $payload);
    $blocked->assertStatus(422)->assertJsonPath('error_code', 'DISCLAIMER_NOT_ACCEPTED');
    expect(Order::count())->toBe(0);

    $this->postJson("/api/v1/disclaimers/{$disclaimer->id}/accept", ['guest_id' => 'guest-1'])->assertOk();

    $allowed = $this->postJson('/api/v1/checkout/complete', $payload);
    $allowed->assertCreated();
    expect(Order::count())->toBe(1);
});

test('the API rejects an agent application until the before_agent_application disclaimer is accepted', function () {
    Storage::fake('local');
    $disclaimer = Disclaimer::factory()->create(['trigger' => DisclaimerTrigger::BeforeAgentApplication]);

    $payload = fn (string $email) => [
        'full_name' => 'Jane Agent',
        'email' => $email,
        'identity_document' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
        'terms' => '1',
        'guest_id' => 'guest-1',
    ];

    $blocked = $this->postJson('/api/v1/agent/apply', $payload('jane.agent@example.com'));
    $blocked->assertStatus(422)->assertJsonPath('error_code', 'DISCLAIMER_NOT_ACCEPTED');
    expect(AgentApplication::count())->toBe(0);

    $this->postJson("/api/v1/disclaimers/{$disclaimer->id}/accept", ['guest_id' => 'guest-1'])->assertOk();

    $allowed = $this->postJson('/api/v1/agent/apply', $payload('jane.agent2@example.com'));
    $allowed->assertCreated();
    expect(AgentApplication::where('email', 'jane.agent2@example.com')->exists())->toBeTrue();
});

test('the API rejects a vendor application until the before_vendor_application disclaimer is accepted', function () {
    Storage::fake('local');
    (new SettingsSeeder)->run();
    (new VendorSubscriptionPlanSeeder)->run();
    $disclaimer = Disclaimer::factory()->create(['trigger' => DisclaimerTrigger::BeforeVendorApplication]);

    $payload = [
        'full_name' => 'Jane Doe',
        'email' => 'jane.vendor@example.com',
        'phone' => '+15551234567',
        'business_name' => 'Jane Co',
        'store_name' => 'Jane Store',
        'store_description' => 'We sell things',
        'bank_name' => 'First Bank',
        'bank_account_name' => 'Jane Doe',
        'bank_account_number' => '1234567890',
        'identity_document' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
        'business_registration_document' => UploadedFile::fake()->create('reg.pdf', 100, 'application/pdf'),
        'terms' => '1',
        'guest_id' => 'guest-1',
    ];

    $blocked = $this->postJson('/api/v1/vendor/apply', $payload);
    $blocked->assertStatus(422)->assertJsonPath('error_code', 'DISCLAIMER_NOT_ACCEPTED');
    expect(VendorApplication::count())->toBe(0);

    $this->postJson("/api/v1/disclaimers/{$disclaimer->id}/accept", ['guest_id' => 'guest-1'])->assertOk();

    $allowed = $this->postJson('/api/v1/vendor/apply', $payload);
    $allowed->assertCreated();
    expect(VendorApplication::where('email', 'jane.vendor@example.com')->exists())->toBeTrue();
});
