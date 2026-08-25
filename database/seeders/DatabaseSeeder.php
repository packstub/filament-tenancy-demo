<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Packstub\Tenancy\Models\Tenant;
use Packstub\Tenancy\Services\TenantOnboarder;

/**
 * Demo data: two tenants (acme + globex), each provisioned with its own
 * database, and two central users:
 *
 * - demo@example.com   owns both tenants
 * - viewer@example.com owns acme but is only a MEMBER of globex — open Globex
 *   as this user and Projects becomes read-only (see ProjectPolicy)
 *
 * Both log in with the password packstub-tenancy-demo.
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

    public const string VIEWER_EMAIL = 'viewer@example.com';

    /** @var array<string, string> tenant slug => role for the viewer account */
    public const array VIEWER_ROLES = ['acme' => Tenant::ROLE_OWNER, 'globex' => 'member'];

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

        // The second account is attached straight to the pivot — exactly what
        // the Members page's "Invite" action does. Roles are per tenant.
        $viewer = User::query()->updateOrCreate(
            ['email' => self::VIEWER_EMAIL],
            ['name' => 'Demo Viewer', 'password' => self::DEMO_PASSWORD],
        );

        foreach (self::VIEWER_ROLES as $slug => $role) {
            Tenant::query()->where('slug', $slug)->first()
                ?->users()->syncWithoutDetaching([$viewer->getKey() => ['role' => $role]]);
        }
    }
}
