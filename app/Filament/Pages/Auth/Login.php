<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function fillDemoCredentials(string $role = 'super_admin'): void
    {
        $this->fillRoleCredentials($role);
    }

    public function loginAsSuperAdmin(): ?LoginResponse
    {
        return $this->loginAsRole('super_admin');
    }

    public function loginAsAdmin(): ?LoginResponse
    {
        return $this->loginAsRole('admin');
    }

    public function loginAsProduction(): ?LoginResponse
    {
        return $this->loginAsRole('production');
    }

    protected function loginAsRole(string $role): ?LoginResponse
    {
        $this->fillRoleCredentials($role);

        return $this->authenticate();
    }

    protected function fillRoleCredentials(string $role): void
    {
        $email = match ($role) {
            'admin' => 'reviewer@qaarisl.com',
            'production' => 'production@qaarisl.com',
            default => 'admin@qaarisl.com',
        };

        $this->form->fill([
            'email' => $email,
            'password' => 'password',
            'remember' => true,
        ]);
    }

    public function getHeading(): string | Htmlable | null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getHeading();
        }

        return 'Welcome back';
    }

    public function getSubheading(): string | Htmlable | null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getSubheading();
        }

        return 'Sign in to the Qaari SL admin panel';
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('Sign in')
            ->submit('authenticate');
    }
}
