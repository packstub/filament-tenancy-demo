<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Packstub\Tenancy\Models\Tenant;
use Tests\TenantTestCase;

/**
 * The operator panel lives on the central domain only and never touches a
 * tenant database.
 */
class CentralPanelTest extends TenantTestCase
{
    public function test_operator_panel_lists_every_tenant_on_the_central_domain(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::query()->where('email', DatabaseSeeder::DEMO_EMAIL)->firstOrFail();
        $central = config('packstub-tenancy.central_domain');

        $this->actingAs($owner)
            ->get("http://{$central}/central/tenants")
            ->assertOk()
            ->assertSee('Acme Inc.')
            ->assertSee('Globex Corp.')
            ->assertSee('acme.'.$central);

        $this->assertFalse(tenancy()->initialized);
    }

    public function test_operator_panel_is_not_served_on_tenant_hosts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::query()->where('email', DatabaseSeeder::DEMO_EMAIL)->firstOrFail();
        $tenant = Tenant::query()->where('slug', 'acme')->firstOrFail();
        $central = config('packstub-tenancy.central_domain');

        $this->actingAs($owner)
            ->get("http://{$tenant->slug}.{$central}/central/tenants")
            ->assertNotFound();
    }
}
