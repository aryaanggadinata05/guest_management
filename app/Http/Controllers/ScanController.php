<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Setting;
use App\Models\Table;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ScanController extends Controller
{
    public function index(): View
    {
        if (auth()->user()->role === 'tenant') {
            abort(403, 'Akses Ditolak. Tenant tidak memiliki akses ke halaman registrasi.');
        }

        $user = auth()->user();
        $settings = Setting::get();
        $events = Event::where('is_active', true)->get();

        $activeEvent = null;
        if ($user->preferred_event_id) {
            $activeEvent = $events->firstWhere('id', $user->preferred_event_id);
        }

        if (! $activeEvent) {
            $activeEvent = $events->sortByDesc('id')->first();
        }

        $preferredMode = in_array($user->preferred_scan_mode, ['camera', 'manual'], true)
            ? $user->preferred_scan_mode
            : 'camera';

        return view('scan', compact('activeEvent', 'events', 'preferredMode', 'settings'));
    }

    // Mengambil ringkasan statistik terkini beserta feed 5 tamu terakhir
    public function stats(Request $request): JsonResponse
    {
        $eventId = $request->event_id;
        if (! $eventId) {
            return response()->json([]);
        }

        $totalGuests = Guest::where('event_id', $eventId)->count();
        $attendedGuests = Guest::where('event_id', $eventId)->where('is_attended', true)->count();

        $stats = Attendance::join('guests', 'attendances.guest_id', '=', 'guests.id')
            ->where('guests.event_id', $eventId)
            ->selectRaw('COALESCE(SUM(attendances.adult_count), 0) as adults, COALESCE(SUM(attendances.child_count), 0) as children')
            ->first();

        $recentAttendances = Attendance::with(['guest.tenant', 'guest.table'])
            ->whereHas('guest', function ($q) use ($eventId) {
                $q->where('event_id', $eventId);
            })
            ->orderByDesc('scanned_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(function (Attendance $att) {
                $guest = $att->guest;

                return [
                    'id' => $att->id,
                    'guest_name' => $guest->name ?? 'Tamu',
                    'tenant_name' => $guest->tenant->name ?? 'Umum',
                    'table_name' => $guest->table->name ?? 'Tanpa Meja',
                    'adult_count' => (int) $att->adult_count,
                    'child_count' => (int) $att->child_count,
                    'scanned_time' => $att->scanned_at ? Carbon::parse($att->scanned_at)->format('H:i').' WIB' : '-',
                ];
            });

        return response()->json([
            'total' => $totalGuests,
            'attended' => $attendedGuests,
            'unattended' => $totalGuests - $attendedGuests,
            'adults' => (int) ($stats->adults ?? 0),
            'children' => (int) ($stats->children ?? 0),
            'recent' => $recentAttendances,
        ]);
    }

    // Simpan preferensi mode scan dan acara aktif per-user
    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'preferred_scan_mode' => ['nullable', 'string', 'in:camera,manual'],
            'preferred_event_id' => ['nullable', 'integer', 'exists:events,id'],
        ]);

        /** @var User $user */
        $user = auth()->user();
        $user->update(array_filter($validated, fn ($val) => ! is_null($val)));

        return response()->json([
            'status' => 'ok',
            'preferred_scan_mode' => $user->preferred_scan_mode,
            'preferred_event_id' => $user->preferred_event_id,
        ]);
    }

    // Ubah password user dari halaman scan
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:4'],
            'new_password_confirmation' => ['required', 'string', 'same:new_password'],
        ]);

        /** @var User $user */
        $user = auth()->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Kata sandi saat ini tidak cocok.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kata sandi Anda berhasil diperbarui!',
        ]);
    }

    // Verifikasi QR Code & Muat Data Beserta Sisa Meja (Optimasi Kueri Anti N+1)
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'qr_code' => ['required', 'string'],
        ]);

        $guest = Guest::with(['tenant', 'event', 'attendance', 'table'])
            ->where('qr_code', $request->qr_code)
            ->first();

        if (! $guest) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code tidak terdaftar dalam sistem!',
            ]);
        }

        if (! $guest->event || ! $guest->event->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Acara "'.($guest->event->name ?? '-').'" sudah tidak aktif!',
            ]);
        }

        // Ambil daftar meja dengan perhitungan kursi hibrida secara efisien dalam 1 kueri agregat
        $tables = Table::where('event_id', $guest->event_id)
            ->with(['guests.attendance'])
            ->get()
            ->map(function (Table $t) use ($guest) {
                $attendedSeats = 0;
                $unattendedSeats = 0;

                foreach ($t->guests as $tableGuest) {
                    if ($tableGuest->id === $guest->id) {
                        continue;
                    }

                    if ($tableGuest->is_attended && $tableGuest->attendance) {
                        $attendedSeats += ((int) $tableGuest->attendance->adult_count + (int) $tableGuest->attendance->child_count);
                    } else {
                        $unattendedSeats += ((int) $tableGuest->adult_pax + (int) $tableGuest->child_pax);
                    }
                }

                $remaining = $t->capacity - ($attendedSeats + $unattendedSeats);

                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'capacity' => $t->capacity,
                    'remaining' => $remaining,
                ];
            });

        return response()->json([
            'success' => true,
            'guest' => $guest,
            'attendance' => $guest->attendance,
            'already_attended' => (bool) $guest->is_attended,
            'tables' => $tables,
        ]);
    }

    // Submit Check-In & Simpan Koreksi Profil dengan Validasi Ketat & Database Transaction
    public function submit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'guest_id' => ['required', 'integer', 'exists:guests,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'in:L,P'],
            'job_type' => ['nullable', 'string', 'max:255'],
            'adult_count' => ['required', 'integer', 'min:1', 'max:50'],
            'child_count' => ['required', 'integer', 'min:0', 'max:50'],
            'table_id' => ['nullable', 'integer', 'exists:tables,id'],
        ]);

        return DB::transaction(function () use ($validated) {
            $guest = Guest::with('event')->lockForUpdate()->findOrFail($validated['guest_id']);
            $adults = (int) $validated['adult_count'];
            $children = (int) $validated['child_count'];
            $totalSeats = $adults + $children;

            // Validasi Kapasitas Meja
            if (! empty($validated['table_id'])) {
                $table = Table::with(['guests.attendance'])->lockForUpdate()->find($validated['table_id']);

                if ($table) {
                    $attendedSeats = 0;
                    $unattendedSeats = 0;

                    foreach ($table->guests as $tableGuest) {
                        if ($tableGuest->id === $guest->id) {
                            continue;
                        }

                        if ($tableGuest->is_attended && $tableGuest->attendance) {
                            $attendedSeats += ((int) $tableGuest->attendance->adult_count + (int) $tableGuest->attendance->child_count);
                        } else {
                            $unattendedSeats += ((int) $tableGuest->adult_pax + (int) $tableGuest->child_pax);
                        }
                    }

                    $availableSeats = $table->capacity - ($attendedSeats + $unattendedSeats);

                    if ($totalSeats > $availableSeats) {
                        return response()->json([
                            'success' => false,
                            'message' => "Kapasitas meja {$table->name} tidak cukup! Tersisa {$availableSeats} kursi, namun rombongan berjumlah {$totalSeats} orang.",
                        ]);
                    }
                }
            }

            // 1. Update profil & alokasi meja tamu
            $guest->update([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'gender' => $validated['gender'] ?? null,
                'job_type' => $validated['job_type'] ?? null,
                'table_id' => $validated['table_id'] ?: null,
                'is_attended' => true,
            ]);

            // 2. Simpan atau perbarui log kehadiran di tabel attendances
            Attendance::updateOrCreate(
                ['guest_id' => $guest->id],
                [
                    'user_id' => auth()->id(),
                    'adult_count' => $adults,
                    'child_count' => $children,
                    'scanned_at' => now(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => "Kehadiran {$guest->name} berhasil dikonfirmasi ({$adults} Dewasa, {$children} Anak)!",
            ]);
        });
    }

    // Pencarian Manual Cepat (Nama / No HP)
    public function search(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->keyword);
        $eventId = $request->event_id;

        if (strlen($keyword) < 2 || ! $eventId) {
            return response()->json([
                'success' => true,
                'guests' => [],
            ]);
        }

        $guests = Guest::with(['tenant'])
            ->where('event_id', $eventId)
            ->where(function ($q) use ($keyword) {
                $cleanPhone = preg_replace('/[^0-9]/', '', $keyword);
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('qr_code', $keyword);

                if (! empty($cleanPhone)) {
                    $q->orWhere('phone', 'like', "%{$cleanPhone}%");
                }
            })
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'guests' => $guests,
        ]);
    }
}
