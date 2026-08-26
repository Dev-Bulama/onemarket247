<?php

namespace Database\Seeders;

use App\Models\HeroSlide;
use App\Support\StockImageDownloader;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Downloads a real stock photo for each homepage hero slide so the hero
 * carousel shows real photography instead of a flat colour background.
 * Safe to re-run: does nothing once any HeroSlide rows already exist,
 * so it never overwrites photos an admin picked deliberately through the
 * Hero Slides admin resource. If a download fails (e.g. no outbound
 * internet), that slide is simply skipped — never a hard failure.
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
        if (HeroSlide::query()->exists()) {
            return;
        }

        foreach (self::SLIDES as $number => $seed) {
            $relativePath = "hero/slide-{$number}.jpg";

            $downloadedPath = StockImageDownloader::download($seed, 1600, 900);

            if (! $downloadedPath) {
                continue;
            }

            Storage::disk('public')->put($relativePath, file_get_contents($downloadedPath));
            unlink($downloadedPath);

            HeroSlide::create([
                'image_path' => $relativePath,
                'sort_order' => $number - 1,
                'is_active' => true,
            ]);
        }
    }
}
