<?php

namespace App\Filament\Vendor\Resources\Products\Concerns;

use App\Models\Product;
use App\Models\ProductDigitalFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * FileUpload components stage files on a public/private "tmp-*" directory
 * (see ProductForm) because they aren't backed by a real product_id yet at
 * form-fill time. This trait moves those staged files into their real,
 * permanent home once the owning Product exists — Spatie MediaLibrary for
 * images, ProductDigitalFile + the private disk for protected downloads —
 * and removes the temporary copies either way.
 */
trait HandlesProductMedia
{
    private function attachStagedImages(Product $product, array $paths): void
    {
        foreach ($paths as $path) {
            if (! Storage::disk('public')->exists($path)) {
                continue;
            }

            $product->addMediaFromDisk($path, 'public')->toMediaCollection('images');
            Storage::disk('public')->delete($path);
        }
    }

    private function attachStagedDigitalFiles(Product $product, array $paths): void
    {
        foreach ($paths as $path) {
            if (! Storage::disk('local')->exists($path)) {
                continue;
            }

            $destination = 'product-digital-files/'.$product->id.'/'.Str::uuid().'-'.basename($path);
            Storage::disk('local')->move($path, $destination);

            ProductDigitalFile::create([
                'product_id' => $product->id,
                'name' => basename($path),
                'file_path' => $destination,
                'file_size' => Storage::disk('local')->size($destination),
            ]);
        }
    }
}
