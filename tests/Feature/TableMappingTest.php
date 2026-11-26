<?php

namespace Tests\Feature;

use App\Filament\Pages\TableMapping;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TableMappingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_can_access_table_mapping_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($admin)->get('/denah-meja');

        $response->assertOk();
    }

    public function test_tenant_cannot_access_table_mapping_page(): void
    {
        $tenantUser = User::factory()->create(['role' => 'tenant', 'is_active' => true]);

        $response = $this->actingAs($tenantUser)->get('/denah-meja');

        $response->assertRedirect('/');
    }

    public function test_register_petugas_cannot_access_table_mapping_page(): void
    {
        $petugas = User::factory()->create(['role' => 'register', 'is_active' => true]);

        $response = $this->actingAs($petugas)->get('/denah-meja');

        $response->assertRedirect('/scan');
    }

    public function test_admin_can_save_table_position(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $event = Event::create([
            'name' => 'Event Tech 2026',
            'location' => 'Jakarta',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        $table = Table::create([
            'event_id' => $event->id,
            'name' => 'Meja VVIP 01',
            'capacity' => 10,
        ]);

        Livewire::actingAs($admin)
            ->test(TableMapping::class)
            ->set('selectedEventId', $event->id)
            ->call('saveTablePosition', $table->id, 45.67, 78.12)
            ->assertNotified('Posisi Tersimpan');

        $table->refresh();
        $this->assertEquals(45.67, $table->pos_x);
        $this->assertEquals(78.12, $table->pos_y);
    }

    public function test_admin_can_reset_table_position(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $event = Event::create([
            'name' => 'Event Tech 2026',
            'location' => 'Jakarta',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        $table = Table::create([
            'event_id' => $event->id,
            'name' => 'Meja VVIP 02',
            'capacity' => 10,
            'pos_x' => 50.00,
            'pos_y' => 50.00,
        ]);

        Livewire::actingAs($admin)
            ->test(TableMapping::class)
            ->set('selectedEventId', $event->id)
            ->call('resetTablePosition', $table->id)
            ->assertNotified('Pin Dihapus dari Denah');

        $table->refresh();
        $this->assertNull($table->pos_x);
        $this->assertNull($table->pos_y);
    }

    public function test_admin_can_assign_guest_to_table_within_capacity(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $event = Event::create([
            'name' => 'Event Tech 2026',
            'location' => 'Jakarta',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        $tenant = Tenant::create(['name' => 'Tenant Beta']);
        $table = Table::create([
            'event_id' => $event->id,
            'name' => 'Meja 01',
            'capacity' => 5,
        ]);
        $guest = Guest::create([
            'event_id' => $event->id,
            'tenant_id' => $tenant->id,
            'name' => 'Budi Santoso',
            'adult_pax' => 2,
            'child_pax' => 1,
            'table_id' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(TableMapping::class)
            ->set('selectedEventId', $event->id)
            ->set('activeTableId', $table->id)
            ->set('selectedGuestToAssign', $guest->id)
            ->call('assignGuest')
            ->assertNotified('Tamu Berhasil Ditempatkan');

        $guest->refresh();
        $this->assertEquals($table->id, $guest->table_id);
        $this->assertEquals(3, $table->getOccupiedSeats());
        $this->assertEquals(2, $table->getRemainingSeats());
    }

    public function test_admin_cannot_assign_guest_if_exceeds_capacity(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $event = Event::create([
            'name' => 'Event Tech 2026',
            'location' => 'Jakarta',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        $tenant = Tenant::create(['name' => 'Tenant Beta']);
        $table = Table::create([
            'event_id' => $event->id,
            'name' => 'Meja Kecil',
            'capacity' => 2,
        ]);
        $guest = Guest::create([
            'event_id' => $event->id,
            'tenant_id' => $tenant->id,
            'name' => 'Keluarga Besar',
            'adult_pax' => 2,
            'child_pax' => 2, // Total 4 pax, exceeding capacity 2
            'table_id' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(TableMapping::class)
            ->set('selectedEventId', $event->id)
            ->set('activeTableId', $table->id)
            ->set('selectedGuestToAssign', $guest->id)
            ->call('assignGuest')
            ->assertNotified('Kapasitas Meja Tidak Cukup!');

        $guest->refresh();
        $this->assertNull($guest->table_id);
    }

    public function test_admin_can_unassign_guest_from_table(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $event = Event::create([
            'name' => 'Event Tech 2026',
            'location' => 'Jakarta',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        $tenant = Tenant::create(['name' => 'Tenant Beta']);
        $table = Table::create([
            'event_id' => $event->id,
            'name' => 'Meja 05',
            'capacity' => 8,
        ]);
        $guest = Guest::create([
            'event_id' => $event->id,
            'tenant_id' => $tenant->id,
            'name' => 'Rina Wijaya',
            'adult_pax' => 1,
            'child_pax' => 0,
            'table_id' => $table->id,
        ]);

        Livewire::actingAs($admin)
            ->test(TableMapping::class)
            ->set('selectedEventId', $event->id)
            ->set('activeTableId', $table->id)
            ->call('unassignGuest', $guest->id)
            ->assertNotified('Tamu Dikeluarkan');

        $guest->refresh();
        $this->assertNull($guest->table_id);
    }

    public function test_event_get_floor_plan_url_returns_relative_path_only_when_file_exists(): void
    {
        Storage::fake('public');

        $eventMissing = Event::create([
            'name' => 'Event Missing File',
            'location' => 'Jakarta',
            'start_date' => now()->toDateString(),
            'is_active' => true,
            'floor_plan_image' => 'floor-plans/nonexistent.png',
        ]);

        $this->assertNull($eventMissing->getFloorPlanUrl());

        Storage::disk('public')->put('floor-plans/valid-plan.png', 'dummy-image-bytes');

        $eventValid = Event::create([
            'name' => 'Event Valid File',
            'location' => 'Jakarta',
            'start_date' => now()->toDateString(),
            'is_active' => true,
            'floor_plan_image' => 'floor-plans/valid-plan.png',
        ]);

        $this->assertEquals('/storage/floor-plans/valid-plan.png', $eventValid->getFloorPlanUrl());
    }
}
