<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A single FileUpload field stages its upload to a "tmp-*" public directory
 * (no real model id exists yet at form-fill time); this moves the staged
 * file into its owning record's singleFile media collection once the
 * record exists, replacing any previous image automatically, and removes
 * the temporary copy either way. Mirrors HandlesProductMedia's staging
 * convention for the single-image case.
 */
trait HandlesStagedSingleImage
{
    private function attachStagedImage(Model $record, ?string $path, string $collection): void
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return;
        }

        $record->addMediaFromDisk($path, 'public')->toMediaCollection($collection);
        Storage::disk('public')->delete($path);
    }
}
