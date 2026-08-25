<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;
use Tests\TenantTestCase;

class LoginPrefillTest extends TenantTestCase
{
    public function test_login_form_is_prefilled_when_enabled(): void
    {
        config(['demo.login_prefill' => true]);

        Livewire::test(Login::class)
            ->assertSchemaStateSet([
                'email' => DatabaseSeeder::DEMO_EMAIL,
                'password' => DatabaseSeeder::DEMO_PASSWORD,
            ]);
    }

    public function test_login_form_is_empty_when_disabled(): void
    {
        config(['demo.login_prefill' => false]);

        Livewire::test(Login::class)
            ->assertSchemaStateSet(['email' => null])
            ->assertDontSee('Sign in as viewer');
    }

    public function test_sign_in_as_viewer_shortcut_authenticates_the_viewer(): void
    {
        config(['demo.login_prefill' => true]);

        $this->seed(DatabaseSeeder::class);

        Livewire::test(Login::class)
            ->assertSee('Sign in as viewer')
            ->call('signInAsViewer')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertAuthenticatedAs(User::query()->where('email', DatabaseSeeder::VIEWER_EMAIL)->firstOrFail());
    }
}
