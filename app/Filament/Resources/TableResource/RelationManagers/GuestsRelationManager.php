<?php

namespace App\Filament\Resources\TableResource\RelationManagers;

use App\Models\Attendance;
use App\Models\Guest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class GuestsRelationManager extends RelationManager
{
    protected static string $relationship = 'guests';

    protected static ?string $title = 'Daftar Tamu di Meja Ini';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama Tamu')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('phone')->label('No. Handphone'),
                Tables\Columns\TextColumn::make('tenant.name')->label('Tenant Pengundang'),
                Tables\Columns\IconColumn::make('is_attended')->label('Hadir')->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\Action::make('plot_guests')
                    ->label('Plot Tamu ke Meja Ini')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('guest_ids')
                            ->label('Pilih Tamu')
                            ->multiple()
                            ->searchable()
                            // 1. Pencarian Presisi: Mem-bypass fuzzy search JS dan langsung query nama ke database
                            ->getSearchResultsUsing(function (string $search, RelationManager $livewire) {
                                $tableRecord = $livewire->getOwnerRecord();

                                return Guest::with(['table', 'tenant'])
                                    ->where('event_id', $tableRecord->event_id)
                                    ->where('name', 'like', "%{$search}%") // Hanya cocokkan kata kunci dengan Nama
                                    ->limit(50) // Limit ringan agar server tidak beban
                                    ->get()
                                    ->mapWithKeys(function ($guest) {
                                        $last4Phone = $guest->phone ? substr($guest->phone, -4) : '----';
                                        $tenantName = $guest->tenant?->name ?? 'Tanpa Tenant';

                                        if ($guest->table) {
                                            $label = "{$guest->name}  •  [{$tenantName}]  (HP: ...{$last4Phone}) | ⚡ [Meja: {$guest->table->name}]";
                                        } else {
                                            $label = "{$guest->name}  •  [{$tenantName}]  (HP: ...{$last4Phone}) | 🟢 [Belum Diplot]";
                                        }

                                        return [$guest->id => $label];
                                    });
                            })
                            // 2. Daftar Default: Tampilan awal saat dropdown diklik (sebelum mengetik)
                            ->options(function (RelationManager $livewire) {
                                $tableRecord = $livewire->getOwnerRecord();

                                return Guest::with(['table', 'tenant'])
                                    ->where('event_id', $tableRecord->event_id)
                                    ->limit(50) // Batasi muatan awal agar memori browser tetap lega
                                    ->get()
                                    ->mapWithKeys(function ($guest) {
                                        $last4Phone = $guest->phone ? substr($guest->phone, -4) : '----';
                                        $tenantName = $guest->tenant?->name ?? 'Tanpa Tenant';

                                        if ($guest->table) {
                                            $label = "{$guest->name}  •  [{$tenantName}]  (HP: ...{$last4Phone}) | ⚡ [Meja: {$guest->table->name}]";
                                        } else {
                                            $label = "{$guest->name}  •  [{$tenantName}]  (HP: ...{$last4Phone}) | 🟢 [Belum Diplot]";
                                        }

                                        return [$guest->id => $label];
                                    });
                            })
                            ->required()
                            ->helperText('Ketik nama tamu untuk mencari dengan akurat. Tamu ⚡ sudah memiliki meja.'),
                    ])
                    ->action(function (array $data, RelationManager $livewire): void {
                        $tableRecord = $livewire->getOwnerRecord();
                        $guestIds = $data['guest_ids'] ?? [];

                        // 1. LOGIKA HIBRIDA: Hitung total kursi yang SUDAH TERISI di meja tersebut
                        $guestIdsInTable = $tableRecord->guests()->pluck('id');

                        $attendedSeats = Attendance::whereIn('guest_id', $guestIdsInTable)
                            ->selectRaw('SUM(adult_count + child_count) as total')
                            ->value('total') ?? 0;

                        $unattendedSeats = $tableRecord->guests()
                            ->whereNotIn('id', Attendance::whereIn('guest_id', $guestIdsInTable)->pluck('guest_id'))
                            ->selectRaw('SUM(adult_pax + child_pax) as total')
                            ->value('total') ?? 0;

                        $currentFilled = $attendedSeats + $unattendedSeats;

                        // 2. Hitung total kepala dari tamu-tamu yang BARU SAJA DIPILIH
                        $selectedGuests = Guest::whereIn('id', $guestIds)->get();
                        $newSeatsNeeded = $selectedGuests->sum('adult_pax') + $selectedGuests->sum('child_pax');

                        // 3. Validasi apakah masih muat?
                        if (($currentFilled + $newSeatsNeeded) > $tableRecord->capacity) {
                            $sisaKursi = $tableRecord->capacity - $currentFilled;
                            Notification::make()
                                ->danger()
                                ->title('Kapasitas Meja Penuh!')
                                ->body("Meja {$tableRecord->name} hanya memiliki sisa {$sisaKursi} kursi, tapi Anda mencoba memasukkan {$newSeatsNeeded} orang.")
                                ->send();

                            return;
                        }

                        // Update table_id pada tamu yang dipilih
                        Guest::whereIn('id', $guestIds)->update([
                            'table_id' => $tableRecord->id,
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Berhasil Memplot Tamu')
                            ->send();
                    }),
            ])
            ->actions([
                // Tombol untuk mengeluarkan tamu dari meja
                Tables\Actions\Action::make('detach_guest')
                    ->label('Keluarkan dari Meja')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Guest $record): void {
                        $record->update(['table_id' => null]);

                        Notification::make()
                            ->success()
                            ->title('Tamu dikeluarkan dari meja')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_detach')
                        ->label('Keluarkan Tamu Terpilih')
                        ->icon('heroicon-o-user-minus')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                $record->update(['table_id' => null]);
                            }

                            Notification::make()
                                ->success()
                                ->title('Tamu terpilih berhasil dikeluarkan dari meja')
                                ->send();
                        }),
                ]),
            ]);
    }
}
