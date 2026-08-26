<?php

use App\Filament\Tenant\Pages\Members;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Packstub\Tenancy\Models\Tenant;
use Packstub\Tenancy\Testing\TenantTestHelpers;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;
use function Pest\Livewire\livewire;

/**
 * Roles are per tenant: the seeded viewer owns Acme but is only a member of
 * Globex. Members read projects; owners also create/edit/delete and manage
 * the workspace's members.
 */
uses(TenantTestHelpers::class);

beforeEach(function () {
    seed(DatabaseSeeder::class);

    $this->viewer = User::query()->where('email', DatabaseSeeder::VIEWER_EMAIL)->firstOrFail();
    $this->acme = Tenant::query()->where('slug', 'acme')->firstOrFail();
    $this->globex = Tenant::query()->where('slug', 'globex')->firstOrFail();
    $this->central = config('packstub-tenancy.central_domain');
});

it('gives the viewer a different role in each tenant', function () {
    expect($this->viewer->isOwnerOf($this->acme))->toBeTrue()
        ->and($this->viewer->isOwnerOf($this->globex))->toBeFalse()
        ->and($this->viewer->roleIn($this->globex))->toBe('member');
});

it('lets a member view projects but not write them', function () {
    $this->actingAsTenant($this->globex, $this->viewer);
    Filament::setTenant($this->globex, isQuiet: true);

    $project = Project::first();

    expect($this->viewer->can('viewAny', Project::class))->toBeTrue()
        ->and($this->viewer->can('view', $project))->toBeTrue()
        ->and($this->viewer->can('create', Project::class))->toBeFalse()
        ->and($this->viewer->can('update', $project))->toBeFalse()
        ->and($this->viewer->can('delete', $project))->toBeFalse();

    $this->leaveTenant();
});

it('lets an owner write projects', function () {
    $this->actingAsTenant($this->acme, $this->viewer);
    Filament::setTenant($this->acme, isQuiet: true);

    expect($this->viewer->can('create', Project::class))->toBeTrue()
        ->and($this->viewer->can('update', Project::first()))->toBeTrue();

    $this->leaveTenant();
});

it('hides write actions on the projects page from a member', function () {
    actingAs($this->viewer)
        ->get("http://globex.{$this->central}/admin/projects")
        ->assertOk()
        ->assertSee('Welcome to Globex Corp.')
        ->assertDontSee('New project');

    actingAs($this->viewer)
        ->get("http://acme.{$this->central}/admin/projects")
        ->assertOk()
        ->assertSee('New project');
});

it('lists members and only lets owners invite', function () {
    actingAs($this->viewer)
        ->get("http://globex.{$this->central}/admin/members")
        ->assertOk()
        ->assertSee(DatabaseSeeder::DEMO_EMAIL)
        ->assertSee(DatabaseSeeder::VIEWER_EMAIL)
        ->assertDontSee('Invite member');

    actingAs($this->viewer)
        ->get("http://acme.{$this->central}/admin/members")
        ->assertOk()
        ->assertSee('Invite member');
});

it('lets an owner invite a new email and an existing user', function () {
    $this->actingAsTenant($this->acme, $this->viewer);
    Filament::setTenant($this->acme, isQuiet: true);

    livewire(Members::class)
        ->callAction('invite', ['email' => 'new@example.com', 'role' => 'member'])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $invited = User::query()->where('email', 'new@example.com')->firstOrFail();
    expect($invited->roleIn($this->acme))->toBe('member')
        ->and($invited->roleIn($this->globex))->toBeNull();

    // An existing central account is attached, not duplicated.
    $existing = User::factory()->create(['email' => 'existing@example.com']);

    livewire(Members::class)
        ->callAction('invite', ['email' => 'existing@example.com', 'role' => Tenant::ROLE_OWNER])
        ->assertHasNoActionErrors();

    expect(User::query()->where('email', 'existing@example.com')->count())->toBe(1)
        ->and($existing->fresh()->isOwnerOf($this->acme))->toBeTrue();

    $this->leaveTenant();
});

it('lets an owner change a role and remove a member', function () {
    $this->actingAsTenant($this->acme, $this->viewer);
    Filament::setTenant($this->acme, isQuiet: true);

    $owner = User::query()->where('email', DatabaseSeeder::DEMO_EMAIL)->firstOrFail();

    livewire(Members::class)
        ->callTableAction('changeRole', $owner, ['role' => 'member'])
        ->assertHasNoTableActionErrors();

    expect($owner->fresh()->roleIn($this->acme))->toBe('member');

    livewire(Members::class)
        ->callTableAction('remove', $owner)
        ->assertHasNoTableActionErrors();

    expect($owner->fresh()->roleIn($this->acme))->toBeNull()
        ->and($owner->fresh()->isOwnerOf($this->globex))->toBeTrue(); // untouched

    $this->leaveTenant();
});
