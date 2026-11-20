<?php

namespace App\Filament\Resources;

use App\Filament\Imports\GuestImporter;
use App\Filament\Resources\GuestResource\Pages;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Setting;
use App\Services\WhatsAppService;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GuestResource extends Resource
{
    protected static ?string $model = Guest::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Daftar Tamu';

    protected static ?string $navigationGroup = 'Operasional Acara';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Tamu';

    protected static ?string $pluralModelLabel = 'Daftar Tamu';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('event_id')
                    ->relationship('event', 'name', function (Builder $query) {
                        $query->where('is_active', true);

                        // Jika yang login Tenant, tampilkan hanya acara miliknya
                        if (auth()->user()->role === 'tenant' && auth()->user()->tenant_id) {
                            $query->whereHas('tenants', function ($q) {
                                $q->where('tenants.id', auth()->user()->tenant_id);
                            });
                        }
                    })
                    ->required()
                    ->label('Pilih Acara'),

                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required()
                    ->label('Tenant / Pengundang')
                    ->visible(fn () => auth()->user()->role === 'admin')
                    ->default(fn () => auth()->user()->role === 'tenant' ? auth()->user()->tenant?->id : null),

                TextInput::make('name')->required()->label('Nama Tamu')->maxLength(255),
                TextInput::make('phone')->label('Nomor Handphone')->tel(),
                Select::make('gender')
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ])->label('Jenis Kelamin'),
                TextInput::make('job_type')->label('Pekerjaan'),

                Grid::make(2)->schema([
                    TextInput::make('adult_pax')
                        ->label('Rencana Dewasa (Pax)')
                        ->numeric()
                        ->default(2)
                        ->required(),
                    TextInput::make('child_pax')
                        ->label('Rencana Anak (Pax)')
                        ->numeric()
                        ->default(2)
                        ->required(),
                ]),

                Select::make('table_id')
                    ->label('Plot Nomor Meja')
                    ->options(function (Forms\Get $get, ?Guest $record) {
                        $eventId = $get('event_id');
                        if (! $eventId) {
                            return [];
                        }

                        return \App\Models\Table::where('event_id', $eventId)
                            ->with(['guests.attendance'])
                            ->get()
                            ->mapWithKeys(function (\App\Models\Table $table) use ($record) {
                                $attendedSeats = 0;
                                $unattendedSeats = 0;

                                foreach ($table->guests as $tableGuest) {
                                    if ($record && $tableGuest->id === $record->id) {
                                        continue;
                                    }

                                    if ($tableGuest->is_attended && $tableGuest->attendance) {
                                        $attendedSeats += ((int) $tableGuest->attendance->adult_count + (int) $tableGuest->attendance->child_count);
                                    } else {
                                        $unattendedSeats += ((int) $tableGuest->adult_pax + (int) $tableGuest->child_pax);
                                    }
                                }

                                $remaining = $table->capacity - ($attendedSeats + $unattendedSeats);
                                $isCurrentTable = $record && $record->table_id === $table->id;

                                if ($remaining <= 0 && ! $isCurrentTable) {
                                    return [];
                                }

                                $label = "{$table->name} (Sisa Kursi: {$remaining})";
                                if ($isCurrentTable) {
                                    $label .= ' [Meja Terpilih]';
                                }

                                return [$table->id => $label];
                            });
                    })
                    ->searchable()
                    ->preload()
                    ->visible(fn () => auth()->user()->role === 'admin'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(GuestImporter::class)
                    ->label('Import Tamu')
                    ->color('primary')
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->columns([
                TextColumn::make('name')->searchable()->label('Nama Tamu'),
                TextColumn::make('phone')->label('Nomor Handphone')->copyable(),
                TextColumn::make('event.name')->label('Acara'),
                TextColumn::make('tenant.name')
                    ->label('Pengundang')
                    ->visible(fn () => auth()->user()->role === 'admin'),
                TextColumn::make('qr_code')
                    ->label('QR Code (UUID)')
                    ->copyable()
                    ->limit(10),

                TextColumn::make('adult_pax')
                    ->label('Rencana Dewasa')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('child_pax')
                    ->label('Rencana Anak')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_attended')
                    ->label('Status Hadir')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('wa_sent_at')
                    ->label('Undangan WA')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Terkirim ('.date('d/m H:i', strtotime($state)).')' : 'Belum Dikirim')
                    ->placeholder('Belum Dikirim')
                    ->default('Belum Dikirim')
                    ->sortable(),

                TextColumn::make('attendance.scanned_at')
                    ->label('Waktu Check-in')
                    ->dateTime('d M Y, H:i:s')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('attendance.adult_count')
                    ->label('Dewasa (Hadir)')
                    ->placeholder('0')
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('attendance.child_count')
                    ->label('Anak (Hadir)')
                    ->placeholder('0')
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('attendance.scanner.name')
                    ->label('Petugas Scanner')
                    ->placeholder('-')
                    ->badge()
                    ->color('info')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('event_filter')
                    ->form([
                        Select::make('status')
                            ->label('Status Acara')
                            ->options([
                                'active' => 'Hanya Acara Aktif',
                                'inactive' => 'Hanya Arsip (Non-Aktif)',
                                'all' => 'Semua Acara (Aktif & Arsip)',
                            ])
                            ->default('active')
                            ->selectablePlaceholder(false)
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('event_id', null)),

                        Select::make('event_id')
                            ->label('Pilih Event')
                            ->placeholder('Semua Event Sesuai Status')
                            ->options(function (Forms\Get $get) {
                                $status = $get('status');
                                $query = Event::query();

                                if ($status === 'active') {
                                    $query->where('is_active', true);
                                } elseif ($status === 'inactive') {
                                    $query->where('is_active', false);
                                }

                                return $query->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (($data['status'] ?? 'active') === 'active') {
                            $query->whereHas('event', fn ($q) => $q->where('is_active', true));
                        } elseif (($data['status'] ?? null) === 'inactive') {
                            $query->whereHas('event', fn ($q) => $q->where('is_active', false));
                        }

                        if (! empty($data['event_id'])) {
                            $query->where('event_id', $data['event_id']);
                        }

                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (($data['status'] ?? 'active') === 'active') {
                            $indicators['status'] = 'Status Acara: Hanya Acara Aktif';
                        } elseif (($data['status'] ?? null) === 'inactive') {
                            $indicators['status'] = 'Status Acara: Hanya Arsip (Non-Aktif)';
                        }

                        if (! empty($data['event_id'])) {
                            $eventName = Event::find($data['event_id'])?->name;
                            if ($eventName) {
                                $indicators['event_id'] = 'Acara: '.$eventName;
                            }
                        }

                        return $indicators;
                    }),

                SelectFilter::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->label('Filter Tenant')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => auth()->user()->role === 'admin'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // TAMBAHAN: Tombol Preview QR Code di Layar (Modal)
                Action::make('preview_qr')
                    ->label('Preview QR')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (Guest $record) => 'QR Code: '.$record->name)
                    ->modalSubmitAction(false) // Hilangkan tombol "Submit" di modal
                    ->modalCancelActionLabel('Tutup') // Ganti tombol "Cancel" menjadi "Tutup"
                    ->modalContent(function (Guest $record) {
                        // Generate SVG untuk ditampilkan langsung di layar browser (tajam di ukuran berapapun)
                        $qrCodeSvg = QrCode::format('svg')
                            ->size(250)
                            ->margin(2)
                            ->generate($record->qr_code);

                        return view('filament.components.qr-preview', [
                            'qrCodeSvg' => (string) $qrCodeSvg,
                            'guestName' => $record->name,
                            'tenantName' => $record->tenant?->name ?? 'Tanpa Pengundang',
                        ]);
                    })
                    ->visible(fn (Guest $record) => auth()->user()->role === 'admin' || $record->event->is_active),

                // PERBAIKAN: Tombol Download Menggunakan Format PNG agar terbaca di semua HP
                Action::make('download_qr')
                    ->label('Download QR')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function (Guest $record) {
                        // Ubah format('svg') menjadi format('png')
                        $qrCodeImage = QrCode::format('png')
                            ->size(400) // Ukuran pixel (400x400)
                            ->margin(2)
                            ->generate($record->qr_code);

                        // File berekstensi .png
                        return response()->streamDownload(function () use ($qrCodeImage) {
                            echo $qrCodeImage;
                        }, 'QR-'.Str::slug($record->name).'.png');
                    })
                    ->visible(fn (Guest $record) => auth()->user()->role === 'admin' && $record->event->is_active),

                Action::make('send_wa_me')
                    ->label('Kirim WA')
                    ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
                    ->color('success')
                    ->form([
                        TextInput::make('phone')
                            ->label('Nomor WhatsApp')
                            ->default(fn (Guest $record) => $record->phone)
                            ->tel()
                            ->required()
                            ->helperText('Nomor akan otomatis diformat ke format internasional (+62).'),
                        Forms\Components\Textarea::make('message')
                            ->label('Isi Pesan Undangan')
                            ->rows(6)
                            ->default(fn (Guest $record) => WhatsAppService::buildMessage($record))
                            ->required(),
                    ])
                    ->modalHeading(fn (Guest $record) => 'Kirim Undangan WhatsApp: '.$record->name)
                    ->modalSubmitActionLabel('Buka WhatsApp (wa.me)')
                    ->action(function (Guest $record, array $data) {
                        $phone = WhatsAppService::formatPhoneNumber($data['phone']);
                        if (! $phone) {
                            Notification::make()
                                ->title('Nomor WhatsApp tidak valid')
                                ->danger()
                                ->send();

                            return;
                        }

                        if ($data['phone'] !== $record->phone) {
                            $record->update(['phone' => $data['phone']]);
                        }

                        $record->update(['wa_sent_at' => now()]);

                        $waUrl = 'https://wa.me/'.$phone.'?text='.rawurlencode($data['message']);

                        Notification::make()
                            ->title('Membuka WhatsApp')
                            ->body("Undangan untuk {$record->name} siap dikirim.")
                            ->success()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('open_wa')
                                    ->label('Buka WhatsApp')
                                    ->url($waUrl, shouldOpenInNewTab: true),
                            ])
                            ->send();

                        return redirect()->away($waUrl);
                    })
                    ->visible(fn (Guest $record) => auth()->user()->role === 'admin' && $record->event->is_active),

                Action::make('send_wa_gateway')
                    ->label('Kirim Gateway')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Guest $record) => "Kirim via Gateway ke {$record->name}")
                    ->modalDescription(fn (Guest $record) => 'Pesan undangan dan link e-tiket akan otomatis dikirimkan via WhatsApp Gateway ke '.($record->phone ?: '(belum ada nomor)').'.')
                    ->action(function (Guest $record) {
                        $result = WhatsAppService::sendViaGateway($record);

                        if ($result['success']) {
                            Notification::make()
                                ->title('Berhasil Terkirim')
                                ->body($result['message'])
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Pengiriman Gagal')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Guest $record) => auth()->user()->role === 'admin' && $record->event->is_active && filled(Setting::get()->wa_gateway_api_key)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(function ($livewire) {
                            $status = $livewire->tableFilters['event_filter']['status'] ?? 'active';

                            return $status !== 'inactive';
                        })
                        ->action(function (Collection $records) {
                            $activeRecords = $records->filter(fn (Guest $record) => $record->event->is_active);
                            $inactiveCount = $records->count() - $activeRecords->count();

                            $activeRecords->each->delete();

                            if ($inactiveCount > 0) {
                                Notification::make()
                                    ->warning()
                                    ->title('Perhatian')
                                    ->body("{$inactiveCount} data tamu tidak dihapus karena berasal dari acara yang sudah Non-Aktif (Arsip).")
                                    ->send();
                            } else {
                                Notification::make()
                                    ->success()
                                    ->title('Berhasil menghapus data tamu')
                                    ->send();
                            }
                        }),

                    ExportBulkAction::make()
                        ->label('Export Excel')
                        ->exports([
                            ExcelExport::make()
                                ->fromTable()
                                ->withFilename('Laporan_Tamu_'.date('Y-m-d_His'))
                                ->withColumns([
                                    Column::make('name')->heading('Nama Tamu'),
                                    Column::make('phone')->heading('Nomor Handphone'),
                                    Column::make('event.name')->heading('Nama Acara'),
                                    Column::make('tenant.name')->heading('Tenant Pengundang'),
                                    Column::make('adult_pax')->heading('Rencana Dewasa'),
                                    Column::make('child_pax')->heading('Rencana Anak'),
                                    Column::make('is_attended')
                                        ->heading('Status Kehadiran')
                                        ->formatStateUsing(fn ($state) => $state ? 'Hadir' : 'Tidak Hadir'),
                                    Column::make('attendance.scanned_at')->heading('Waktu Check-in'),
                                    Column::make('attendance.adult_count')->heading('Hadir Dewasa'),
                                    Column::make('attendance.child_count')->heading('Hadir Anak'),
                                    Column::make('attendance.scanner.name')->heading('Petugas Scanner'),
                                ]),
                        ]),

                    BulkAction::make('print_qr_bulk')
                        ->label('Cetak QR (Massal)')
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->visible(function ($livewire) {
                            $status = $livewire->tableFilters['event_filter']['status'] ?? 'active';

                            return $status !== 'inactive';
                        })
                        ->action(function (Collection $records) {
                            $activeIds = $records->filter(fn (Guest $record) => $record->event->is_active)
                                ->pluck('id')
                                ->toArray();

                            if (empty($activeIds)) {
                                Notification::make()
                                    ->danger()
                                    ->title('Gagal Mencetak')
                                    ->body('Tidak dapat mencetak QR Code untuk acara yang sudah Non-Aktif.')
                                    ->send();

                                return;
                            }

                            Session::put('print_qr_ids', $activeIds);

                            return redirect()->route('print.qr');
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('broadcast_whatsapp')
                        ->label('Broadcast WhatsApp (Gateway)')
                        ->icon('heroicon-o-megaphone')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Broadcast Undangan WhatsApp')
                        ->modalDescription('Pesan undangan dan link e-tiket akan dikirimkan otomatis melalui WhatsApp Gateway ke semua tamu terpilih yang memiliki nomor HP valid.')
                        ->modalSubmitActionLabel('Mulai Kirim Broadcast')
                        ->action(function (Collection $records) {
                            $settings = Setting::get();

                            if (blank($settings->wa_gateway_api_key)) {
                                Notification::make()
                                    ->title('Gateway Belum Terhubung')
                                    ->body('Silakan atur API Key WhatsApp Gateway di menu Pengaturan Tampilan terlebih dahulu.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $successCount = 0;
                            $failCount = 0;

                            foreach ($records as $guest) {
                                if (blank($guest->phone)) {
                                    $failCount++;

                                    continue;
                                }

                                $result = WhatsAppService::sendViaGateway($guest);
                                if ($result['success']) {
                                    $successCount++;
                                } else {
                                    $failCount++;
                                }

                                usleep(300000); // 0.3 detik jeda antar pengiriman
                            }

                            Notification::make()
                                ->title('Broadcast Selesai')
                                ->body("Berhasil terkirim: {$successCount} tamu. Gagal/Tanpa nomor: {$failCount} tamu.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => auth()->user()->role === 'admin' && filled(Setting::get()->wa_gateway_api_key)),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['attendance.scanner', 'event', 'tenant']);

        if (auth()->user()->role === 'tenant') {
            return $query->where('tenant_id', auth()->user()->tenant?->id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuests::route('/'),
            'create' => Pages\CreateGuest::route('/create'),
            'edit' => Pages\EditGuest::route('/{record}/edit'),
        ];
    }
}
