<?php

namespace App\Filament\Pages;

use App\Models\DeviceToken;
use App\Models\PushSetting;
use App\Notifications\Channels\OneSignalChannel;
use App\Notifications\Messages\OneSignalMessage;
use Filament\Actions\Action;
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
use Throwable;

/**
 * Lets an admin configure a real OneSignal account (App ID + REST API
 * key) so AdminBroadcastNotification (and any future notification that
 * adds OneSignalChannel::class to its via()) can actually deliver a
 * native push to the mobile app — same "admin enters real credentials,
 * never hardcoded" shape as MailSettings.
 */
class PushSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.push-settings';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Push Notifications';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::guard('admin')->user()?->can('notifications.manage') ?? false;
    }

    public function mount(): void
    {
        $settings = PushSetting::current();

        $this->form->fill([
            ...$settings->only(['is_active', 'app_id']),
            'rest_api_key' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('OneSignal')
                    ->description('Leave the REST API key blank to keep the one already saved. Find both values in your OneSignal dashboard under Settings → Keys & IDs. While inactive, no push notifications are sent — recipients still get their email and in-app notification.')
                    ->columns(1)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Send push notifications')
                            ->columnSpanFull(),
                        TextInput::make('app_id')
                            ->label('OneSignal App ID')
                            ->helperText('This is also the value the mobile app itself is built with — see mobile/src/config/api.ts.'),
                        TextInput::make('rest_api_key')
                            ->label('REST API Key')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn ($state) => filled($state)),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if (! filled($data['rest_api_key'] ?? null)) {
            unset($data['rest_api_key']);
        }

        PushSetting::current()->update($data);

        Notification::make()->title('Push settings saved')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTestPush')
                ->label('Send test push')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->schema([
                    TextInput::make('device_token')
                        ->label('Send to device token')
                        ->required()
                        ->helperText('A OneSignal player id already registered via the mobile app — see the device_tokens table, or your own test device after opening the app once.'),
                ])
                ->action(function (array $data): void {
                    $token = $data['device_token'];

                    if (! DeviceToken::where('token', $token)->exists()) {
                        Notification::make()->title('No device is registered with that token')->danger()->send();

                        return;
                    }

                    $settings = PushSetting::current();

                    if (! $settings->app_id || ! $settings->rest_api_key) {
                        Notification::make()->title('Save your App ID and REST API key first')->danger()->send();

                        return;
                    }

                    try {
                        // Calls the same API OneSignalChannel::send() does, but
                        // without swallowing failures — this action needs real
                        // success/failure feedback, unlike a live notification
                        // send where a push failure must never block mail/database.
                        OneSignalChannel::sendRaw(
                            $settings->app_id,
                            $settings->rest_api_key,
                            [$token],
                            OneSignalMessage::create('Test push — '.config('app.name'), "If you're reading this, your push settings work."),
                        );

                        Notification::make()->title('Test push sent')->success()->send();
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('Could not send the test push')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
