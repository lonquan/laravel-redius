<?php

namespace AntCool\Redius;

use AntCool\Redius\Exceptions\SaveTokenException;
use Illuminate\Foundation\Auth\User as Authenticatable;
use AntCool\Redius\Exceptions\MissingAttributesException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @property int        $id
 * @property int|string $tokenable_id
 * @property string     $tokenable_type
 * @property string     $name
 * @property string     $token
 * @property array      $abilities
 * @property array      $attributes
 * @property int        $expiration unit:minute
 * @property int        $created_at
 * @property int|null   $last_used_at
 */
class AccessToken
{
    protected $fillable = [
        'id', 'token', 'name', 'abilities', 'attributes',
        'expiration', 'tokenable_type', 'tokenable_id',
        'created_at', 'last_used_at',
    ];

    protected array $attributes = [];

    public static function findId(int $id): ?self
    {
        if ($attributes = app(RediusRepository::class)->get(sprintf(Redius::$tokenKey, $id))) {
            return (new self())->withAttributes($attributes);
        }

        return null;
    }

    public static function create(array $attributes, string $token): self
    {
        $attributes['token'] = hash('sha256', $token);
        $attributes['created_at'] = time();
        $attributes['last_used_at'] = null;

        $accessToken = (new self())->withAttributes($attributes);

        throw_unless($accessToken->save(), new SaveTokenException('Token save failed'));

        return $accessToken;
    }

    public function getKey(): int
    {
        throw_unless(isset($this->attributes['id']), new MissingAttributesException('token id missing'));

        return $this->attributes['id'];
    }

    public function refresh(?int $expiration = null): self
    {
        $this->attributes['expiration'] = is_int($expiration) ? $expiration : $this->attributes['expiration'];
        $this->attributes['last_used_at'] = time();

        throw_unless($this->save(), new SaveTokenException('Token refresh failed'));

        return $this;
    }

    public function forget(): bool
    {
        app(RediusRepository::class)->deleteMultiple([
            sprintf(Redius::$tokenKey, $this->id),
            sprintf(Redius::$tokenableKey, $this->tokenable_type, $this->tokenable_id),
        ]);

        return true;
    }

    public function save()
    {
        throw_if(empty($this->attributes), new MissingAttributesException('token attributes is empty'));

        $this->attributes['id'] = $this->attributes['id'] ?? app()->make(RediusRepository::class)->increment(Redius::$autoIncrKey);

        return app()->make(RediusRepository::class)->put(
            $this->getTokenCacheKey($this->getKey()),
            $this->attributes,
            $this->attributes['expiration']
        );
    }

    public function withAttributes(array $attributes): self
    {
        $this->attributes = Arr::only($attributes, $this->fillable);

        return $this;
    }

    public function can($ability)
    {
        return in_array('*', $this->abilities) || array_key_exists($ability, array_flip($this->abilities));
    }

    public function cant($ability)
    {
        return !$this->can($ability);
    }

    public function __get($name)
    {
        return $this->attributes[$name] ?? null;
    }

    protected function getTokenCacheKey(int $id): string
    {
        return sprintf(Redius::$tokenKey, $id);
    }
}
