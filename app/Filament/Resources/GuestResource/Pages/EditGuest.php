<?php

namespace App\Filament\Resources\GuestResource\Pages;

use App\Filament\Resources\GuestResource;
use App\Models\Guest;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditGuest extends EditRecord
{
    protected static string $resource = GuestResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id ?? $user->tenant?->id;

        if ($user->role === 'tenant' && $tenantId) {
            $eventId = $data['event_id'];

            // Jika tenant memindahkan tamu ke event lain
            if ($this->record->event_id != $eventId) {
                $eventTenant = \DB::table('event_tenant')
                    ->where('event_id', $eventId)
                    ->where('tenant_id', $tenantId)
                    ->first();

                $quota = $eventTenant ? $eventTenant->quota : 0;

                $currentGuestCount = Guest::where('event_id', $eventId)
                    ->where('tenant_id', $tenantId)
                    ->count();

                if ($currentGuestCount >= $quota) {
                    Notification::make()
                        ->danger()
                        ->title('Kuota Acara Penuh!')
                        ->body("Tidak dapat memindahkan tamu. Kuota untuk acara tujuan sudah penuh ({$quota} undangan).")
                        ->send();

                    throw ValidationException::withMessages([
                        'event_id' => 'Kuota untuk acara tujuan sudah penuh.',
                    ]);
                }
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        // Arahkan kembali ke halaman List (index) setelah berhasil Create
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
