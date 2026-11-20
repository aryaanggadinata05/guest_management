<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Guest;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AttendanceStats extends BaseWidget
{
    use InteractsWithPageFilters; // 2. Panggil trait di sini

    protected function getStats(): array
    {
        $user = auth()->user();

        // 3. Ambil nilai dari filter dashboard
        $eventId = $this->filters['event_id'] ?? null;
        $tenantId = $this->filters['tenant_id'] ?? null;

        $guestQuery = Guest::query();
        $attendanceQuery = Attendance::query();

        // 4. Terapkan Filter Event (Jika dipilih)
        if ($eventId) {
            $guestQuery->where('event_id', $eventId);
            $attendanceQuery->whereHas('guest', function ($q) use ($eventId) {
                $q->where('event_id', $eventId);
            });
        }

        // 5. Terapkan Filter Tenant
        if ($user->role === 'tenant') {
            // Tenant hanya melihat datanya sendiri
            $guestQuery->where('tenant_id', $user->tenant->id);
            $attendanceQuery->whereHas('guest', function ($q) use ($user) {
                $q->where('tenant_id', $user->tenant->id);
            });
        } elseif ($tenantId) {
            // Admin memfilter berdasarkan tenant tertentu
            $guestQuery->where('tenant_id', $tenantId);
            $attendanceQuery->whereHas('guest', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            });
        }

        $totalGuest = (clone $guestQuery)->count();
        $totalAttended = (clone $guestQuery)->where('is_attended', true)->count();
        $totalAdult = (clone $attendanceQuery)->sum('adult_count');
        $totalChild = (clone $attendanceQuery)->sum('child_count');

        return [
            Stat::make('Total Undangan', number_format($totalGuest))
                ->description('Tamu terdaftar dalam sistem')
                ->descriptionIcon('heroicon-m-envelope')
                ->color('gray'),
            Stat::make('Sudah Check-In', number_format($totalAttended))
                ->description('Tamu telah konfirmasi hadir')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
            Stat::make('Realisasi Dewasa', number_format((int) $totalAdult))
                ->description('Jumlah riil tamu dewasa')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),
            Stat::make('Realisasi Anak-anak', number_format((int) $totalChild))
                ->description('Jumlah riil tamu anak-anak')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('warning'),
        ];
    }
}
