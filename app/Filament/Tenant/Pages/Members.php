<?php

namespace App\Filament\Tenant\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Packstub\Tenancy\Models\Tenant;

/**
 * Who belongs to the current workspace, straight from the central
 * tenant_user pivot. Every member can look; only owners can invite, change a
 * role or remove someone.
 *
 * "Invite" is deliberately simple for a demo: an existing central account is
 * attached to the tenant, an unknown email gets a fresh account with a
 * temporary password that is shown once to the inviter. A real app would send
 * an invitation email instead — the pivot write is the only part that matters
 * for tenancy.
 */
class Members extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.tenant.pages.members';

    public const array ROLES = [Tenant::ROLE_OWNER => 'Owner', 'member' => 'Member'];

    public function table(Table $table): Table
    {
        return $table
            ->relationship(fn () => $this->tenant()->users())
            ->inverseRelationship('tenants')
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('pivot.role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => self::ROLES[$state] ?? $state)
                    ->color(fn (string $state) => $state === Tenant::ROLE_OWNER ? 'primary' : 'gray'),
                TextColumn::make('pivot.created_at')->label('Joined')->since(),
            ])
            ->recordActions([
                Action::make('changeRole')
                    ->label('Change role')
                    ->icon(Heroicon::OutlinedArrowsRightLeft)
                    ->visible(fn (User $record) => $this->canManage() && ! $record->is(auth()->user()))
                    ->fillForm(fn (User $record) => ['role' => $record->pivot->role])
                    ->schema([
                        Select::make('role')->options(self::ROLES)->required(),
                    ])
                    ->action(function (User $record, array $data): void {
                        $this->tenant()->users()->updateExistingPivot($record->getKey(), ['role' => $data['role']]);

                        Notification::make()->title("{$record->name} is now a ".self::ROLES[$data['role']])->success()->send();
                    }),
                Action::make('remove')
                    ->icon(Heroicon::OutlinedUserMinus)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => $this->canManage() && ! $record->is(auth()->user()))
                    ->action(function (User $record): void {
                        $this->tenant()->users()->detach($record->getKey());

                        Notification::make()->title("{$record->name} removed from {$this->tenant()->name}")->success()->send();
                    }),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('invite')
                ->label('Invite member')
                ->icon(Heroicon::OutlinedUserPlus)
                ->visible(fn () => $this->canManage())
                ->schema([
                    TextInput::make('email')->email()->required()->maxLength(255),
                    Select::make('role')->options(self::ROLES)->default('member')->required(),
                ])
                ->action(function (array $data): void {
                    $tenant = $this->tenant();
                    $email = Str::lower($data['email']);

                    if ($tenant->users()->where('email', $email)->exists()) {
                        Notification::make()->title("{$email} is already a member")->warning()->send();

                        return;
                    }

                    $user = User::query()->where('email', $email)->first();
                    $temporaryPassword = null;

                    if (! $user) {
                        $temporaryPassword = Str::password(12);
                        $user = User::query()->create([
                            'name' => Str::headline(Str::before($email, '@')),
                            'email' => $email,
                            'password' => $temporaryPassword,
                        ]);
                    }

                    $tenant->users()->attach($user->getKey(), ['role' => $data['role']]);

                    Notification::make()
                        ->title("{$user->name} added to {$tenant->name} as ".self::ROLES[$data['role']])
                        ->body($temporaryPassword
                            ? "New account — temporary password: {$temporaryPassword} (demo only; a real app would email an invitation)."
                            : 'They can switch to this workspace from the tenant menu right away.')
                        ->success()
                        ->persistent()
                        ->send();
                }),
        ];
    }

    private function tenant(): Tenant
    {
        return Filament::getTenant();
    }

    private function canManage(): bool
    {
        return auth()->user()->isOwnerOf($this->tenant());
    }
}
