<?php

namespace AntCool\Redius;

use AntCool\Redius\Events\TokenAuthenticated;
use AntCool\Redius\Traits\HasApiTokens;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Guard
{
    public function __construct(protected AuthManager $auth, protected ?string $provider)
    {
    }

    public function __invoke(Request $request): ?Authenticatable
    {
        if ($token = $request->bearerToken()) {
            $accessToken = Redius::findToken($token);

            if (!$this->isValidAccessToken($accessToken)) {
                return null;
            }

            /** @var HasApiTokens $tokenable */
            $tokenable = Redius::findTokenable($accessToken);

            if (!$this->hasValidProvider($tokenable) || !$this->supportsTokens($tokenable)) {
                return null;
            }

            // call custom validation
            if (is_callable(Redius::$accessTokenAuthenticatedCallback) &&
                !(Redius::$accessTokenAuthenticatedCallback)($accessToken, $tokenable)) {
                return null;
            }

            event(new TokenAuthenticated($accessToken, $tokenable));

            return $tokenable->withAccessToken($accessToken->refresh());
        }

        return null;
    }

    protected function isValidAccessToken(?AccessToken $accessToken): bool
    {
        if (!$accessToken) {
            return false;
        }

        if (!$accessToken->expiration) {
            return true;
        }

        return (($accessToken->last_used_at ?? $accessToken->created_at) + 60 * $accessToken->expiration) > time();
    }


    protected function hasValidProvider(Authenticatable $tokenable = null): bool
    {
        if (is_null($this->provider)) {
            return true;
        }

        $model = config("auth.providers.{$this->provider}.model");

        return $tokenable instanceof $model;
    }

    protected function supportsTokens(Authenticatable $tokenable = null): bool
    {
        return $tokenable && in_array(HasApiTokens::class, class_uses_recursive(get_class($tokenable)));
    }
}
