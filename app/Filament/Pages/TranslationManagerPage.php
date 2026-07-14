<?php

namespace App\Filament\Pages;

use App\Models\Language;
use App\Models\Product;
use App\Models\ProductTranslation;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Missing-translation report for ProductTranslation (see docs/architecture/05-filament-resources.md).
 * Export produces a CSV of products with at least one active language
 * missing a translation row; import bulk-upserts ProductTranslation rows
 * from a CSV in the same shape (matched on product SKU + language code).
 */
class TranslationManagerPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.translation-manager';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Translation Manager';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return Auth::guard('admin')->user()?->can('products.update') ?? false;
    }

    public function table(Table $table): Table
    {
        $activeLanguages = Language::query()->where('is_active', true)->orderBy('code')->get();

        return $table
            ->query(Product::query()->with(['translations.language']))
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('sku')
                    ->searchable(),
                TextColumn::make('missing_languages')
                    ->label('Missing translations')
                    ->state(function (Product $record) use ($activeLanguages) {
                        $translatedCodes = $record->translations->pluck('language.code')->filter();
                        $missing = $activeLanguages->pluck('code')->diff($translatedCodes)->values();

                        return $missing->isEmpty() ? 'Complete' : $missing->implode(', ');
                    })
                    ->badge()
                    ->color(fn (string $state) => $state === 'Complete' ? 'success' : 'danger'),
            ])
            ->headerActions([
                Action::make('exportMissing')
                    ->label('Export missing translations (CSV)')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(route('admin.translation-report.export'))
                    ->openUrlInNewTab(),
                Action::make('importTranslations')
                    ->label('Import translations (CSV)')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->schema([
                        FileUpload::make('file')
                            ->label('CSV file')
                            ->acceptedFileTypes(['text/csv', 'text/plain'])
                            ->disk('local')
                            ->directory('tmp-translation-imports')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $imported = $this->importFromCsv($data['file']);

                        Notification::make()
                            ->title("Imported {$imported} translation rows")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    /**
     * Expects columns: sku, language_code, name, short_description,
     * description, seo_title, seo_description. Rows referencing an
     * unknown SKU or language code are skipped.
     */
    private function importFromCsv(string $path): int
    {
        $stream = Storage::disk('local')->readStream($path);

        $header = fgetcsv($stream);
        $count = 0;

        while (($row = fgetcsv($stream)) !== false) {
            $row = array_combine($header, $row);

            $product = Product::where('sku', $row['sku'] ?? null)->first();
            $language = Language::where('code', $row['language_code'] ?? null)->first();

            if (! $product || ! $language) {
                continue;
            }

            ProductTranslation::updateOrCreate(
                ['product_id' => $product->id, 'language_id' => $language->id],
                [
                    'name' => $row['name'] ?: null,
                    'short_description' => $row['short_description'] ?: null,
                    'description' => $row['description'] ?: null,
                    'seo_title' => $row['seo_title'] ?: null,
                    'seo_description' => $row['seo_description'] ?: null,
                ],
            );

            $count++;
        }

        fclose($stream);
        Storage::disk('local')->delete($path);

        return $count;
    }
}
