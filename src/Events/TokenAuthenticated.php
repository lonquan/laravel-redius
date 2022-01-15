<?php

namespace AntCool\Redius\Events;

use AntCool\Redius\AccessToken;
use Illuminate\Foundation\Auth\User as Authenticatable;

class TokenAuthenticated
{
    public AccessToken $token;
    public Authenticatable $tokenable;

    /**
     * Create a new event instance.
     *
     * @param AccessToken $token
     *
     * @return void
     */
    public function __construct(AccessToken $token, Authenticatable $tokenable)
    {
        $this->token = $token;
        $this->tokenable = $tokenable;
    }
}
