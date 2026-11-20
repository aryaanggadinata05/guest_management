<?php

namespace App\Filament\Resources\GuestResource\Pages;

use App\Filament\Resources\GuestResource;
use App\Models\Event;
use App\Models\Guest;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class ListGuests extends ListRecords
{
    protected static string $resource = GuestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_report')
                ->label('Cetak Laporan Lengkap')
                ->icon('heroicon-o-presentation-chart-line')
                ->color('success')
                ->modalHeading('Download Laporan Kehadiran')
                ->modalDescription('Pilih acara untuk menghasilkan laporan eksekutif (Excel) beserta grafik analitik.')
                ->modalSubmitActionLabel('Generate & Download Excel')
                ->modalWidth('md')
                ->form([
                    Select::make('event_id')
                        ->label('Pilih Acara')
                        ->options(Event::pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->default(fn () => Event::where('is_active', true)->latest()->first()?->id),
                ])
                ->action(function (array $data) {
                    $event = Event::find($data['event_id']);

                    // 1. Ambil Data
                    $guests = Guest::with(['tenant', 'attendance', 'table'])
                        ->where('event_id', $event->id)
                        ->get();

                    $jsonData = $guests->map(function ($g) {
                        return [
                            'Guest ID' => 'G-'.str_pad($g->id, 3, '0', STR_PAD_LEFT),
                            'Name' => $g->name,
                            'Phone' => $g->phone ?? '-',
                            'Gender' => $g->gender ?? '-',
                            'Job Type' => $g->job_type ?? '-',
                            'Tenant' => $g->tenant ? $g->tenant->name : 'Tanpa Pengundang',
                            'Status' => $g->is_attended ? 'Attended' : 'No Show',
                            'Check-In Time' => $g->attendance && $g->attendance->scanned_at ? Carbon::parse($g->attendance->scanned_at)->format('Y-m-d H:i:s') : 'N/A',
                            'Table Plot' => $g->table ? $g->table->name : '-',
                            'RSVP Adult' => $g->adult_pax ?? 0,
                            'RSVP Child' => $g->child_pax ?? 0,
                            'Actual Adult' => $g->attendance ? $g->attendance->adult_count : 0,
                            'Actual Child' => $g->attendance ? $g->attendance->child_count : 0,
                        ];
                    });

                    // 2. Siapkan Path File
                    $uniqueId = uniqid();
                    $jsonPath = storage_path("app/temp_guests_{$uniqueId}.json");
                    $fileName = 'Laporan_Kehadiran_'.Str::slug($event->name).'_'.date('Ymd_His').'.xlsx';
                    $excelPath = storage_path("app/{$fileName}");

                    file_put_contents($jsonPath, $jsonData->toJson());

                    $scriptPath = storage_path('app/scripts/generate_report.py');

                    // 3. Eksekusi Python
                    $result = Process::run("python3 \"{$scriptPath}\" \"{$jsonPath}\" \"{$excelPath}\"");

                    // 4. Tangani Hasilnya
                    if ($result->successful()) {
                        @unlink($jsonPath);

                        // Download file menggunakan fitur bawaan Filament/Livewire
                        return response()->download($excelPath)->deleteFileAfterSend(true);
                    } else {
                        @unlink($jsonPath);
                        // Munculkan notifikasi jika terjadi error di Python
                        Notification::make()
                            ->title('Gagal Membuat Laporan')
                            ->body('Pesan Error Python: '.$result->errorOutput())
                            ->danger()
                            ->duration(10000) // Tampil 10 detik agar bisa dibaca
                            ->send();
                    }
                }),

            CreateAction::make(),
        ];
    }
}
