<?php

use App\Models\AppSetting;

test('bootstrap defaults to the production API URL when force_production is on', function () {
    AppSetting::current()->update([
        'production_api_url' => 'https://onemarket247.com/api/v1',
        'local_api_url' => 'http://192.168.1.50:8000/api/v1',
        'active_environment' => 'local',
        'force_production' => true,
    ]);

    $this->getJson('/api/v1/bootstrap')
        ->assertOk()
        ->assertJsonPath('data.api_base_url', 'https://onemarket247.com/api/v1');
});

test('bootstrap resolves to the local URL when force_production is off and local is active', function () {
    AppSetting::current()->update([
        'production_api_url' => 'https://onemarket247.com/api/v1',
        'local_api_url' => 'http://192.168.1.50:8000/api/v1',
        'active_environment' => 'local',
        'force_production' => false,
    ]);

    $this->getJson('/api/v1/bootstrap')
        ->assertOk()
        ->assertJsonPath('data.api_base_url', 'http://192.168.1.50:8000/api/v1');
});

test('bootstrap falls back to production when local is active but no local URL is set', function () {
    AppSetting::current()->update([
        'production_api_url' => 'https://onemarket247.com/api/v1',
        'local_api_url' => null,
        'active_environment' => 'local',
        'force_production' => false,
    ]);

    $this->getJson('/api/v1/bootstrap')
        ->assertOk()
        ->assertJsonPath('data.api_base_url', 'https://onemarket247.com/api/v1');
});

test('bootstrap returns branding and app name, falling back to config app name when unset', function () {
    AppSetting::current()->update([
        'production_api_url' => 'https://onemarket247.com/api/v1',
        'app_name' => null,
        'logo_url' => 'https://onemarket247.com/logo.png',
        'splash_logo_url' => 'https://onemarket247.com/splash.png',
        'min_app_version' => '1.2.0',
    ]);

    $response = $this->getJson('/api/v1/bootstrap')->assertOk();

    expect($response->json('data.app_name'))->toBe(config('app.name'))
        ->and($response->json('data.logo_url'))->toBe('https://onemarket247.com/logo.png')
        ->and($response->json('data.splash_logo_url'))->toBe('https://onemarket247.com/splash.png')
        ->and($response->json('data.min_app_version'))->toBe('1.2.0');
});

test('bootstrap returns the configured product grid column count, defaulting to 4', function () {
    AppSetting::current()->update(['production_api_url' => 'https://onemarket247.com/api/v1']);

    $this->getJson('/api/v1/bootstrap')
        ->assertOk()
        ->assertJsonPath('data.product_grid_columns', 4);

    AppSetting::current()->update(['product_grid_columns' => 3]);

    $this->getJson('/api/v1/bootstrap')
        ->assertOk()
        ->assertJsonPath('data.product_grid_columns', 3);
});
