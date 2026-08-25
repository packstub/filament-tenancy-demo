<?php

namespace App\Filament\Pages\Auth;

use Database\Seeders\DatabaseSeeder;
use Filament\Auth\Pages\Login as BaseLogin;

/**
 * Demo convenience: the login form comes pre-filled with the seeded owner's
 * credentials, so a first visit is one click. Controlled by DEMO_LOGIN_PREFILL
 * (see .env.example) — leave it off anywhere real users log in.
 */
class Login extends BaseLogin
{
    public function mount(): void
    {
        parent::mount();

        if (config('demo.login_prefill')) {
            $this->form->fill([
                'email' => DatabaseSeeder::DEMO_EMAIL,
                'password' => DatabaseSeeder::DEMO_PASSWORD,
                'remember' => true,
            ]);
        }
    }
}
