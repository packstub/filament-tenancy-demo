<?php

namespace App\Filament\Central\Resources\Tenants\Pages;

use App\Filament\Central\Resources\Tenants\TenantResource;
use Filament\Resources\Pages\ManageRecords;

class ManageTenants extends ManageRecords
{
    protected static string $resource = TenantResource::class;
}
