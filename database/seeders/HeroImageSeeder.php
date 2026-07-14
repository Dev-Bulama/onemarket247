<?php

namespace Database\Seeders;

use App\Support\StockImageDownloader;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Downloads a real stock photo for each homepage hero slide so the hero
 * section shows real photography instead of a flat colour background.
 * Safe to re-run: skips any slide whose file already exists. If the
 * download fails (e.g. no outbound internet), the slide is simply left
 * without a background image and the homepage falls back to its solid
 * colour gradient — never a hard failure.
 */
class HeroImageSeeder extends Seeder
{
    private const SLIDES = [
        1 => 'hero-marketplace-shopping',
        2 => 'hero-categories-retail',
        3 => 'hero-vendor-business',
    ];

    public function run(): void
    {
        foreach (self::SLIDES as $number => $seed) {
            $relativePath = "hero/slide-{$number}.jpg";

            if (Storage::disk('public')->exists($relativePath)) {
                continue;
            }

            $downloadedPath = StockImageDownloader::download($seed, 1600, 900);

            if (! $downloadedPath) {
                continue;
            }

            Storage::disk('public')->put($relativePath, file_get_contents($downloadedPath));
            unlink($downloadedPath);
        }
    }
}
