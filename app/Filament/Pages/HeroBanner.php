<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Uploads the storefront homepage hero photo directly to
 * storage/app/public/hero/slide-1.jpg — the exact path
 * resources/views/storefront/home.blade.php already checks for. Not backed
 * by an Eloquent model (there's nothing to list/paginate, just one file),
 * so this is a settings-style Page rather than a Resource, mirroring
 * App\Filament\Vendor\Pages\StoreSettings's shape.
 *
 * Deliberately manual (admin picks the exact photo) rather than an
 * automated stock-photo fetch: see DatabaseSeeder's comment on why
 * HeroImageSeeder was removed from the default seed chain.
 */
class HeroBanner extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.hero-banner';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Hero Banner';

    private const TARGET_PATH = 'hero/slide-1.jpg';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::guard('admin')->user()?->can('settings.manage') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                FileUpload::make('image')
                    ->label('Hero photo')
                    ->image()
                    ->disk('public')
                    ->directory('tmp-hero-banner')
                    ->visibility('public')
                    ->maxSize(8192)
                    ->helperText('Recommended: a landscape photo around 1600×900px. Replaces the current hero photo immediately on save.'),
            ]);
    }

    public function save(): void
    {
        $path = $this->form->getState()['image'] ?? null;

        if (! $path || ! Storage::disk('public')->exists($path)) {
            Notification::make()->title('Choose a photo first')->warning()->send();

            return;
        }

        Storage::disk('public')->put(self::TARGET_PATH, Storage::disk('public')->get($path));
        Storage::disk('public')->delete($path);

        $this->form->fill();

        Notification::make()->title('Hero photo updated')->success()->send();
    }

    public function removeImage(): void
    {
        Storage::disk('public')->delete(self::TARGET_PATH);

        Notification::make()->title('Hero photo removed')->success()->send();
    }

    public function currentImageUrl(): ?string
    {
        if (! Storage::disk('public')->exists(self::TARGET_PATH)) {
            return null;
        }

        return Storage::disk('public')->url(self::TARGET_PATH).'?v='.Storage::disk('public')->lastModified(self::TARGET_PATH);
    }
}
