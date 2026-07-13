<?php

use App\Enums\UserType;
use App\Models\Address;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Product;
use App\Models\User;

test('a customer can view and update their profile', function () {
    $user = User::factory()->create(['user_type' => UserType::Customer, 'email_verified_at' => now()]);
    $language = Language::factory()->create();
    $currency = Currency::factory()->create();

    $this->actingAs($user)->get(route('account.profile.edit'))->assertOk();

    $this->actingAs($user)->put(route('account.profile.update'), [
        'name' => 'Jane Updated',
        'phone' => '123456',
        'date_of_birth' => '1990-01-01',
        'gender' => 'female',
        'preferred_language_id' => $language->id,
        'preferred_currency_id' => $currency->id,
        'marketing_opt_in' => '1',
    ])->assertRedirect(route('account.profile.edit'));

    expect($user->fresh()->name)->toBe('Jane Updated')
        ->and($user->customerProfile()->first()->marketing_opt_in)->toBeTrue();
});

test('a customer can create, edit, and delete an address, and set it as default', function () {
    $user = User::factory()->create(['user_type' => UserType::Customer, 'email_verified_at' => now()]);
    $country = Country::factory()->create();

    $this->actingAs($user)->get(route('account.addresses.index'))->assertOk();
    $this->actingAs($user)->get(route('account.addresses.create'))->assertOk();

    $this->actingAs($user)->post(route('account.addresses.store'), [
        'label' => 'Home',
        'full_name' => 'Jane Doe',
        'address_line_1' => '123 Main St',
        'country_id' => $country->id,
        'is_default_shipping' => '1',
    ])->assertRedirect(route('account.addresses.index'));

    $address = $user->addresses()->first();
    expect($address)->not->toBeNull()
        ->and($address->is_default_shipping)->toBeTrue();

    $this->actingAs($user)->get(route('account.addresses.edit', $address))->assertOk();

    $this->actingAs($user)->put(route('account.addresses.update', $address), [
        'label' => 'Home Updated',
        'full_name' => 'Jane Doe',
        'address_line_1' => '456 Main St',
        'country_id' => $country->id,
    ])->assertRedirect(route('account.addresses.index'));

    expect($address->fresh())
        ->label->toBe('Home Updated')
        ->is_default_shipping->toBeFalse();

    $this->actingAs($user)->delete(route('account.addresses.destroy', $address))->assertRedirect(route('account.addresses.index'));
    expect(Address::find($address->id))->toBeNull();
});

test('a customer cannot view, edit, or delete another customers address', function () {
    $owner = User::factory()->create(['user_type' => UserType::Customer, 'email_verified_at' => now()]);
    $other = User::factory()->create(['user_type' => UserType::Customer, 'email_verified_at' => now()]);
    $address = Address::factory()->create(['addressable_id' => $owner->id, 'addressable_type' => User::class]);

    $this->actingAs($other)->get(route('account.addresses.edit', $address))->assertForbidden();
    $this->actingAs($other)->put(route('account.addresses.update', $address), ['label' => 'x', 'full_name' => 'x', 'address_line_1' => 'x'])->assertForbidden();
    $this->actingAs($other)->delete(route('account.addresses.destroy', $address))->assertForbidden();
});

test('a customer can add and remove a product from their wishlist', function () {
    $user = User::factory()->create(['user_type' => UserType::Customer, 'email_verified_at' => now()]);
    $product = Product::factory()->create();

    $this->actingAs($user)->get(route('account.wishlist.index'))->assertOk();

    $this->actingAs($user)->post(route('account.wishlist.store', $product))->assertRedirect();
    expect($user->fresh()->wishlist->products()->where('product_id', $product->id)->exists())->toBeTrue();

    $this->actingAs($user)->delete(route('account.wishlist.destroy', $product))->assertRedirect();
    expect($user->fresh()->wishlist->products()->where('product_id', $product->id)->exists())->toBeFalse();
});

test('a customer can add and remove a product from their compare list', function () {
    $user = User::factory()->create(['user_type' => UserType::Customer, 'email_verified_at' => now()]);
    $product = Product::factory()->create();

    $this->actingAs($user)->get(route('account.compare.index'))->assertOk();

    $this->actingAs($user)->post(route('account.compare.store', $product))->assertRedirect();
    expect($user->fresh()->compareList->products()->where('product_id', $product->id)->exists())->toBeTrue();

    $this->actingAs($user)->delete(route('account.compare.destroy', $product))->assertRedirect();
    expect($user->fresh()->compareList->products()->where('product_id', $product->id)->exists())->toBeFalse();
});

test('the product page shows wishlist and compare actions for an authenticated customer', function () {
    $user = User::factory()->create(['user_type' => UserType::Customer, 'email_verified_at' => now()]);
    $product = Product::factory()->create();

    $this->actingAs($user)->get(route('products.show', $product))
        ->assertOk()
        ->assertSee('Add to wishlist')
        ->assertSee('Add to compare');
});
