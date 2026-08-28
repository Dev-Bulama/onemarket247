<?php

namespace App\Filament\Pages;

use App\Actions\Admin\SendAdminMessageAction;
use App\Enums\AdminMessageAudience;
use App\Enums\UserType;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * A direct admin -> user broadcast (see App\Actions\Admin\SendAdminMessageAction),
 * distinct from the automated transactional notifications every other
 * Notification class in this app sends — this is for platform
 * announcements, policy updates, etc. Delivered via the same mail
 * branding plus the database channel, so it also shows up in the
 * recipient's account notifications (and GET /api/v1/notifications).
 */
class SendAdminMessage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.send-admin-message';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Send Message';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::guard('admin')->user()?->can('notifications.manage') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(['audience' => AdminMessageAudience::AllCustomers->value]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Select::make('audience')
                    ->options(AdminMessageAudience::class)
                    ->live()
                    ->required(),
                Select::make('user_ids')
                    ->label('Recipients')
                    ->multiple()
                    ->searchable()
                    ->options(fn () => User::whereIn('user_type', [UserType::Customer, UserType::VendorOwner, UserType::VendorStaff])
                        ->limit(50)
                        ->pluck('email', 'id'))
                    ->getSearchResultsUsing(fn (string $search) => User::whereIn('user_type', [UserType::Customer, UserType::VendorOwner, UserType::VendorStaff])
                        ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                        ->limit(50)
                        ->pluck('email', 'id'))
                    ->visible(fn ($get) => $get('audience') === AdminMessageAudience::Specific)
                    ->required(fn ($get) => $get('audience') === AdminMessageAudience::Specific),
                TextInput::make('subject')
                    ->required()
                    ->maxLength(255),
                Textarea::make('body')
                    ->required()
                    ->rows(8)
                    ->helperText('One paragraph per line.'),
            ]);
    }

    public function send(): void
    {
        $data = $this->form->getState();

        $audience = $data['audience'] instanceof AdminMessageAudience
            ? $data['audience']
            : AdminMessageAudience::from($data['audience']);

        $count = app(SendAdminMessageAction::class)->handle(
            $audience,
            $data['subject'],
            $data['body'],
            $data['user_ids'] ?? [],
            Auth::guard('admin')->user(),
        );

        $this->form->fill(['audience' => AdminMessageAudience::AllCustomers->value]);

        Notification::make()->title("Message sent to {$count} ".str('recipient')->plural($count))->success()->send();
    }
}
