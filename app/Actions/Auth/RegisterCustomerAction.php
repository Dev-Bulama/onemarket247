<?php

namespace App\Actions\Auth;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterCustomerAction
{
    /**
     * @param  array{name: string, email: string, password: string, phone: ?string}  $data
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'user_type' => UserType::Customer,
                'status' => UserStatus::Active,
            ]);

            CustomerProfile::create(['user_id' => $user->id]);

            event(new Registered($user));

            return $user;
        });
    }
}
