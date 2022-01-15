<?php

namespace AntCool\Redius;

use Illuminate\Auth\AuthManager;
use Illuminate\Auth\RequestGuard;
use Illuminate\Cache\RedisStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class RediusServiceProvider extends ServiceProvider
{
    public function register()
    {
        config([
            'auth.guards.redius' => array_merge(
                ['driver' => 'redius', 'provider' => null],
                config('auth.guards.redius', [])
            ),
        ]);

        $this->registerRepository();
    }

    public function boot()
    {
        $this->configureGuard();
    }

    protected function registerRepository()
    {
        $this->app->singleton(
            RediusRepository::class,
            fn ($app) => new RediusRepository(
                new RedisStore(
                    $app->make('redis'),
                    config('auth.redius.prefix', 'redius'),
                    config('auth.redius.connection', 'default'),
                )
            )
        );
    }

    protected function configureGuard()
    {
        Auth::resolved(
            fn ($auth) => $auth->extend('redius',
                fn ($app, $name, array $config) => tap(
                    $this->createGuard($auth, $config),
                    fn ($guard) => app()->refresh('request', $guard, 'setRequest')
                ))
        );
    }

    protected function createGuard(AuthManager $auth, $config): RequestGuard
    {
        return new RequestGuard(
            new Guard($auth, $config['provider'] ?? null), request(), null
        );
    }
}
