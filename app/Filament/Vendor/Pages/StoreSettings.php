<?php

namespace App\Filament\Vendor\Pages;

use App\Enums\StoreStatus;
use App\Enums\UserType;
use App\Models\Store;
use Filament\Forms\Components\KeyValue;
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
 * A store is a singleton per vendor (see Vendor::store(): HasOne), so this
 * is a settings-style Page rather than a Resource — there is never a list
 * of stores to browse from the vendor's own panel.
 */
class StoreSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.vendor.pages.store-settings';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = Auth::guard('vendor')->user();

        return $user->user_type === UserType::VendorOwner
            || $user->can('store.settings.manage');
    }

    public function mount(): void
    {
        $this->form->fill($this->store()->only([
            'name', 'description', 'email', 'phone', 'address',
            'country_id', 'state_id', 'city_id',
            'status', 'seo_title', 'seo_description', 'social_links',
            'working_hours', 'vacation_message',
        ]));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('email')
                    ->email(),
                TextInput::make('phone'),
                Textarea::make('address'),
                Select::make('status')
                    ->label('Store status')
                    ->options([
                        StoreStatus::Active->value => StoreStatus::Active->getLabel(),
                        StoreStatus::Vacation->value => StoreStatus::Vacation->getLabel(),
                    ])
                    ->required()
                    ->helperText('Deactivation is handled by the platform, not from here.'),
                Textarea::make('vacation_message')
                    ->visible(fn ($get) => $get('status') === StoreStatus::Vacation->value),
                TextInput::make('seo_title')->maxLength(255),
                Textarea::make('seo_description')->maxLength(255),
                KeyValue::make('social_links'),
                KeyValue::make('working_hours'),
            ]);
    }

    public function save(): void
    {
        $this->store()->update($this->form->getState());

        Notification::make()->title('Store settings saved')->success()->send();
    }

    private function store(): Store
    {
        $vendorId = Auth::guard('vendor')->user()->actingVendorId();

        return Store::withoutGlobalScopes()->where('vendor_id', $vendorId)->firstOrFail();
    }
}
