<?php

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Cart;
use App\Models\Country;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wishlist;

function deleteAccountToken(User $user): string
{
    return $user->createToken('t', ['customer:*'])->plainTextToken;
}

test('a customer can delete their own account with the correct password', function () {
    $user = User::factory()->create(['password' => bcrypt('correct-password')]);
    $token = deleteAccountToken($user);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson('/api/v1/profile', ['current_password' => 'correct-password'])
        ->assertOk();

    $user->refresh();
    expect($user->status)->toBe(UserStatus::Deleted)
        ->and($user->name)->toBe('Deleted User')
        ->and($user->email)->toContain('@deleted.')
        ->and($user->phone)->toBeNull()
        ->and($user->tokens()->count())->toBe(0);
});

test('deleting an account is rejected with the wrong password', function () {
    $user = User::factory()->create(['password' => bcrypt('correct-password')]);
    $token = deleteAccountToken($user);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson('/api/v1/profile', ['current_password' => 'wrong-password'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('current_password');

    expect($user->fresh()->status)->toBe(UserStatus::Active);
});

test('a social-login-only account can delete without a password', function () {
    $user = User::factory()->create();
    SocialAccount::create(['user_id' => $user->id, 'provider' => 'google', 'provider_user_id' => 'g-123']);
    $token = deleteAccountToken($user);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson('/api/v1/profile', [])
        ->assertOk();

    expect($user->fresh()->status)->toBe(UserStatus::Deleted)
        ->and(SocialAccount::where('user_id', $user->id)->exists())->toBeFalse();
});

test('deleting an account removes addresses, wishlist, and carts', function () {
    $user = User::factory()->create(['password' => bcrypt('correct-password')]);
    $user->addresses()->create(['label' => 'Home', 'full_name' => 'Test', 'address_line_1' => '1 Test St', 'country_id' => Country::factory()->create()->id]);
    $wishlist = $user->wishlistOrCreate();
    $cart = Cart::create(['customer_id' => $user->id]);
    $token = deleteAccountToken($user);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson('/api/v1/profile', ['current_password' => 'correct-password'])
        ->assertOk();

    expect($user->addresses()->count())->toBe(0)
        ->and(Wishlist::find($wishlist->id))->toBeNull()
        ->and(Cart::find($cart->id))->toBeNull();
});

test('a deleted account cannot log back in', function () {
    $user = User::factory()->create(['password' => bcrypt('correct-password')]);
    $token = deleteAccountToken($user);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson('/api/v1/profile', ['current_password' => 'correct-password'])
        ->assertOk();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email, // the original email — no longer matches the anonymized row
        'password' => 'correct-password',
        'device_name' => 'test',
    ])->assertStatus(422);
});

test('a vendor account cannot be deleted through this endpoint', function () {
    $vendor = Vendor::factory()->create();
    $vendor->user->update(['user_type' => UserType::VendorOwner]);
    $token = $vendor->user->createToken('t', ['vendor:*'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson('/api/v1/profile', [])
        ->assertStatus(422);

    expect($vendor->user->fresh()->status)->toBe(UserStatus::Active);
});
