<?php

namespace App\Providers;

use App\Auth\ScopedEloquentUserProvider;
use App\Enums\UserType;
use App\Listeners\MergeGuestCartOnLogin;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Resources\Json\JsonResource;
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

        // Every /api/v1/* response is already wrapped in ApiResponse's own
        // {data, meta, message} envelope — a Resource collection's default
        // {data: [...]} wrapper would double-nest ('data.products.data.0'
        // instead of 'data.products.0') wherever a resource collection sits
        // inside that envelope, so it's disabled globally instead of on
        // every resource class individually.
        JsonResource::withoutWrapping();
    }
}
