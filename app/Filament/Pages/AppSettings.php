<?php

namespace App\Filament\Pages;

use App\Enums\AppEnvironment;
use App\Models\AppSetting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * Controls what the mobile app resolves at cold start via
 * GET /api/v1/bootstrap (see App\Http\Controllers\Api\V1\BootstrapController
 * and App\Models\AppSetting) — which backend it actually talks to, and its
 * branding — without needing a new app build for either.
 */
class AppSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.app-settings';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'App Settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::guard('admin')->user()?->can('settings.manage') ?? false;
    }

    public function mount(): void
    {
        $settings = AppSetting::current();

        $this->form->fill($settings->only([
            'active_environment', 'local_api_url', 'production_api_url', 'force_production',
            'app_name', 'logo_url', 'splash_logo_url', 'min_app_version',
        ]));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Backend environment')
                    ->description("Controls which API URL the mobile app uses — read once by every already-installed app the next time it starts, with no app-store update needed. \"Force production\" is a safety net: while it's on, every app always uses the production URL below regardless of the environment picked here, so testing \"Local\" can never accidentally strand real users on an unreachable server.")
                    ->columns(2)
                    ->schema([
                        Toggle::make('force_production')
                            ->label('Force production for everyone')
                            ->columnSpanFull()
                            ->default(true),
                        Select::make('active_environment')
                            ->options(AppEnvironment::class)
                            ->required(),
                        TextInput::make('production_api_url')
                            ->label('Production API URL')
                            ->url()
                            ->required()
                            ->placeholder('https://onemarket247.com/api/v1'),
                        TextInput::make('local_api_url')
                            ->label('Local/staging API URL')
                            ->url()
                            ->helperText('Only used when Active Environment is Local and Force Production is off — e.g. a tunnel URL to a developer\'s machine.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Branding')
                    ->description('Read by the mobile app on startup, alongside the environment above.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('app_name')
                            ->helperText('Falls back to "'.config('app.name').'" when blank.'),
                        TextInput::make('min_app_version')
                            ->label('Minimum app version')
                            ->helperText('e.g. "1.2.0" — an installed app older than this shows an update-required screen instead of continuing. Leave blank to disable.'),
                        TextInput::make('logo_url')
                            ->label('App logo URL')
                            ->url()
                            ->columnSpanFull(),
                        TextInput::make('splash_logo_url')
                            ->label('Splash screen logo URL')
                            ->url()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        AppSetting::current()->update($data);

        Notification::make()->title('App settings saved')->success()->send();
    }
}
