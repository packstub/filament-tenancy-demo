<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Packstub\Tenancy\Concerns\HasPackstubTenants;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Users live in the CENTRAL database — one login, every workspace.
 *
 * - CentralConnection pins this model to the central connection even while a
 *   request is served from a tenant subdomain.
 * - HasPackstubTenants implements Filament's HasTenants + HasDefaultTenant
 *   (canAccessTenant / getTenants / getDefaultTenant) via the tenant_user pivot.
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasDefaultTenant, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use CentralConnection, HasFactory, HasPackstubTenants, Notifiable;

    /**
     * Outside APP_ENV=local Filament requires an explicit answer here.
     * Any registered user may open the panel; which TENANTS they see is
     * decided per tenant by HasPackstubTenants::canAccessTenant().
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
