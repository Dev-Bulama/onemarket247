<?php

use App\Actions\Inventory\AdjustStockAction;
use App\Enums\DisclaimerTrigger;
use App\Enums\ShippingRateType;
use App\Enums\StockStatus;
use App\Models\AgentApplication;
use App\Models\CheckoutSession;
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

test('a first-visit disclaimer shows on the homepage for a new visitor', function () {
    Disclaimer::factory()->create(['trigger' => DisclaimerTrigger::FirstVisit, 'title' => 'Welcome notice']);

    $this->get('/')->assertOk()->assertSee('Welcome notice');
});

test('accepting the disclaimer stops it from showing again for the same guest', function () {
    $disclaimer = Disclaimer::factory()->create(['trigger' => DisclaimerTrigger::FirstVisit, 'title' => 'Welcome notice']);

    $first = $this->get('/');
    $visitorId = $first->getCookie('visitor_id')->getValue();

    $this->withCookie('visitor_id', $visitorId)->post(route('disclaimers.accept', $disclaimer));

    $this->withCookie('visitor_id', $visitorId)->get('/')->assertDontSee('Welcome notice');
});

test('a before_checkout disclaimer blocks checkout until accepted', function () {
    Storage::fake('local');
    (new SettingsSeeder)->run();
    $disclaimer = Disclaimer::factory()->create(['trigger' => DisclaimerTrigger::BeforeCheckout, 'title' => 'Checkout notice']);
    Currency::factory()->create(['is_default' => true]);
    $country = Country::factory()->create();
    $zone = ShippingZone::factory()->create();
    ShippingZoneLocation::factory()->create(['shipping_zone_id' => $zone->id, 'country_id' => $country->id]);
    ShippingRate::factory()->create(['shipping_zone_id' => $zone->id, 'rate_type' => ShippingRateType::Free, 'base_amount' => 0]);
    $product = Product::factory()->create(['manage_stock' => true, 'stock_status' => StockStatus::InStock]);
    $warehouse = Warehouse::factory()->create(['vendor_id' => $product->vendor_id]);
    app(AdjustStockAction::class)->handle($warehouse, $product, 10, 'seed');

    $addResponse = $this->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 1]);
    $token = $addResponse->getCookie('cart_token')->getValue();

    $indexResponse = $this->withCookie('cart_token', $token)->get(route('checkout.index'));
    $indexResponse->assertOk()->assertSee('Checkout notice');
    $visitorId = $indexResponse->getCookie('visitor_id')->getValue();

    $session = CheckoutSession::first();
    $payload = [
        'checkout_session_key' => $session->idempotency_key,
        'email' => 'guest@example.com',
        'full_name' => 'Jane Guest',
        'address_line_1' => '123 Main St',
        'country_id' => $country->id,
    ];

    $blocked = $this->withCookie('cart_token', $token)->withCookie('visitor_id', $visitorId)->post(route('checkout.store'), $payload);

    $blocked->assertRedirect(route('checkout.index'))->assertSessionHasErrors('checkout');
    expect(Order::count())->toBe(0);

    $this->withCookie('visitor_id', $visitorId)->post(route('disclaimers.accept', $disclaimer));

    $allowed = $this->withCookie('cart_token', $token)->withCookie('visitor_id', $visitorId)->post(route('checkout.store'), $payload);

    $allowed->assertRedirect();
    expect(Order::count())->toBe(1);
});

test('a before_agent_application disclaimer blocks submission until accepted', function () {
    Storage::fake('local');
    $disclaimer = Disclaimer::factory()->create(['trigger' => DisclaimerTrigger::BeforeAgentApplication, 'title' => 'Agent notice']);

    $indexResponse = $this->get(route('agent.apply'));
    $indexResponse->assertOk()->assertSee('Agent notice');
    $visitorId = $indexResponse->getCookie('visitor_id')->getValue();

    $payload = fn (string $email) => [
        'full_name' => 'Jane Agent',
        'email' => $email,
        'identity_document' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
        'terms' => '1',
    ];

    $blocked = $this->withCookie('visitor_id', $visitorId)->post(route('agent.apply'), $payload('jane.agent@example.com'));

    $blocked->assertRedirect(route('agent.apply'))->assertSessionHasErrors('application');
    expect(AgentApplication::count())->toBe(0);

    $this->withCookie('visitor_id', $visitorId)->post(route('disclaimers.accept', $disclaimer));

    $allowed = $this->withCookie('visitor_id', $visitorId)->post(route('agent.apply'), $payload('jane.agent2@example.com'));

    $allowed->assertRedirect(route('agent.apply.submitted'));
    expect(AgentApplication::where('email', 'jane.agent2@example.com')->exists())->toBeTrue();
});

test('a before_vendor_application disclaimer blocks submission until accepted', function () {
    Storage::fake('local');
    (new SettingsSeeder)->run();
    (new VendorSubscriptionPlanSeeder)->run();
    $disclaimer = Disclaimer::factory()->create(['trigger' => DisclaimerTrigger::BeforeVendorApplication, 'title' => 'Vendor notice']);

    $indexResponse = $this->get(route('vendor.register'));
    $indexResponse->assertOk()->assertSee('Vendor notice');
    $visitorId = $indexResponse->getCookie('visitor_id')->getValue();

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
    ];

    $blocked = $this->withCookie('visitor_id', $visitorId)->post('/vendor/register', $payload);

    $blocked->assertRedirect(route('vendor.register'))->assertSessionHasErrors('application');
    expect(VendorApplication::count())->toBe(0);

    $this->withCookie('visitor_id', $visitorId)->post(route('disclaimers.accept', $disclaimer));

    $allowed = $this->withCookie('visitor_id', $visitorId)->post('/vendor/register', $payload);

    $allowed->assertRedirect(route('vendor.register.submitted'));
    expect(VendorApplication::where('email', 'jane.vendor@example.com')->exists())->toBeTrue();
});
