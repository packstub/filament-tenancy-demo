<?php

namespace App\Filament\Central\Resources\Users\Pages;

use App\Filament\Central\Resources\Users\UserResource;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;
}
