<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Packstub\Tenancy\Models\Tenant;
use Packstub\Tenancy\Services\TenantOnboarder;

/**
 * Demo data: one user who owns two tenants (acme + globex), each provisioned
 * with its own database. Log in with demo@example.com / packstub-tenancy-demo.
 *
 * Idempotent: safe to run on every deploy (`php artisan db:seed --force`) —
 * existing users/tenants are left alone. Provisioning normally runs on the
 * queue; the seeder forces the sync driver so it returns with both tenants
 * READY, whatever QUEUE_CONNECTION is.
 */
class DatabaseSeeder extends Seeder
{
    public const string DEMO_EMAIL = 'demo@example.com';

    public const string DEMO_PASSWORD = 'packstub-tenancy-demo';

    public function run(): void
    {
        config(['queue.default' => 'sync']);

        // updateOrCreate so a changed demo password reaches an existing deployment.
        $owner = User::query()->updateOrCreate(
            ['email' => self::DEMO_EMAIL],
            ['name' => 'Demo User', 'password' => self::DEMO_PASSWORD],
        );

        $onboarder = app(TenantOnboarder::class);

        foreach (['Acme Inc.' => 'acme', 'Globex Corp.' => 'globex'] as $name => $slug) {
            if (Tenant::query()->where('slug', $slug)->exists()) {
                continue;
            }

            // Exactly what the onboarding wizard does: tenant row + domain row +
            // owner pivot in one central transaction, then the pipeline.
            $onboarder->create(name: $name, slug: $slug, owner: $owner);
        }
    }
}
