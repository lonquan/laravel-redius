<?php

namespace AntCool\Redius;

use AntCool\Redius\Exceptions\MissingTokenableException;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

class Redius
{
    public static $accessTokenAuthenticatedCallback;

    public static $cacheTokenable = true;

    public static $autoIncrKey = 'auto_incr_id';

    /** @var string $tokenableKey tokenables:tokenable_type:tokenable_id */
    public static $tokenableKey = 'tokenables:%s:%s';

    /** @var string $tokenKey tokens:id */
    public static $tokenKey = 'tokens:%s';

    public static function withAccessTokenAuthenticatedCallback(callable $callback)
    {
        self::$accessTokenAuthenticatedCallback = $callback;
    }

    public static function disableTokenableCache()
    {
        self::$cacheTokenable = false;
    }

    public static function createToken(Authenticatable $tokenable, array $attributes = []): NewAccessToken
    {
        $token = AccessToken::create(
            array_merge($attributes, ['tokenable_type' => get_class($tokenable), 'tokenable_id' => $tokenable->getKey()]),
            $plainTextToken = Str::random(40)
        );

        self::$cacheTokenable && app(RediusRepository::class)->put(
            sprintf(self::$tokenableKey, $token->tokenable_type, $token->tokenable_id),
            $tokenable?->unsetRelations(),
            $token->expiration
        );

        return new NewAccessToken($token, $token->getKey() . '|' . $plainTextToken);
    }

    public static function findToken(string $token): ?AccessToken
    {
        [$id, $token] = explode('|', $token);

        if ($id && ($token ?? false) && $accessToken = AccessToken::findId($id)) {

            return hash_equals($accessToken->token, hash('sha256', $token)) ? $accessToken : null;
        }

        return null;
    }

    public static function findTokenable(AccessToken $accessToken): Authenticatable
    {
        $tokenable = null;

        $tokenableKey = sprintf(self::$tokenableKey, $accessToken->tokenable_type, $accessToken->tokenable_id);

        self::$cacheTokenable && $tokenable = app(RediusRepository::class)->get($tokenableKey);

        if (!$tokenable) {
            /** @var \Illuminate\Database\Eloquent\Builder $model */
            $model = $accessToken->tokenable_type::query();
            $tokenable = $model->find($accessToken->tokenable_id);

            throw_unless($tokenable, new MissingTokenableException('Tokenable missing'));

            self::$cacheTokenable && app(RediusRepository::class)->put(
                $tokenableKey,
                $tokenable,
                $accessToken->expiration
            );
        }

        return $tokenable;
    }

    public static function refreshTokenable(Authenticatable $tokenable, ?int $expiration = null): bool
    {
        return app(RediusRepository::class)->put(
            sprintf(self::$tokenableKey, get_class($tokenable), $tokenable->getKey()),
            $tokenable->unsetRelations(),
            $expiration
        );
    }

    public static function forgetTokenable(Authenticatable $tokenable): bool
    {
        return app(RediusRepository::class)->forget(
            sprintf(self::$tokenableKey, get_class($tokenable), $tokenable->getKey())
        );
    }

    public static function flush(): bool
    {
        return app(RediusRepository::class)->clear();
    }
}
