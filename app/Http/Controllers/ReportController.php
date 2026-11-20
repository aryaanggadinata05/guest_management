<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Guest;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function downloadExcel(int|string $event_id): BinaryFileResponse|RedirectResponse
    {
        // Keamanan: Hanya admin yang boleh mengunduh laporan eksekutif
        if (! auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Akses Ditolak. Hanya Administrator yang dapat mengunduh laporan ini.');
        }

        $event = Event::findOrFail($event_id);

        // 1. Ambil data mentah dari database
        $guests = Guest::with(['tenant', 'attendance', 'table'])
            ->where('event_id', $event_id)
            ->get();

        // 2. Format / Map Data menjadi JSON bersih agar mudah diolah Python
        $data = $guests->map(function ($g) {
            $isAttended = (bool) $g->is_attended;

            return [
                'Guest ID' => 'G-'.str_pad($g->id, 3, '0', STR_PAD_LEFT),
                'Name' => $g->name,
                'Phone' => $g->phone ?? '-',
                'Gender' => $g->gender ?? '-',
                'Job Type' => $g->job_type ?? '-',
                'Tenant' => $g->tenant ? $g->tenant->name : 'Tanpa Pengundang',
                'Status' => $isAttended ? 'Attended' : 'No Show',
                'Check-In Time' => $g->attendance && $g->attendance->scanned_at ? Carbon::parse($g->attendance->scanned_at)->format('Y-m-d H:i:s') : 'N/A',
                'Table Plot' => $g->table ? $g->table->name : '-',
                'RSVP Adult' => $g->adult_pax ?? 0,
                'RSVP Child' => $g->child_pax ?? 0,
                'Actual Adult' => $g->attendance ? $g->attendance->adult_count : 0,
                'Actual Child' => $g->attendance ? $g->attendance->child_count : 0,
            ];
        });

        // 3. Tentukan Lokasi Penyimpanan Sementara
        $uniqueId = uniqid();
        $jsonPath = storage_path("app/temp_guests_{$uniqueId}.json");
        $fileName = 'Laporan_Kehadiran_'.Str::slug($event->name).'_'.date('Ymd_His').'.xlsx';
        $excelPath = storage_path("app/{$fileName}");

        // 4. Simpan Data JSON
        file_put_contents($jsonPath, $data->toJson());

        // 5. Panggil Script Python
        $scriptPath = storage_path('app/scripts/generate_report.py');
        $pythonBinary = env('PYTHON_BINARY') ?: (file_exists(base_path('.venv/bin/python3')) ? base_path('.venv/bin/python3') : 'python3');

        $result = Process::run("{$pythonBinary} \"{$scriptPath}\" \"{$jsonPath}\" \"{$excelPath}\"");

        if ($result->successful() && file_exists($excelPath)) {
            // Hapus file JSON sementara karena sudah tidak terpakai
            @unlink($jsonPath);

            // Return ke browser sebagai download, lalu hapus Excel dari server
            return response()->download($excelPath)->deleteFileAfterSend(true);
        } else {
            // Jika Python error, bersihkan JSON dan tampilkan pesan error
            @unlink($jsonPath);

            return back()->with('error', 'Gagal memproses Python Report: '.$result->errorOutput());
        }
    }
}
