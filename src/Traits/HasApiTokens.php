<?php

namespace AntCool\Redius\Traits;

use AntCool\Redius\AccessToken;
use AntCool\Redius\Exceptions\TokenAttributeException;
use AntCool\Redius\NewAccessToken;
use AntCool\Redius\Redius;

trait HasApiTokens
{
    protected AccessToken $accessToken;

    public function forgetTokenable(): bool
    {
        return Redius::forgetTokenable($this);
    }

    public function refreshTokenable(int $expiration = null): bool
    {
        return Redius::refreshTokenable($this, $expiration ? $expiration : $this->currentAccessToken()->expiration);
    }

    public function forgetToken(): bool
    {
        return $this->currentAccessToken()->forget();
    }

    public function refreshToken(): AccessToken
    {
        return $this->currentAccessToken()->refresh();
    }

    public function tokenCan(string $ability): bool
    {
        return $this->currentAccessToken()->can($ability);
    }

    public function createToken(string $name, ?int $expiration = 30, array $abilities = ['*'], array $attributes = []): NewAccessToken
    {
        throw_unless(
            is_null($expiration) || (is_int($expiration) && $expiration > 0),
            new TokenAttributeException('Expiration time only allows null or numbers greater than zero')
        );

        return Redius::createToken(
            $this,
            [
                'name' => $name, 'expiration' => is_int($expiration) ? $expiration * 60 : null,
                'abilities' => $abilities, 'attributes' => $attributes,
            ]
        );
    }

    public function currentAccessToken(): AccessToken
    {
        return $this->accessToken;
    }

    public function withAccessToken(AccessToken $accessToken): static
    {
        $this->accessToken = $accessToken;

        return $this;
    }
}
