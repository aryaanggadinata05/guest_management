<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityAndScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_scan(): void
    {
        $response = $this->get('/scan');
        $response->assertRedirect('/login-redirect');
    }

    public function test_tenant_cannot_access_scan(): void
    {
        $tenantUser = User::factory()->create(['role' => 'tenant', 'is_active' => true]);

        $response = $this->actingAs($tenantUser)->get('/scan');
        $response->assertRedirect('/');
    }

    public function test_register_role_can_access_scan(): void
    {
        $registerUser = User::factory()->create(['role' => 'register', 'is_active' => true]);

        $response = $this->actingAs($registerUser)->get('/scan');
        $response->assertStatus(200);
    }

    public function test_inactive_register_user_cannot_access_scan(): void
    {
        $inactiveRegisterUser = User::factory()->create(['role' => 'register', 'is_active' => false]);

        $response = $this->actingAs($inactiveRegisterUser)->get('/scan');
        $response->assertRedirect('/login');
    }

    public function test_inactive_user_cannot_access_filament_panel(): void
    {
        $inactiveAdmin = User::factory()->create(['role' => 'admin', 'is_active' => false]);

        $panel = Filament::getPanel('admin');
        $this->assertFalse($inactiveAdmin->canAccessPanel($panel));
    }

    public function test_inactive_user_cannot_authenticate_via_login(): void
    {
        $inactiveUser = User::factory()->create([
            'username' => 'inactiveuser',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'is_active' => false,
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'username' => 'inactiveuser',
                'password' => 'password123',
            ])
            ->call('authenticate')
            ->assertHasErrors(['data.username']);

        $this->assertGuest();
    }

    public function test_invalid_credentials_shows_error_message_and_notification(): void
    {
        Livewire::test(Login::class)
            ->fillForm([
                'username' => 'nonexistentuser',
                'password' => 'wrongpassword',
            ])
            ->call('authenticate')
            ->assertHasErrors(['data.username' => 'Username atau kata sandi yang Anda masukkan salah.'])
            ->assertNotified();

        $this->assertGuest();
    }

    public function test_tenant_cannot_download_executive_report(): void
    {
        $tenantUser = User::factory()->create(['role' => 'tenant', 'is_active' => true]);

        $response = $this->actingAs($tenantUser)->get('/report/download/1');
        $response->assertRedirect('/');
    }

    public function test_admin_redirected_to_home_on_404(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($admin)->get('/halaman-acak-tidak-ada-123');
        $response->assertRedirect('/');
    }

    public function test_tenant_redirected_to_home_on_404(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant', 'is_active' => true]);

        $response = $this->actingAs($tenant)->get('/halaman-acak-tidak-ada-123');
        $response->assertRedirect('/');
    }

    public function test_register_role_redirected_to_scan_on_404(): void
    {
        $register = User::factory()->create(['role' => 'register', 'is_active' => true]);

        $response = $this->actingAs($register)->get('/halaman-acak-tidak-ada-123');
        $response->assertRedirect('/scan');
    }

    public function test_register_role_redirected_to_scan_on_403(): void
    {
        $register = User::factory()->create(['role' => 'register', 'is_active' => true]);

        // Route report.download hanya untuk admin via EnsureAdmin
        $response = $this->actingAs($register)->get('/report/download/1');
        $response->assertRedirect('/scan');
    }

    public function test_guest_redirected_to_login_on_404(): void
    {
        $response = $this->get('/halaman-acak-tidak-ada-123');
        $response->assertRedirect('/login');
    }

    public function test_tenant_can_only_print_their_own_guests(): void
    {
        $tenantA = Tenant::create(['name' => 'Tenant A']);
        $tenantB = Tenant::create(['name' => 'Tenant B']);

        $tenantUserA = User::factory()->create(['role' => 'tenant', 'tenant_id' => $tenantA->id, 'is_active' => true]);

        $event = Event::create([
            'name' => 'Event Testing',
            'location' => 'Medan',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $guestA = Guest::create([
            'tenant_id' => $tenantA->id,
            'event_id' => $event->id,
            'name' => 'Tamu Tenant A',
        ]);

        $guestB = Guest::create([
            'tenant_id' => $tenantB->id,
            'event_id' => $event->id,
            'name' => 'Tamu Tenant B',
        ]);

        // Tenant A tries to print both Guest A and Guest B
        $response = $this->actingAs($tenantUserA)
            ->withSession(['print_qr_ids' => [$guestA->id, $guestB->id]])
            ->get('/print-qr');

        $response->assertStatus(200);
        $response->assertSee('Tamu Tenant A');
        $response->assertDontSee('Tamu Tenant B');
    }
}
