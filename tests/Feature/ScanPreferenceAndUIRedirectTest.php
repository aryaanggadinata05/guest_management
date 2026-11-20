<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanPreferenceAndUIRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_scan_preferences_via_ajax(): void
    {
        $event = Event::create([
            'name' => 'Acara Launching Produk',
            'location' => 'Grand Ballroom',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'register',
            'is_active' => true,
            'preferred_scan_mode' => 'camera',
            'preferred_event_id' => null,
        ]);

        $response = $this->actingAs($user)->postJson('/scan/preferences', [
            'preferred_scan_mode' => 'manual',
            'preferred_event_id' => $event->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'preferred_scan_mode' => 'manual',
                'preferred_event_id' => $event->id,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'preferred_scan_mode' => 'manual',
            'preferred_event_id' => $event->id,
        ]);
    }

    public function test_scan_page_preselects_user_preferences(): void
    {
        $eventA = Event::create([
            'name' => 'Event A',
            'location' => 'Jakarta',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $eventB = Event::create([
            'name' => 'Event B',
            'location' => 'Surabaya',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'register',
            'is_active' => true,
            'preferred_scan_mode' => 'manual',
            'preferred_event_id' => $eventB->id,
        ]);

        $response = $this->actingAs($user)->get('/scan');

        $response->assertOk();
        $response->assertViewHas('activeEvent', function ($event) use ($eventB) {
            return $event->id === $eventB->id;
        });
        $response->assertViewHas('preferredMode', 'manual');
        $response->assertSee("currentMode = 'manual'", false);
    }

    public function test_admin_sees_kembali_ke_panel_admin_button(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/scan');

        $response->assertOk();
        $response->assertSee('Panel Admin');
        $response->assertSee('href="/"', false);
    }

    public function test_register_role_does_not_see_kembali_ke_panel_admin_button(): void
    {
        $register = User::factory()->create([
            'role' => 'register',
            'is_active' => true,
        ]);

        $response = $this->actingAs($register)->get('/scan');

        $response->assertOk();
        $response->assertDontSee('Panel Admin');
        $response->assertDontSee('href="/"', false);
    }

    public function test_scan_stats_returns_recent_attendances(): void
    {
        $event = Event::create([
            'name' => 'Gala Dinner 2026',
            'location' => 'Ballroom A',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $tenant = Tenant::create(['name' => 'PT Maju Bersama']);
        $table = Table::create([
            'event_id' => $event->id,
            'name' => 'VIP 01',
            'capacity' => 10,
        ]);

        $guest = Guest::create([
            'tenant_id' => $tenant->id,
            'event_id' => $event->id,
            'table_id' => $table->id,
            'name' => 'Budi Santoso',
            'is_attended' => true,
            'adult_pax' => 2,
            'child_pax' => 1,
        ]);

        $user = User::factory()->create(['role' => 'register', 'is_active' => true]);

        Attendance::create([
            'guest_id' => $guest->id,
            'user_id' => $user->id,
            'adult_count' => 2,
            'child_count' => 1,
            'scanned_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/scan/stats?event_id='.$event->id);

        $response->assertOk()
            ->assertJsonStructure([
                'total',
                'attended',
                'unattended',
                'adults',
                'children',
                'recent' => [
                    '*' => [
                        'id',
                        'guest_name',
                        'tenant_name',
                        'table_name',
                        'adult_count',
                        'child_count',
                        'scanned_time',
                    ],
                ],
            ]);

        $data = $response->json();
        $this->assertCount(1, $data['recent']);
        $this->assertEquals('Budi Santoso', $data['recent'][0]['guest_name']);
        $this->assertEquals('PT Maju Bersama', $data['recent'][0]['tenant_name']);
        $this->assertEquals('VIP 01', $data['recent'][0]['table_name']);
        $this->assertEquals(2, $data['recent'][0]['adult_count']);
        $this->assertEquals(1, $data['recent'][0]['child_count']);
    }
}
