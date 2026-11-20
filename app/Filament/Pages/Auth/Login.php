<?php

namespace App\Filament\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportRedirects\Redirector;

class Login extends BaseAuth
{
    protected static string $layout = 'filament-panels::components.layout.base';

    protected static string $view = 'filament.pages.auth.login';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getUsernameFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }

    protected function getUsernameFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Nama Pengguna (Username)')
            ->placeholder('Masukkan username Anda')
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Kata Sandi')
            ->placeholder('Masukkan kata sandi Anda')
            ->password()
            ->revealable()
            ->autocomplete('current-password')
            ->required()
            ->extraInputAttributes(['tabindex' => 2]);
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label('Ingat Saya di Perangkat Ini');
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('Masuk ke Sistem')
            ->submit('authenticate');
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(10);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        // 1. Cek kecocokan data login
        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        // 2. Cegah login jika akun dinonaktifkan
        if (! $user->is_active) {
            Filament::auth()->logout();

            Notification::make()
                ->title('Akun Dinonaktifkan')
                ->body('Akun Anda dinonaktifkan. Silakan hubungi Administrator.')
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'data.username' => 'Akun Anda dinonaktifkan. Silakan hubungi Administrator.',
            ]);
        }

        // Reset rate limiter setelah login berhasil
        $this->clearRateLimiter();

        session()->regenerate();

        // 3. Jika yang login adalah petugas scanner ('register'), lempar ke /scan
        if ($user->role === 'register') {
            return new class implements LoginResponse
            {
                public function toResponse($request): RedirectResponse|Redirector
                {
                    return redirect()->intended(url('/scan'));
                }
            };
        }

        // 4. Jika Admin / Tenant, lanjutkan proses login normal ke Dashboard utama (/)
        return app(LoginResponse::class);
    }

    /**
     * Tampilkan notifikasi toast dan pesan validasi merah di bawah field username
     */
    protected function throwFailureValidationException(): never
    {
        Notification::make()
            ->title('Gagal Masuk')
            ->body('Username atau kata sandi yang Anda masukkan salah.')
            ->danger()
            ->send();

        throw ValidationException::withMessages([
            'data.username' => 'Username atau kata sandi yang Anda masukkan salah.',
        ]);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'username' => trim($data['username'] ?? ''),
            'password' => $data['password'] ?? '',
        ];
    }
}
