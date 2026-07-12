<?php

namespace App\Auth;

use App\Enums\UserType;
use Illuminate\Auth\EloquentUserProvider;

/**
 * Enforces guard-level user-type isolation: a credential/token/id that
 * resolves to a User outside this provider's allowed user_types is treated
 * as "not found," never authenticated. This is what makes it structurally
 * impossible for a vendor session to satisfy the admin guard (or vice
 * versa) — see docs/architecture/01-system-architecture.md §3.
 */
class ScopedEloquentUserProvider extends EloquentUserProvider
{
    /**
     * @param  list<UserType>  $allowedUserTypes
     */
    public function __construct($hasher, $model, private readonly array $allowedUserTypes)
    {
        parent::__construct($hasher, $model);
    }

    public function retrieveById($identifier)
    {
        $user = parent::retrieveById($identifier);

        return $this->allowed($user) ? $user : null;
    }

    public function retrieveByToken($identifier, #[\SensitiveParameter] $token)
    {
        $user = parent::retrieveByToken($identifier, $token);

        return $this->allowed($user) ? $user : null;
    }

    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials)
    {
        $user = parent::retrieveByCredentials($credentials);

        return $this->allowed($user) ? $user : null;
    }

    private function allowed(mixed $user): bool
    {
        return $user !== null && in_array($user->user_type, $this->allowedUserTypes, true);
    }
}
