<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A plain Eloquent model — no tenant_id, no global scope, no BelongsToTenant.
 * In tenant context the default connection already IS the tenant's database.
 */
#[Fillable(['name', 'status', 'description', 'due_on'])]
class Project extends Model
{
    protected function casts(): array
    {
        return [
            'due_on' => 'date',
        ];
    }
}
