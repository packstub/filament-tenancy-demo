<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;
use Tests\TestCase;

class LoginPrefillTest extends TestCase
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

        Livewire::test(Login::class)->assertSchemaStateSet(['email' => null]);
    }
}
