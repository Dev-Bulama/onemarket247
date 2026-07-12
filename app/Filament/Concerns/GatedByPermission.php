<?php

namespace App\Filament\Concerns;

/**
 * Gates an entire Filament resource behind a single Spatie permission,
 * for reference-data resources where ownership doesn't apply (Country,
 * Language, Currency, Setting, ...) and a dedicated Policy class would be
 * pure boilerplate. Resources with real ownership semantics (Vendor,
 * Store, ...) use a Policy instead — see docs/CODING_STANDARDS.md.
 */
trait GatedByPermission
{
    protected static function permission(): string
    {
        return static::$managePermission;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(static::permission()) ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return static::canViewAny();
    }

    public static function canDeleteAny(): bool
    {
        return static::canViewAny();
    }
}
