<?php

namespace App\Filament\Pages;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Setting;
use App\Models\Table;
use App\Models\Tenant;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class TableMapping extends Page
{
    protected static ?string $title = 'Denah & Plotting Meja';

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Denah & Plotting Meja';

    protected static ?string $navigationGroup = 'Operasional Acara';

    protected static ?string $slug = 'denah-meja';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.table-mapping';

    // State Properties
    public ?int $selectedEventId = null;

    public string $mode = 'plotting'; // 'plotting' | 'position'

    public string $statusFilter = 'all'; // 'all' | 'available' | 'full'

    public string $tenantFilter = 'all'; // 'all' | (string) tenant_id

    public string $zoomLevel = '100'; // 'fit', '100', '125', '150', '200'

    public ?int $activeTableId = null; // ID meja yang sedang dibuka di Slide-Over Drawer

    public ?int $selectedTableForPosition = null; // ID meja yang dipilih untuk ditempatkan pada denah

    public ?int $selectedGuestToAssign = null; // ID tamu yang dipilih untuk di-assign

    public function getHeading(): string
    {
        return 'Denah & Plotting Meja';
    }

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function mount(): void
    {
        $firstEvent = Event::where('is_active', true)->orderByDesc('id')->first();
        if ($firstEvent) {
            $this->selectedEventId = $firstEvent->id;
        }
    }

    public function setEvent(int $eventId): void
    {
        $this->selectedEventId = $eventId;
        $this->activeTableId = null;
        $this->selectedTableForPosition = null;
        $this->selectedGuestToAssign = null;
    }

    public function setMode(string $mode): void
    {
        if (in_array($mode, ['plotting', 'position'], true)) {
            $this->mode = $mode;
            if ($mode === 'position') {
                $this->activeTableId = null;
            }
        }
    }

    public function setZoom(string $level): void
    {
        if (in_array($level, ['fit', '100', '125', '150', '200'], true)) {
            $this->zoomLevel = $level;
        }
    }

    public function selectTableForPosition(?int $tableId): void
    {
        $this->selectedTableForPosition = $tableId;
    }

    // Menyimpan koordinat persentase meja
    public function saveTablePosition(int $tableId, float $posX, float $posY): void
    {
        $table = Table::where('event_id', $this->selectedEventId)->find($tableId);
        if (! $table) {
            Notification::make()
                ->title('Meja tidak ditemukan')
                ->danger()
                ->send();

            return;
        }

        // Clamp persentase antara 0.00% dan 100.00%
        $clampedX = max(0.00, min(100.00, round($posX, 2)));
        $clampedY = max(0.00, min(100.00, round($posY, 2)));

        $table->update([
            'pos_x' => $clampedX,
            'pos_y' => $clampedY,
        ]);

        $this->selectedTableForPosition = null;

        Notification::make()
            ->title('Posisi Tersimpan')
            ->body("Meja {$table->name} berhasil dipetakan di ({$clampedX}%, {$clampedY}%).")
            ->success()
            ->send();
    }

    // Mengangkat pin meja dari denah (reset koordinat)
    public function resetTablePosition(int $tableId): void
    {
        $table = Table::where('event_id', $this->selectedEventId)->find($tableId);
        if ($table) {
            $table->update([
                'pos_x' => null,
                'pos_y' => null,
            ]);

            if ($this->selectedTableForPosition === $tableId) {
                $this->selectedTableForPosition = null;
            }

            Notification::make()
                ->title('Pin Dihapus dari Denah')
                ->body("Meja {$table->name} dikembalikan ke baki meja belum dipetakan.")
                ->info()
                ->send();
        }
    }

    // Membuka Slide-Over Drawer meja di mode plotting
    public function openTableDrawer(int $tableId): void
    {
        $this->activeTableId = $tableId;
        $this->selectedGuestToAssign = null;
    }

    // Menutup Slide-Over Drawer
    public function closeTableDrawer(): void
    {
        $this->activeTableId = null;
        $this->selectedGuestToAssign = null;
    }

    // Menempatkan tamu ke meja aktif
    public function assignGuest(): void
    {
        if (! $this->activeTableId || ! $this->selectedGuestToAssign) {
            Notification::make()
                ->title('Pilih Tamu')
                ->body('Silakan pilih tamu yang ingin ditempatkan di meja ini.')
                ->warning()
                ->send();

            return;
        }

        $table = Table::where('event_id', $this->selectedEventId)
            ->with(['guests.attendance'])
            ->find($this->activeTableId);

        $guest = Guest::where('event_id', $this->selectedEventId)->find($this->selectedGuestToAssign);

        if (! $table || ! $guest) {
            Notification::make()
                ->title('Data Tidak Valid')
                ->danger()
                ->send();

            return;
        }

        $neededSeats = ((int) $guest->adult_pax + (int) $guest->child_pax);
        if ($neededSeats <= 0) {
            $neededSeats = 1;
        }

        $remainingSeats = $table->getRemainingSeats();

        // Validasi over-capacity meja
        if ($neededSeats > $remainingSeats) {
            Notification::make()
                ->title('Kapasitas Meja Tidak Cukup!')
                ->body("Meja {$table->name} hanya memiliki sisa {$remainingSeats} kursi, sedangkan tamu {$guest->name} membutuhkan {$neededSeats} kursi.")
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        $guest->update([
            'table_id' => $table->id,
        ]);

        $this->selectedGuestToAssign = null;

        Notification::make()
            ->title('Tamu Berhasil Ditempatkan')
            ->body("{$guest->name} ({$neededSeats} pax) berhasil diplot ke meja {$table->name}.")
            ->success()
            ->send();
    }

    // Mengeluarkan tamu dari meja aktif
    public function unassignGuest(int $guestId): void
    {
        $guest = Guest::where('event_id', $this->selectedEventId)
            ->where('table_id', $this->activeTableId)
            ->find($guestId);

        if ($guest) {
            $guest->update(['table_id' => null]);

            Notification::make()
                ->title('Tamu Dikeluarkan')
                ->body("{$guest->name} berhasil dikeluarkan dari meja.")
                ->success()
                ->send();
        }
    }

    public function getViewData(): array
    {
        $activeEvents = Event::where('is_active', true)->orderBy('name')->get();
        $currentEvent = $this->selectedEventId ? Event::find($this->selectedEventId) : null;

        // Detail 1: WAJIB eager load guests.attendance dan guests.tenant untuk mencegah N+1 Query
        $tables = $this->selectedEventId
            ? Table::where('event_id', $this->selectedEventId)
                ->with(['guests.attendance', 'guests.tenant'])
                ->orderBy('name')
                ->get()
            : collect();

        $mappedTables = $tables->filter(fn (Table $table) => ! is_null($table->pos_x) && ! is_null($table->pos_y));
        $unmappedTables = $tables->filter(fn (Table $table) => is_null($table->pos_x) || is_null($table->pos_y));

        $activeTable = $this->activeTableId
            ? $tables->firstWhere('id', $this->activeTableId)
            : null;

        $unassignedGuests = $this->selectedEventId
            ? Guest::where('event_id', $this->selectedEventId)
                ->whereNull('table_id')
                ->with('tenant')
                ->orderBy('name')
                ->get()
            : collect();

        $tenants = $currentEvent
            ? $currentEvent->tenants()->orderBy('name')->get()
            : collect();

        $zones = $mappedTables
            ->pluck('zone')
            ->filter(fn ($z) => filled($z))
            ->unique()
            ->sort()
            ->values();

        $settings = Setting::get();

        return [
            'activeEvents' => $activeEvents,
            'currentEvent' => $currentEvent,
            'tables' => $tables,
            'mappedTables' => $mappedTables,
            'unmappedTables' => $unmappedTables,
            'activeTable' => $activeTable,
            'unassignedGuests' => $unassignedGuests,
            'tenants' => $tenants,
            'zones' => $zones,
            'settings' => $settings,
        ];
    }
}
