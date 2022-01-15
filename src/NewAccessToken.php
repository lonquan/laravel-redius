<?php

namespace AntCool\Redius;

use AntCool\Redius\AccessToken;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class NewAccessToken implements Arrayable, Jsonable
{
    public function __construct(public AccessToken $accessToken, public string $plainTextToken)
    {
    }

    public function toArray(): array
    {
        return [
            'accessToken' => $this->accessToken,
            'plainTextToken' => $this->plainTextToken,
        ];
    }

    public function toJson($options = JSON_ERROR_NONE): string
    {
        return json_encode($this->toArray(), $options);
    }
}
