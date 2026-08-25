<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\CentralPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    CentralPanelProvider::class,
];
