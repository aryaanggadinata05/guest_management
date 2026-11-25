<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OfficerEventAndWalkInTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_officer_with_assigned_events_only_sees_assigned_active_events(): void
    {
        $eventA = Event::create([
            'name' => 'Acara Alpha',
            'location' => 'Ballroom A',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        $eventB = Event::create([
            'name' => 'Acara Beta',
            'location' => 'Ballroom B',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $petugas = User::factory()->create(['role' => 'register', 'is_active' => true]);
        $petugas->assignedEvents()->attach($eventA->id);

        $accessible = $petugas->getAccessibleEvents();
        $this->assertCount(1, $accessible);
        $this->assertEquals($eventA->id, $accessible->first()->id);

        $response = $this->actingAs($petugas)->get('/scan');
        $response->assertOk();
        $response->assertSee('Acara Alpha');
        $response->assertDontSee('Acara Beta');
    }

    public function test_officer_without_assigned_events_falls_back_to_all_active_events(): void
    {
        $eventA = Event::create([
            'name' => 'Acara Alpha',
            'location' => 'Ballroom A',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        $eventB = Event::create([
            'name' => 'Acara Beta',
            'location' => 'Ballroom B',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $petugas = User::factory()->create(['role' => 'register', 'is_active' => true]);

        $accessible = $petugas->getAccessibleEvents();
        $this->assertCount(2, $accessible);
        $this->assertTrue($accessible->contains('id', $eventA->id));
        $this->assertTrue($accessible->contains('id', $eventB->id));
    }

    public function test_officer_receives_403_for_unassigned_event_on_scan_endpoints(): void
    {
        $eventA = Event::create([
            'name' => 'Acara Alpha',
            'location' => 'Ballroom A',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        $eventB = Event::create([
            'name' => 'Acara Beta',
            'location' => 'Ballroom B',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $tenant = Tenant::create(['name' => 'Tenant Beta']);
        $guestB = Guest::create([
            'event_id' => $eventB->id,
            'tenant_id' => $tenant->id,
            'name' => 'Tamu Beta',
            'adult_pax' => 1,
            'child_pax' => 0,
        ]);

        $petugas = User::factory()->create(['role' => 'register', 'is_active' => true]);
        $petugas->assignedEvents()->attach($eventA->id);

        $this->actingAs($petugas)->getJson("/scan/stats?event_id={$eventB->id}")->assertStatus(403);
        $this->actingAs($petugas)->postJson('/scan/verify', ['qr_code' => $guestB->qr_code])->assertStatus(403);
        $this->actingAs($petugas)->postJson('/scan/submit', [
            'guest_id' => $guestB->id,
            'name' => 'Tamu Beta',
            'adult_count' => 1,
            'child_count' => 0,
        ])->assertStatus(403);
        $this->actingAs($petugas)->postJson('/scan/search', [
            'keyword' => 'Tamu',
            'event_id' => $eventB->id,
        ])->assertStatus(403);
        $this->actingAs($petugas)->postJson('/scan/preferences', [
            'preferred_event_id' => $eventB->id,
        ])->assertStatus(403);
        $this->actingAs($petugas)->getJson("/scan/walk-in-options?event_id={$eventB->id}")->assertStatus(403);
        $this->actingAs($petugas)->postJson('/scan/walk-in', [
            'event_id' => $eventB->id,
            'name' => 'Walk-In Gelap',
            'adult_count' => 1,
            'child_count' => 0,
        ])->assertStatus(403);
    }

    public function test_changing_user_role_from_register_detaches_assigned_events(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $event = Event::create([
            'name' => 'Acara Alpha',
            'location' => 'Ballroom A',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $petugas = User::factory()->create(['role' => 'register', 'is_active' => true]);
        $petugas->assignedEvents()->attach($event->id);
        $this->assertEquals(1, $petugas->assignedEvents()->count());

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $petugas->getRouteKey()])
            ->fillForm([
                'role' => 'admin',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $petugas->refresh();
        $this->assertEquals('admin', $petugas->role);
        $this->assertEquals(0, $petugas->assignedEvents()->count());
    }

    public function test_walk_in_umum_creates_guest_and_attendance_and_assigns_table(): void
    {
        $event = Event::create([
            'name' => 'Gala Dinner 2026',
            'location' => 'Grand Ballroom',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        $table = Table::create([
            'event_id' => $event->id,
            'name' => 'Meja 01',
            'zone' => 'Regular',
            'capacity' => 10,
        ]);
        $petugas = User::factory()->create(['role' => 'register', 'is_active' => true]);

        $response = $this->actingAs($petugas)->postJson('/scan/walk-in', [
            'event_id' => $event->id,
            'name' => 'Andi Walk-In',
            'phone' => '081234567890',
            'tenant_id' => null,
            'adult_count' => 2,
            'child_count' => 1,
            'table_id' => $table->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'guest' => [
                    'name' => 'Andi Walk-In',
                    'tenant_name' => 'Tanpa Pengundang',
                    'table_name' => 'Meja 01',
                ],
                'stats' => [
                    'total' => 1,
                    'attended' => 1,
                    'adults' => 2,
                    'children' => 1,
                ],
            ]);

        $guest = Guest::where('name', 'Andi Walk-In')->first();
        $this->assertNotNull($guest);
        $this->assertNull($guest->tenant_id);
        $this->assertEquals($table->id, $guest->table_id);
        $this->assertTrue($guest->is_attended);
        $this->assertNotEmpty($guest->qr_code);

        $attendance = Attendance::where('guest_id', $guest->id)->first();
        $this->assertNotNull($attendance);
        $this->assertEquals($petugas->id, $attendance->user_id);
        $this->assertEquals(2, $attendance->adult_count);
        $this->assertEquals(1, $attendance->child_count);
    }

    public function test_walk_in_validates_table_capacity_and_tenant_quota(): void
    {
        $event = Event::create([
            'name' => 'Gala Dinner 2026',
            'location' => 'Grand Ballroom',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);
        $tenant = Tenant::create(['name' => 'Tenant Terbatas']);
        $event->tenants()->attach($tenant->id, ['quota' => 1]);

        Guest::create([
            'event_id' => $event->id,
            'tenant_id' => $tenant->id,
            'name' => 'Tamu Pertama',
            'adult_pax' => 1,
            'child_pax' => 0,
        ]);

        $smallTable = Table::create([
            'event_id' => $event->id,
            'name' => 'Meja Mini',
            'capacity' => 2,
        ]);

        $petugas = User::factory()->create(['role' => 'register', 'is_active' => true]);

        // 1. Uji penolakan kuota tenant penuh
        $quotaResp = $this->actingAs($petugas)->postJson('/scan/walk-in', [
            'event_id' => $event->id,
            'name' => 'Tamu Over Quota',
            'tenant_id' => $tenant->id,
            'adult_count' => 1,
            'child_count' => 0,
        ]);
        $quotaResp->assertStatus(422)->assertJson(['success' => false]);
        $this->assertDatabaseMissing('guests', ['name' => 'Tamu Over Quota']);

        // 2. Uji penolakan kapasitas meja tidak cukup
        $tableResp = $this->actingAs($petugas)->postJson('/scan/walk-in', [
            'event_id' => $event->id,
            'name' => 'Rombongan Besar',
            'tenant_id' => null,
            'adult_count' => 2,
            'child_count' => 1,
            'table_id' => $smallTable->id,
        ]);
        $tableResp->assertStatus(422)->assertJson(['success' => false]);
        $this->assertDatabaseMissing('guests', ['name' => 'Rombongan Besar']);
    }
}
