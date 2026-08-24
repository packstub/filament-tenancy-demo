<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

/**
 * Tenant-safe seeder: runs INSIDE each new tenant's database during
 * provisioning (CreateDatabase → MigrateDatabase → SeedDatabase → MarkTenantReady).
 * Wired via `'seeder' => TenantSeeder::class` in config/packstub-tenancy.php.
 *
 * Only touch tenant tables here — never users, tenants, or anything central.
 */
class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $name = tenant()?->name ?? 'this workspace';

        Project::create([
            'name' => "Welcome to {$name}",
            'status' => 'active',
            'description' => 'This project was created by TenantSeeder inside this tenant\'s own database. Every tenant gets its own copy.',
            'due_on' => now()->addWeek(),
        ]);
    }
}
