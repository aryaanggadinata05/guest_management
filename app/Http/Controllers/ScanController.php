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

/**
 * Controller untuk halaman Meja Registrasi (/scan), verifikasi QR, check-in, dan Walk-In.
 *
 * @author AAA-05
 * @copyright Copyright (c) 2026 AAA-05
 */
class ScanController extends Controller
{
    public function index(): View
    {
        if (auth()->user()->role === 'tenant') {
            abort(403, 'Akses Ditolak. Tenant tidak memiliki akses ke halaman registrasi.');
        }

        /** @var User $user */
        $user = auth()->user();
        $settings = Setting::get();
        $events = $user->getAccessibleEvents();

        $activeEvent = null;
        if ($events->count() === 1) {
            $activeEvent = $events->first();
        } elseif ($user->preferred_event_id) {
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

    /**
     * Memeriksa apakah user memiliki izin mengakses event pada halaman /scan.
     */
    private function userCanAccessEvent(User $user, int $eventId): bool
    {
        return $user->getAccessibleEvents()->contains('id', $eventId);
    }

    private function unauthorizedEventResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Anda tidak memiliki izin untuk mengakses acara ini.',
        ], 403);
    }

    /**
     * Membangun payload statistik KPI dan 5 tamu check-in terakhir.
     *
     * @return array<string, mixed>
     */
    private function buildStatsPayload(int $eventId): array
    {
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
                    'guest_name' => $guest?->name ?? 'Tamu',
                    'tenant_name' => $guest?->tenant?->name ?? 'Tanpa Pengundang',
                    'table_name' => $guest?->table?->name ?? 'Tanpa Meja',
                    'adult_count' => (int) $att->adult_count,
                    'child_count' => (int) $att->child_count,
                    'scanned_time' => $att->scanned_at ? Carbon::parse($att->scanned_at)->format('H:i').' WIB' : '-',
                ];
            });

        return [
            'total' => $totalGuests,
            'attended' => $attendedGuests,
            'unattended' => $totalGuests - $attendedGuests,
            'adults' => (int) ($stats->adults ?? 0),
            'children' => (int) ($stats->children ?? 0),
            'recent' => $recentAttendances,
        ];
    }

    // Mengambil ringkasan statistik terkini beserta feed 5 tamu terakhir
    public function stats(Request $request): JsonResponse
    {
        $eventId = $request->event_id;
        if (! $eventId) {
            return response()->json([]);
        }

        /** @var User $user */
        $user = auth()->user();
        if (! $this->userCanAccessEvent($user, (int) $eventId)) {
            return $this->unauthorizedEventResponse();
        }

        return response()->json($this->buildStatsPayload((int) $eventId));
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

        if (! empty($validated['preferred_event_id']) && ! $this->userCanAccessEvent($user, (int) $validated['preferred_event_id'])) {
            return $this->unauthorizedEventResponse();
        }

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

        /** @var User $user */
        $user = auth()->user();
        if (! $this->userCanAccessEvent($user, (int) $guest->event_id)) {
            return $this->unauthorizedEventResponse();
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

        /** @var User $user */
        $user = auth()->user();

        return DB::transaction(function () use ($validated, $user) {
            $guest = Guest::with('event')->lockForUpdate()->findOrFail($validated['guest_id']);

            if (! $this->userCanAccessEvent($user, (int) $guest->event_id)) {
                return $this->unauthorizedEventResponse();
            }

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

        /** @var User $user */
        $user = auth()->user();
        if (! $this->userCanAccessEvent($user, (int) $eventId)) {
            return $this->unauthorizedEventResponse();
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

    // Ambil opsi tenant (beserta sisa kuota) & meja (beserta sisa kursi) untuk form Walk-In
    public function walkInOptions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
        ]);

        $eventId = (int) $validated['event_id'];

        /** @var User $user */
        $user = auth()->user();
        if (! $this->userCanAccessEvent($user, $eventId)) {
            return $this->unauthorizedEventResponse();
        }

        $event = Event::findOrFail($eventId);

        $guestCountsByTenant = Guest::where('event_id', $eventId)
            ->whereNotNull('tenant_id')
            ->selectRaw('tenant_id, COUNT(*) as total')
            ->groupBy('tenant_id')
            ->pluck('total', 'tenant_id');

        $tenants = $event->tenants()
            ->orderBy('name')
            ->get()
            ->map(function ($tenant) use ($guestCountsByTenant) {
                $quota = (int) ($tenant->pivot->quota ?? 0);
                $used = (int) ($guestCountsByTenant[$tenant->id] ?? 0);
                $remaining = max(0, $quota - $used);

                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'quota' => $quota,
                    'remaining_quota' => $remaining,
                ];
            });

        $tables = Table::where('event_id', $eventId)
            ->with(['guests.attendance'])
            ->orderBy('name')
            ->get()
            ->map(function (Table $table) {
                return [
                    'id' => $table->id,
                    'name' => $table->name,
                    'zone' => $table->zone,
                    'capacity' => $table->capacity,
                    'remaining' => $table->getRemainingSeats(),
                ];
            });

        return response()->json([
            'success' => true,
            'tenants' => $tenants,
            'tables' => $tables,
        ]);
    }

    // Registrasi Tamu Walk-In On-the-Spot + Auto Check-In + Plot Meja
    public function walkIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'adult_count' => ['required', 'integer', 'min:1', 'max:50'],
            'child_count' => ['required', 'integer', 'min:0', 'max:50'],
            'table_id' => ['nullable', 'integer', 'exists:tables,id'],
        ]);

        $eventId = (int) $validated['event_id'];

        /** @var User $user */
        $user = auth()->user();
        if (! $this->userCanAccessEvent($user, $eventId)) {
            return $this->unauthorizedEventResponse();
        }

        return DB::transaction(function () use ($validated, $eventId) {
            $event = Event::findOrFail($eventId);
            if (! $event->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acara ini sudah tidak aktif.',
                ], 422);
            }

            $adults = (int) $validated['adult_count'];
            $children = (int) $validated['child_count'];
            $totalSeats = $adults + $children;
            $tenantId = ! empty($validated['tenant_id']) ? (int) $validated['tenant_id'] : null;
            $tableId = ! empty($validated['table_id']) ? (int) $validated['table_id'] : null;

            // Validasi Kuota Tenant jika kategori pengundang dipilih
            if ($tenantId) {
                $eventTenant = DB::table('event_tenant')
                    ->where('event_id', $eventId)
                    ->where('tenant_id', $tenantId)
                    ->first();

                if (! $eventTenant) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tenant yang dipilih tidak terdaftar pada acara ini.',
                    ], 422);
                }

                $quota = (int) $eventTenant->quota;
                $currentGuestCount = Guest::where('event_id', $eventId)
                    ->where('tenant_id', $tenantId)
                    ->lockForUpdate()
                    ->count();

                if ($currentGuestCount >= $quota) {
                    return response()->json([
                        'success' => false,
                        'message' => "Kuota tenant untuk acara ini sudah penuh ({$currentGuestCount}/{$quota} undangan terpakai).",
                    ], 422);
                }
            }

            // Validasi Kapasitas Meja jika nomor meja dipilih
            if ($tableId) {
                $table = Table::with(['guests.attendance'])
                    ->where('event_id', $eventId)
                    ->lockForUpdate()
                    ->find($tableId);

                if (! $table) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Meja yang dipilih tidak ditemukan pada acara ini.',
                    ], 422);
                }

                $availableSeats = $table->getRemainingSeats();
                if ($totalSeats > $availableSeats) {
                    return response()->json([
                        'success' => false,
                        'message' => "Kapasitas meja {$table->name} tidak cukup! Tersisa {$availableSeats} kursi, namun tamu membutuhkan {$totalSeats} kursi.",
                    ], 422);
                }
            }

            // 1. Buat data Tamu Walk-In (is_attended = true, qr_code otomatis dibuat oleh model)
            $guest = Guest::create([
                'event_id' => $eventId,
                'tenant_id' => $tenantId,
                'table_id' => $tableId,
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'adult_pax' => $adults,
                'child_pax' => $children,
                'is_attended' => true,
            ]);

            // 2. Catat kehadiran pada tabel attendances
            Attendance::create([
                'guest_id' => $guest->id,
                'user_id' => auth()->id(),
                'adult_count' => $adults,
                'child_count' => $children,
                'scanned_at' => now(),
            ]);

            $guest->load(['tenant', 'table']);

            return response()->json([
                'success' => true,
                'message' => "Tamu Walk-In {$guest->name} berhasil didaftarkan & check-in ({$adults} Dewasa, {$children} Anak)!",
                'guest' => [
                    'id' => $guest->id,
                    'name' => $guest->name,
                    'phone' => $guest->phone,
                    'qr_code' => $guest->qr_code,
                    'tenant_name' => $guest->tenant?->name ?? 'Tanpa Pengundang',
                    'table_name' => $guest->table?->name ?? 'Tanpa Meja',
                ],
                'stats' => $this->buildStatsPayload($eventId),
            ]);
        });
    }
}
