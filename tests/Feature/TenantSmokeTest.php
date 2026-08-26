<?php

use App\Models\Project;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Packstub\Tenancy\Models\Tenant;
use Packstub\Tenancy\Testing\TenantTestHelpers;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

/**
 * End-to-end smoke test — the same one CI runs against a fresh install of the
 * plugin from the Packstub registry: seed → two READY tenants with their own
 * databases → the owner can open each tenant panel on its subdomain and the
 * Projects table shows only that tenant's rows.
 */
uses(TenantTestHelpers::class);

it('provisions two ready tenants with isolated databases from the seeder', function () {
    seed(DatabaseSeeder::class);

    $owner = User::query()->where('email', DatabaseSeeder::DEMO_EMAIL)->firstOrFail();
    $tenants = Tenant::query()->orderBy('slug')->get();

    expect($tenants->pluck('slug')->all())->toBe(['acme', 'globex'])
        ->and($tenants->pluck('status')->all())->toBe(['ready', 'ready'])
        ->and($owner->getTenants(filament()->getDefaultPanel()))->toHaveCount(2);

    foreach ($tenants as $tenant) {
        tenancy()->initialize($tenant);

        // TenantSeeder ran inside THIS tenant's database only.
        expect(Project::count())->toBe(1)
            ->and(Project::first()->name)->toBe("Welcome to {$tenant->name}");

        tenancy()->end();
    }
});

it('lets the owner open each tenant panel on its subdomain', function () {
    seed(DatabaseSeeder::class);

    $owner = User::query()->where('email', DatabaseSeeder::DEMO_EMAIL)->firstOrFail();
    $central = config('packstub-tenancy.central_domain');

    foreach (Tenant::all() as $tenant) {
        actingAs($owner)
            ->get("http://{$tenant->slug}.{$central}/admin/projects")
            ->assertOk()
            ->assertSee($tenant->name)
            ->assertSee("Welcome to {$tenant->name}");
    }
});

it('starts a new tenant with its own empty projects table', function () {
    [$tenant, $user] = $this->createTenantWithUser(['name' => 'Initech', 'slug' => 'initech']);

    $this->actingAsTenant($tenant, $user);

    expect(Project::count())->toBe(1); // just the TenantSeeder welcome row
    Project::create(['name' => 'TPS reports']);
    expect(Project::count())->toBe(2);

    $this->leaveTenant();
});
