<?php

namespace App\Providers;

use App\Auth\ScopedEloquentUserProvider;
use App\Enums\UserType;
use App\Listeners\MergeGuestCartOnLogin;
use App\Support\Cart\CartResolver;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('scoped', function ($app, array $config) {
            return new ScopedEloquentUserProvider(
                $app['hash'],
                $config['model'],
                array_map(
                    fn (string $case) => UserType::from($case),
                    $config['allowed_user_types'],
                ),
            );
        });

        Event::listen(Login::class, MergeGuestCartOnLogin::class);

        View::composer('layouts.storefront', function ($view) {
            $view->with('cartItemCount', app(CartResolver::class)->peek()?->activeItems()->sum('quantity') ?? 0);
        });
    }
}
