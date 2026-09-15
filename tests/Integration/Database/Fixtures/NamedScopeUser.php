<?php

namespace Heritage\Tests\Integration\Database\Fixtures;

use Heritage\Database\Eloquent\Attributes\Scope;
use Heritage\Database\Eloquent\Builder;
use Heritage\Tests\Database\Fixtures\Models\Integration\User;

class NamedScopeUser extends User
{
    /** {@inheritdoc} */
    #[\Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    #[Scope]
    protected function verified(Builder $builder, bool $email = true)
    {
        return $builder->when(
            $email === true,
            fn ($query) => $query->whereNotNull('email_verified_at'),
            fn ($query) => $query->whereNull('email_verified_at'),
        );
    }

    #[Scope]
    protected function verifiedWithoutReturn(Builder $builder, bool $email = true)
    {
        $this->verified($builder, $email);
    }

    public function scopeVerifiedUser(Builder $builder, bool $email = true)
    {
        return $builder->when(
            $email === true,
            fn ($query) => $query->whereNotNull('email_verified_at'),
            fn ($query) => $query->whereNull('email_verified_at'),
        );
    }
}
