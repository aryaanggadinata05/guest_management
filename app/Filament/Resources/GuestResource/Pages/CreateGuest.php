<?php

namespace App\Filament\Resources\GuestResource\Pages;

use App\Filament\Resources\GuestResource;
use App\Models\Guest;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateGuest extends CreateRecord
{
    protected static string $resource = GuestResource::class;

    protected function getRedirectUrl(): string
    {
        // Arahkan kembali ke halaman List (index) setelah berhasil Create
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        // Jika yang login adalah Tenant
        $tenantId = $user->tenant_id ?? $user->tenant?->id;

        if ($user->role === 'tenant' && $tenantId) {
            $data['tenant_id'] = $tenantId;
            $eventId = $data['event_id'];

            // 1. Ambil batas kuota tenant untuk event tersebut dari tabel pivot
            $eventTenant = \DB::table('event_tenant')
                ->where('event_id', $eventId)
                ->where('tenant_id', $tenantId)
                ->first();

            $quota = $eventTenant ? $eventTenant->quota : 0;

            // 2. Hitung jumlah tamu yang SUDAH ADA di database untuk tenant & event ini
            $currentGuestCount = Guest::where('event_id', $eventId)
                ->where('tenant_id', $tenantId)
                ->count();

            // 3. Jika jumlah saat ini sudah >= kuota, gagalkan proses simpan!
            if ($currentGuestCount >= $quota) {
                Notification::make()
                    ->danger()
                    ->title('Kuota Penuh!')
                    ->body("Gagal! Anda sudah mencapai batas maksimal kuota ({$quota} undangan) untuk acara ini.")
                    ->send();

                throw ValidationException::withMessages([
                    'event_id' => "Kuota undangan untuk event ini sudah habis ({$currentGuestCount} dari {$quota} kuota terpakai).",
                ]);
            }
        }

        return $data;
    }
}
