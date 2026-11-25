<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        // Arahkan kembali ke halaman List (index) setelah berhasil Create
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->hidden(fn (): bool => $this->record->id === auth()->id()),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->role !== 'register') {
            $this->record->assignedEvents()->detach();
        }
    }
}
