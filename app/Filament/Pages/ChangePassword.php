<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ChangePassword extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Ubah Kata Sandi';

    protected static ?string $slug = 'ubah-password';

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.change-password';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Keamanan Akun')
                    ->description('Perbarui kata sandi akun Anda untuk menjaga keamanan akses ke dalam sistem.')
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Kata Sandi Saat Ini')
                            ->password()
                            ->revealable()
                            ->required()
                            ->autocomplete('current-password'),

                        TextInput::make('new_password')
                            ->label('Kata Sandi Baru')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(4)
                            ->different('current_password')
                            ->validationMessages([
                                'different' => 'Kata sandi baru tidak boleh sama dengan kata sandi saat ini.',
                                'min' => 'Kata sandi baru minimal 4 karakter.',
                            ])
                            ->autocomplete('new-password'),

                        TextInput::make('new_password_confirmation')
                            ->label('Ulangi Kata Sandi Baru')
                            ->password()
                            ->revealable()
                            ->required()
                            ->same('new_password')
                            ->validationMessages([
                                'same' => 'Konfirmasi kata sandi baru tidak cocok.',
                            ])
                            ->autocomplete('new-password'),
                    ])
                    ->columns(1)
                    ->maxWidth('xl'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        /** @var User $user */
        $user = auth()->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            Notification::make()
                ->title('Gagal Mengubah Kata Sandi')
                ->body('Kata sandi saat ini yang Anda masukkan salah.')
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'data.current_password' => 'Kata sandi saat ini tidak cocok.',
            ]);
        }

        $user->update([
            'password' => Hash::make($data['new_password']),
        ]);

        $this->form->fill();

        Notification::make()
            ->title('Kata Sandi Berhasil Diperbarui')
            ->body('Kata sandi akun Anda telah berhasil diubah.')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Kata Sandi')
                ->submit('save')
                ->color('primary'),
        ];
    }
}
