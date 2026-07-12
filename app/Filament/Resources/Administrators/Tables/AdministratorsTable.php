<?php

namespace App\Filament\Resources\Administrators\Tables;

use App\Enums\UserStatus;
use App\Enums\UserType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AdministratorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user_type')
                    ->badge(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('roles.name')
                    ->badge()
                    ->separator(',')
                    ->label('Roles'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('user_type')
                    ->options([
                        UserType::SuperAdmin->value => UserType::SuperAdmin->getLabel(),
                        UserType::Admin->value => UserType::Admin->getLabel(),
                        UserType::Staff->value => UserType::Staff->getLabel(),
                    ]),
                SelectFilter::make('status')
                    ->options(UserStatus::class),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn ($record) => $record->user_type !== UserType::SuperAdmin || auth()->id() === $record->id),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
