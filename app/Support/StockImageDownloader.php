<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Fetches a real, deterministic stock photo for a given seed (same seed
 * always returns the same photo) so demo/seed content and default hero
 * imagery look like real product photography instead of a generated
 * placeholder. Network calls are short-timeout and failures are swallowed
 * so a host with no outbound internet still seeds successfully — callers
 * fall back to a locally generated placeholder when this returns null.
 */
class StockImageDownloader
{
    public static function download(string $seed, int $width = 800, int $height = 800): ?string
    {
        try {
            $response = Http::timeout(5)->get("https://picsum.photos/seed/{$seed}/{$width}/{$height}.jpg");
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful() || ! str_starts_with((string) $response->header('Content-Type'), 'image/')) {
            return null;
        }

        $directory = storage_path('app/tmp-seed-images');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $path = $directory.'/'.Str::random(16).'.jpg';
        file_put_contents($path, $response->body());

        return $path;
    }
}
