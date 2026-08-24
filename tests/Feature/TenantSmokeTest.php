<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Packstub\Tenancy\Models\Tenant;
use Packstub\Tenancy\Testing\TenantTestHelpers;
use Tests\TenantTestCase;

/**
 * End-to-end smoke test — the same one CI runs against a fresh install of the
 * plugin from the Packstub registry: seed → two READY tenants with their own
 * databases → the owner can open each tenant panel on its subdomain and the
 * Projects table shows only that tenant's rows.
 */
class TenantSmokeTest extends TenantTestCase
{
    use TenantTestHelpers;

    public function test_seeder_provisions_two_ready_tenants_with_isolated_databases(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::query()->where('email', DatabaseSeeder::DEMO_EMAIL)->firstOrFail();
        $tenants = Tenant::query()->orderBy('slug')->get();

        $this->assertSame(['acme', 'globex'], $tenants->pluck('slug')->all());
        $this->assertSame(['ready', 'ready'], $tenants->pluck('status')->all());
        $this->assertCount(2, $owner->getTenants(filament()->getDefaultPanel()));

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            // TenantSeeder ran inside THIS tenant's database only.
            $this->assertSame(1, Project::count());
            $this->assertSame("Welcome to {$tenant->name}", Project::first()->name);

            tenancy()->end();
        }
    }

    public function test_owner_can_open_each_tenant_panel_on_its_subdomain(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::query()->where('email', DatabaseSeeder::DEMO_EMAIL)->firstOrFail();
        $central = config('packstub-tenancy.central_domain');

        foreach (Tenant::all() as $tenant) {
            $this->actingAs($owner)
                ->get("http://{$tenant->slug}.{$central}/admin/projects")
                ->assertOk()
                ->assertSee($tenant->name)
                ->assertSee("Welcome to {$tenant->name}");
        }
    }

    public function test_a_new_tenant_starts_with_its_own_empty_projects_table(): void
    {
        [$tenant, $user] = $this->createTenantWithUser(['name' => 'Initech', 'slug' => 'initech']);

        $this->actingAsTenant($tenant, $user);

        $this->assertSame(1, Project::count()); // just the TenantSeeder welcome row
        Project::create(['name' => 'TPS reports']);
        $this->assertSame(2, Project::count());

        $this->leaveTenant();
    }
}
