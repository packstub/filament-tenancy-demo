<?php

namespace App\Filament\Central\Resources\Users;

use App\Filament\Central\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Central `users` table — one identity across every tenant (see the plugin's
 * "Isolation model": users, sessions and auth are never per-tenant).
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('tenants.name')->label('Tenants')->badge(),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
