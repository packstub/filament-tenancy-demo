<?php

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\seed;
use function Pest\Livewire\livewire;

it('prefills the login form when enabled', function () {
    config(['demo.login_prefill' => true]);

    livewire(Login::class)
        ->assertSchemaStateSet([
            'email' => DatabaseSeeder::DEMO_EMAIL,
            'password' => DatabaseSeeder::DEMO_PASSWORD,
        ]);
});

it('leaves the login form empty when disabled', function () {
    config(['demo.login_prefill' => false]);

    livewire(Login::class)
        ->assertSchemaStateSet(['email' => null])
        ->assertDontSee('Sign in as viewer');
});

it('authenticates the viewer through the sign-in-as-viewer shortcut', function () {
    config(['demo.login_prefill' => true]);

    seed(DatabaseSeeder::class);

    livewire(Login::class)
        ->assertSee('Sign in as viewer')
        ->call('signInAsViewer')
        ->assertHasNoErrors()
        ->assertRedirect();

    assertAuthenticatedAs(User::query()->where('email', DatabaseSeeder::VIEWER_EMAIL)->firstOrFail());
});
