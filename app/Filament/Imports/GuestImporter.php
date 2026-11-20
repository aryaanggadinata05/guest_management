<?php

namespace App\Filament\Imports;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Tenant;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GuestImporter extends Importer
{
    protected static ?string $model = Guest::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->label('Nama Tamu'),
            ImportColumn::make('phone')
                ->rules(['max:255'])
                ->label('Nomor HP'),
            ImportColumn::make('gender')
                ->rules(['max:255'])
                ->label('Jenis Kelamin (L/P)'),
            ImportColumn::make('job_type')
                ->rules(['max:255'])
                ->label('Pekerjaan'),

            // Mencegah Filament mengisi NULL jika di Excel kosong
            ImportColumn::make('adult_pax')
                ->numeric()
                ->rules(['nullable', 'integer'])
                ->label('Dewasa (Pax)')
                ->fillRecordUsing(function (Guest $record, $state, array $options): void {
                    $record->adult_pax = (isset($state) && $state !== '')
                        ? (int) $state
                        : (int) ($options['default_adult_pax'] ?? 2);
                }),

            ImportColumn::make('child_pax')
                ->numeric()
                ->rules(['nullable', 'integer'])
                ->label('Anak (Pax)')
                ->fillRecordUsing(function (Guest $record, $state, array $options): void {
                    $record->child_pax = (isset($state) && $state !== '')
                        ? (int) $state
                        : (int) ($options['default_child_pax'] ?? 2);
                }),
        ];
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Select::make('event_id')
                ->label('Pilih Acara')
                ->options(Event::where('is_active', true)->pluck('name', 'id'))
                ->default(Event::where('is_active', true)->latest()->first()?->id)
                ->required(),

            Select::make('tenant_id')
                ->label('Pilih Tenant Pengundang')
                ->options(Tenant::pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->visible(fn () => auth()->user()->role === 'admin')
                ->required(fn () => auth()->user()->role === 'admin')
                ->default(fn () => auth()->user()->role === 'tenant' ? auth()->user()->tenant?->id : null),

            Grid::make(2)->schema([
                TextInput::make('default_adult_pax')
                    ->label('Default Dewasa (Per Undangan)')
                    ->numeric()
                    ->default(2)
                    ->required(),
                TextInput::make('default_child_pax')
                    ->label('Default Anak (Per Undangan)')
                    ->numeric()
                    ->default(2)
                    ->required(),
            ]),
        ];
    }

    public function resolveRecord(): ?Guest
    {
        $eventId = $this->options['event_id'];
        $tenantId = auth()->user()->role === 'admin'
            ? ($this->options['tenant_id'] ?? null)
            : auth()->user()->tenant?->id;

        $defaultAdult = (int) ($this->options['default_adult_pax'] ?? 2);
        $defaultChild = (int) ($this->options['default_child_pax'] ?? 2);

        $name = trim($this->data['name']);

        // Bersihkan nomor handphone (buang spasi, strip, dll) jika ada
        $rawPhone = $this->data['phone'] ?? null;
        $cleanPhone = $rawPhone ? preg_replace('/[^0-9]/', '', $rawPhone) : null;

        // Tentukan Kunci Pencarian Unik
        if (! empty($cleanPhone)) {
            // Kunci 1: Berdasarkan Event, Tenant, dan No HP
            $criteria = [
                'event_id' => $eventId,
                'tenant_id' => $tenantId,
                'phone' => $this->data['phone'], // Simpan sesuai input asli, atau gunakan $cleanPhone
            ];
        } else {
            // Kunci 2: Fallback jika No HP kosong (Berdasarkan Event, Tenant, dan Nama)
            $criteria = [
                'event_id' => $eventId,
                'tenant_id' => $tenantId,
                'name' => $name,
            ];
        }

        // Cari data yang sudah ada, atau inisialisasi data baru
        $guest = Guest::firstOrNew($criteria);

        // Jika data baru, periksa kuota undangan tenant terlebih dahulu
        if (! $guest->exists) {
            if ($tenantId) {
                $eventTenant = DB::table('event_tenant')
                    ->where('event_id', $eventId)
                    ->where('tenant_id', $tenantId)
                    ->first();

                if ($eventTenant && $eventTenant->quota > 0) {
                    $currentGuestCount = Guest::where('event_id', $eventId)
                        ->where('tenant_id', $tenantId)
                        ->count();

                    if ($currentGuestCount >= (int) $eventTenant->quota) {
                        throw new RowImportFailedException(
                            "Batas kuota undangan ({$eventTenant->quota}) untuk tenant ini pada acara tersebut sudah tercapai."
                        );
                    }
                }
            }

            $guest->qr_code = (string) Str::uuid();
            $guest->is_attended = false;
        }

        // Update / Set atribut lainnya
        $guest->name = $name;
        $guest->phone = $this->data['phone'] ?? null;
        $guest->gender = $this->data['gender'] ?? null;
        $guest->job_type = $this->data['job_type'] ?? null;
        $guest->adult_pax = (isset($this->data['adult_pax']) && $this->data['adult_pax'] !== '')
            ? (int) $this->data['adult_pax']
            : $defaultAdult;
        $guest->child_pax = (isset($this->data['child_pax']) && $this->data['child_pax'] !== '')
            ? (int) $this->data['child_pax']
            : $defaultChild;

        return $guest;
    }

    // Lapisan pengaman terakhir sebelum INSERT SQL
    protected function beforeSave(): void
    {
        if ($this->record->adult_pax === null || $this->record->adult_pax === '') {
            $this->record->adult_pax = (int) ($this->options['default_adult_pax'] ?? 2);
        }

        if ($this->record->child_pax === null || $this->record->child_pax === '') {
            $this->record->child_pax = (int) ($this->options['default_child_pax'] ?? 2);
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import tamu selesai: '.number_format($import->successful_rows).' baris berhasil diimpor.';
        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ('.number_format($failedRowsCount).' baris gagal).';
        }

        return $body;
    }
}
