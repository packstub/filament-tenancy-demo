<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Packstub\Tenancy\Models\Tenant;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

/**
 * The operator panel lives on the central domain only and never touches a
 * tenant database.
 */
beforeEach(function () {
    seed(DatabaseSeeder::class);

    $this->owner = User::query()->where('email', DatabaseSeeder::DEMO_EMAIL)->firstOrFail();
    $this->central = config('packstub-tenancy.central_domain');
});

it('lists every tenant on the central domain', function () {
    actingAs($this->owner)
        ->get("http://{$this->central}/central/tenants")
        ->assertOk()
        ->assertSee('Acme Inc.')
        ->assertSee('Globex Corp.')
        ->assertSee('acme.'.$this->central);

    expect(tenancy()->initialized)->toBeFalse();
});

it('is not served on tenant hosts', function () {
    $tenant = Tenant::query()->where('slug', 'acme')->firstOrFail();

    actingAs($this->owner)
        ->get("http://{$tenant->slug}.{$this->central}/central/tenants")
        ->assertNotFound();
});
