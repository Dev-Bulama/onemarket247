<?php

namespace App\Providers;

use App\Auth\ScopedEloquentUserProvider;
use App\Enums\UserType;
use App\Listeners\MergeGuestCartOnLogin;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
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

        Blade::directive('price', fn ($expression) => "<?php echo \App\Support\PriceDisplay::format({$expression}); ?>");
    }
}
