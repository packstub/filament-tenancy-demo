<?php

namespace App\Filament\Central\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Packstub\Tenancy\Models\Tenant;

class TenantStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $byStatus = Tenant::query()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return [
            Stat::make('Tenants', (string) $byStatus->sum())
                ->description('each on its own database'),
            Stat::make('Ready', (string) ($byStatus[Tenant::STATUS_READY] ?? 0))
                ->color('success'),
            Stat::make('Provisioning / failed', ($byStatus[Tenant::STATUS_PROVISIONING] ?? 0).' / '.($byStatus[Tenant::STATUS_FAILED] ?? 0))
                ->color(($byStatus[Tenant::STATUS_FAILED] ?? 0) > 0 ? 'danger' : 'gray'),
            Stat::make('Users', (string) User::query()->count())
                ->description('central users table'),
        ];
    }
}
