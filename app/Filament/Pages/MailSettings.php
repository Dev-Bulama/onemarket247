<?php

namespace App\Filament\Pages;

use App\Models\MailSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Lets an admin configure real SMTP credentials and the branding every
 * outbound notification uses (see MailConfigServiceProvider and the
 * customized resources/views/vendor/mail/* theme) without ever needing
 * .env/server access — the exact problem behind the "Connection could not
 * be established with host 127.0.0.1:2525" crash this page exists to fix.
 */
class MailSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.mail-settings';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Mail Settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::guard('admin')->user()?->can('smtp.manage') ?? false;
    }

    public function mount(): void
    {
        $settings = MailSetting::current();

        $this->form->fill([
            ...$settings->only([
                'is_active', 'mailer', 'host', 'port', 'username', 'encryption',
                'from_address', 'from_name', 'logo_url', 'primary_color', 'footer_text',
            ]),
            'password' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('SMTP transport')
                    ->description('Leave the password blank to keep the one already saved. While inactive, the platform falls back to whatever is configured in the server .env file.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Use these settings')
                            ->columnSpanFull(),
                        Select::make('mailer')
                            ->options(['smtp' => 'SMTP'])
                            ->default('smtp')
                            ->required(),
                        TextInput::make('host')->label('Outgoing (SMTP) server'),
                        TextInput::make('port')->numeric(),
                        Select::make('encryption')
                            ->options(['tls' => 'TLS', 'ssl' => 'SSL', '' => 'None'])
                            ->default('tls'),
                        TextInput::make('username'),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn ($state) => filled($state)),
                        TextInput::make('from_address')
                            ->label('From email address')
                            ->email(),
                        TextInput::make('from_name')
                            ->label('From name'),
                    ]),
                Section::make('Branding')
                    ->description('Applied to every notification email sent by the platform.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('logo_url')
                            ->label('Logo URL')
                            ->url()
                            ->helperText('A hosted image URL — shown in the header of every email instead of the site name.')
                            ->columnSpanFull(),
                        ColorPicker::make('primary_color')
                            ->label('Accent color')
                            ->helperText('Used for the button in every email.'),
                        Textarea::make('footer_text')
                            ->label('Footer text')
                            ->helperText('Replaces the default "© year Site Name" line.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if (! filled($data['password'] ?? null)) {
            unset($data['password']);
        }

        MailSetting::current()->update($data);

        Notification::make()->title('Mail settings saved')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTestEmail')
                ->label('Send test email')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->schema([
                    TextInput::make('to')
                        ->label('Send to')
                        ->email()
                        ->required()
                        ->default(fn () => Auth::guard('admin')->user()?->email),
                ])
                ->action(function (array $data): void {
                    $to = $data['to'];

                    try {
                        Mail::raw(
                            'This is a test email from '.config('app.name').". If you're reading this, your mail settings work.",
                            fn ($message) => $message->to($to)->subject('Test email — '.config('app.name')),
                        );

                        Notification::make()->title("Test email sent to {$to}")->success()->send();
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('Could not send the test email')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
