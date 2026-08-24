<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Packstub\Tenancy\Services\TenantOnboarder;

/**
 * Demo data: one user who owns two tenants (acme + globex), each provisioned
 * with its own database. Log in with demo@example.com / password.
 *
 * Provisioning normally runs on the queue; the seeder forces the sync driver so
 * `php artisan migrate:fresh --seed` finishes with both tenants READY.
 */
class DatabaseSeeder extends Seeder
{
    public const string DEMO_EMAIL = 'demo@example.com';

    public function run(): void
    {
        config(['queue.default' => 'sync']);

        $owner = User::query()->firstOrCreate(
            ['email' => self::DEMO_EMAIL],
            ['name' => 'Demo User', 'password' => 'password'],
        );

        $onboarder = app(TenantOnboarder::class);

        foreach (['Acme Inc.' => 'acme', 'Globex Corp.' => 'globex'] as $name => $slug) {
            // Exactly what the onboarding wizard does: tenant row + domain row +
            // owner pivot in one central transaction, then the pipeline.
            $onboarder->create(name: $name, slug: $slug, owner: $owner);
        }
    }
}
