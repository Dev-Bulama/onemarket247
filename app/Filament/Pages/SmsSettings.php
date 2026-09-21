<?php

namespace App\Filament\Pages;

use App\Models\SmsSetting;
use App\Notifications\Channels\AfricasTalkingChannel;
use App\Notifications\Messages\SmsMessage;
use App\Support\AuditLogger;
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
 * Lets an admin configure a real Africa's Talking account (username + API
 * key) so any notification that adds AfricasTalkingChannel::class to its
 * via() can actually deliver an SMS — same "admin enters real credentials,
 * never hardcoded" shape as MailSettings/PushSettings.
 */
class SmsSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.sms-settings';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'SMS Notifications';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::guard('admin')->user()?->can('notifications.manage') ?? false;
    }

    public function mount(): void
    {
        $settings = SmsSetting::current();

        $this->form->fill([
            ...$settings->only(['is_active', 'sandbox', 'username', 'sender_id']),
            'api_key' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make("Africa's Talking")
                    ->description('Leave the API key blank to keep the one already saved. Find your credentials in your Africa\'s Talking dashboard. While inactive, no SMS is sent — recipients still get their email and in-app notification.')
                    ->columns(1)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Send SMS notifications')
                            ->columnSpanFull(),
                        Toggle::make('sandbox')
                            ->label('Sandbox mode')
                            ->helperText('Use your sandbox app credentials for testing before going live.')
                            ->columnSpanFull(),
                        TextInput::make('username')
                            ->label('Username')
                            ->helperText('Your Africa\'s Talking application username ("sandbox" while testing).'),
                        TextInput::make('api_key')
                            ->label('API Key')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn ($state) => filled($state)),
                        TextInput::make('sender_id')
                            ->label('Sender ID')
                            ->helperText('Optional alphanumeric sender ID or shortcode, if you have one registered.'),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if (! filled($data['api_key'] ?? null)) {
            unset($data['api_key']);
        }

        $setting = SmsSetting::current();
        $before = $setting->only(array_keys($data));
        $setting->update($data);

        AuditLogger::record('sms_settings.updated', $setting, static::redactApiKey($before), static::redactApiKey($data));

        Notification::make()->title('SMS settings saved')->success()->send();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function redactApiKey(array $data): array
    {
        if (array_key_exists('api_key', $data)) {
            $data['api_key'] = '[redacted]';
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTestSms')
                ->label('Send test SMS')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->schema([
                    TextInput::make('phone')
                        ->label('Send to phone number')
                        ->required()
                        ->helperText('Full international format, e.g. +2348012345678.'),
                ])
                ->action(function (array $data): void {
                    $settings = SmsSetting::current();

                    if (! $settings->username || ! $settings->api_key) {
                        Notification::make()->title('Save your username and API key first')->danger()->send();

                        return;
                    }

                    try {
                        AfricasTalkingChannel::sendRaw(
                            $settings,
                            SmsMessage::create($data['phone'], 'Test SMS from '.config('app.name').' — if you\'re reading this, your SMS settings work.'),
                        );

                        Notification::make()->title('Test SMS sent')->success()->send();
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('Could not send the test SMS')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
