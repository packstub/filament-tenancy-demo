<?php

namespace App\Filament\Central\Resources\Tenants;

use App\Filament\Central\Resources\Tenants\Pages\ManageTenants;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Packstub\Tenancy\Models\Tenant;
use Stancl\Tenancy\Events\TenantCreated;

/**
 * Operator view of the central `tenants` table. Read-mostly: tenants are
 * created through the onboarding wizard in the admin panel; here you can
 * watch provisioning, re-run it for a failed tenant, and delete a tenant
 * (which drops its database through stancl's TenantDeleted pipeline).
 */
class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->searchable()->badge()->color('gray'),
                TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    Tenant::STATUS_READY => 'success',
                    Tenant::STATUS_PROVISIONING => 'warning',
                    default => 'danger',
                }),
                TextColumn::make('domains.domain')->label('Domains')->listWithLineBreaks(),
                TextColumn::make('users_count')->counts('users')->label('Members'),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    Tenant::STATUS_READY => 'Ready',
                    Tenant::STATUS_PROVISIONING => 'Provisioning',
                    Tenant::STATUS_FAILED => 'Failed',
                ]),
            ])
            ->recordActions([
                Action::make('open')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Tenant $record) => 'http'.(request()->isSecure() ? 's' : '').'://'.$record->defaultDomainString().'/admin', shouldOpenInNewTab: true)
                    ->visible(fn (Tenant $record) => $record->status === Tenant::STATUS_READY),
                Action::make('retryProvisioning')
                    ->label('Retry provisioning')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Tenant $record) => $record->status === Tenant::STATUS_FAILED)
                    ->action(function (Tenant $record): void {
                        // Same thing `php artisan tenants:retry-provisioning {slug}` does.
                        $record->updateQuietly(['status' => Tenant::STATUS_PROVISIONING]);
                        event(new TenantCreated($record));

                        Notification::make()->title("Provisioning re-queued for {$record->name}")->success()->send();
                    }),
                DeleteAction::make()
                    ->modalDescription('Deletes the tenant, its domain rows, and drops its database.'),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->with('domains'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTenants::route('/'),
        ];
    }
}
