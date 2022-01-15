<h1 align="center"> Redius for Laravel </h1>

<p align="center"> Redis 驱动的用户认证 (Bearer) 服务.</p>

## 安装

```shell
$ composer require antcool/redius -vvv
```

## 配置(可选)

```php
// config/auth.php
return  [
    'guards' => [
        // ...
        'redius' => [
            'driver' => 'redius',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        // ...
        'users' => [
            'driver' => 'eloquent',
            'model' => \App\Models\User::class
        ],
    ],
    
    'redius' => [
        'prefix' => 'redius',
        'connection' => 'default',
    ] 
]

// config/database.php
return [
    // ...
    'redius' => [
        'url' => null,
        'host' => env('REDIUS_HOST', '127.0.0.1'),
        'password' => env('REDIUS_PASSWORD', null),
        'port' => env('REDIUS_PORT', '6379'),
        'database' => env('REDIUS_DB', 8),
     ],
]


// app/Providers/AuthServiceProvider.php
// 禁用模型缓存(仅缓存 token 内容, 不缓存用户信息)
\AntCool\Redius\Redius::disableTokenableCache();

// 自定义 AccessToken 验证
\AntCool\Redius\Redius::withAccessTokenAuthenticatedCallback(
    function(\AntCool\Redius\AccessToken $token, Authenticatable $tokenable): bool {
    
    }
);
```

## Usage

```php
// App\Models\User
class User extends Authenticatable
{
    use HasApiTokens;
}

$user = User::query()->first();

$token = $user->createToken(
    name: 'token name', 
    expiration: 30, // 过期时间(分钟, null 为一直有效) 
    abilities: ['*'], 
    attributes: ['you_custom_key' => 'val', 'you_custom_key' => 'val'],
);

// $token->plainTextToken
// $token->accessToken

// 请求 headers 添加: Authorization:Bearer {$plainTextToken}

// 路由添加中间件: auth:redius

// 获取经过认证后的用户
// auth('redius')->user() 

// 其他方法
$user->tokenCan()
$user->forgetTokenable()
$user->forgetToken()
$user->refreshTokenable()
$user->refreshToken()
$user->currentAccessToken()

Redius::flush()
```

## License

MIT
