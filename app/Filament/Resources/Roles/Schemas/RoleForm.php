<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->where('guard_name', 'admin')),
                Hidden::make('guard_name')
                    ->default('admin'),
                CheckboxList::make('permissions')
                    ->relationship('permissions', 'name', fn ($query) => $query->where('guard_name', 'admin'))
                    ->searchable()
                    ->bulkToggleable()
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}
