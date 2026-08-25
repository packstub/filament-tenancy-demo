<?php

namespace App\Filament\Pages\Auth;

use Database\Seeders\DatabaseSeeder;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;

/**
 * Demo convenience: the login form comes pre-filled with the seeded owner's
 * credentials, so a first visit is one click, and a second button signs in as
 * the viewer account (owner of Acme, read-only member of Globex). Both are
 * controlled by DEMO_LOGIN_PREFILL (see .env.example) — leave it off anywhere
 * real users log in.
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

    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
            Action::make('signInAsViewer')
                ->label('Sign in as viewer')
                ->color('gray')
                ->visible(fn () => (bool) config('demo.login_prefill'))
                ->action(fn () => $this->signInAsViewer()),
        ];
    }

    public function signInAsViewer(): ?LoginResponse
    {
        $this->form->fill([
            'email' => DatabaseSeeder::VIEWER_EMAIL,
            'password' => DatabaseSeeder::DEMO_PASSWORD,
            'remember' => true,
        ]);

        return $this->authenticate();
    }
}
