<?php

namespace Tests\Feature;

use App\Filament\Tenant\Pages\Members;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Packstub\Tenancy\Models\Tenant;
use Packstub\Tenancy\Testing\TenantTestHelpers;
use Tests\TenantTestCase;

/**
 * Roles are per tenant: the seeded viewer owns Acme but is only a member of
 * Globex. Members read projects; owners also create/edit/delete and manage
 * the workspace's members.
 */
class MembersAndRolesTest extends TenantTestCase
{
    use TenantTestHelpers;

    private User $viewer;

    private Tenant $acme;

    private Tenant $globex;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->viewer = User::query()->where('email', DatabaseSeeder::VIEWER_EMAIL)->firstOrFail();
        $this->acme = Tenant::query()->where('slug', 'acme')->firstOrFail();
        $this->globex = Tenant::query()->where('slug', 'globex')->firstOrFail();
    }

    public function test_viewer_has_a_different_role_in_each_tenant(): void
    {
        $this->assertTrue($this->viewer->isOwnerOf($this->acme));
        $this->assertFalse($this->viewer->isOwnerOf($this->globex));
        $this->assertSame('member', $this->viewer->roleIn($this->globex));
    }

    public function test_member_can_view_projects_but_not_write_them(): void
    {
        $this->actingAsTenant($this->globex, $this->viewer);
        Filament::setTenant($this->globex, isQuiet: true);

        $project = Project::first();

        $this->assertTrue($this->viewer->can('viewAny', Project::class));
        $this->assertTrue($this->viewer->can('view', $project));
        $this->assertFalse($this->viewer->can('create', Project::class));
        $this->assertFalse($this->viewer->can('update', $project));
        $this->assertFalse($this->viewer->can('delete', $project));

        $this->leaveTenant();
    }

    public function test_owner_can_write_projects(): void
    {
        $this->actingAsTenant($this->acme, $this->viewer);
        Filament::setTenant($this->acme, isQuiet: true);

        $this->assertTrue($this->viewer->can('create', Project::class));
        $this->assertTrue($this->viewer->can('update', Project::first()));

        $this->leaveTenant();
    }

    public function test_projects_page_hides_write_actions_from_a_member(): void
    {
        $central = config('packstub-tenancy.central_domain');

        $this->actingAs($this->viewer)
            ->get("http://globex.{$central}/admin/projects")
            ->assertOk()
            ->assertSee('Welcome to Globex Corp.')
            ->assertDontSee('New project');

        $this->actingAs($this->viewer)
            ->get("http://acme.{$central}/admin/projects")
            ->assertOk()
            ->assertSee('New project');
    }

    public function test_members_page_lists_members_and_only_owners_can_invite(): void
    {
        $central = config('packstub-tenancy.central_domain');

        $this->actingAs($this->viewer)
            ->get("http://globex.{$central}/admin/members")
            ->assertOk()
            ->assertSee(DatabaseSeeder::DEMO_EMAIL)
            ->assertSee(DatabaseSeeder::VIEWER_EMAIL)
            ->assertDontSee('Invite member');

        $this->actingAs($this->viewer)
            ->get("http://acme.{$central}/admin/members")
            ->assertOk()
            ->assertSee('Invite member');
    }

    public function test_owner_can_invite_a_new_email_and_an_existing_user(): void
    {
        $this->actingAsTenant($this->acme, $this->viewer);
        Filament::setTenant($this->acme, isQuiet: true);

        Livewire::test(Members::class)
            ->callAction('invite', ['email' => 'new@example.com', 'role' => 'member'])
            ->assertHasNoActionErrors()
            ->assertNotified();

        $invited = User::query()->where('email', 'new@example.com')->firstOrFail();
        $this->assertSame('member', $invited->roleIn($this->acme));
        $this->assertNull($invited->roleIn($this->globex));

        // An existing central account is attached, not duplicated.
        $existing = User::factory()->create(['email' => 'existing@example.com']);

        Livewire::test(Members::class)
            ->callAction('invite', ['email' => 'existing@example.com', 'role' => Tenant::ROLE_OWNER])
            ->assertHasNoActionErrors();

        $this->assertSame(1, User::query()->where('email', 'existing@example.com')->count());
        $this->assertTrue($existing->fresh()->isOwnerOf($this->acme));

        $this->leaveTenant();
    }

    public function test_owner_can_change_role_and_remove_a_member(): void
    {
        $this->actingAsTenant($this->acme, $this->viewer);
        Filament::setTenant($this->acme, isQuiet: true);

        $owner = User::query()->where('email', DatabaseSeeder::DEMO_EMAIL)->firstOrFail();

        Livewire::test(Members::class)
            ->callTableAction('changeRole', $owner, ['role' => 'member'])
            ->assertHasNoTableActionErrors();

        $this->assertSame('member', $owner->fresh()->roleIn($this->acme));

        Livewire::test(Members::class)
            ->callTableAction('remove', $owner)
            ->assertHasNoTableActionErrors();

        $this->assertNull($owner->fresh()->roleIn($this->acme));
        $this->assertTrue($owner->fresh()->isOwnerOf($this->globex)); // untouched

        $this->leaveTenant();
    }
}
