<?php

namespace Tests\Feature;

use App\Filament\Pages\ChangePassword;
use App\Filament\Resources\GuestResource;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class GuestResourceTenantButtonsAndChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_can_see_download_qr_and_whatsapp_buttons_and_bulk_action(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $event = Event::create([
            'name' => 'Event Testing',
            'location' => 'Medan',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $tenant = Tenant::create(['name' => 'Tenant Alpha']);

        $guest = Guest::create([
            'event_id' => $event->id,
            'tenant_id' => $tenant->id,
            'name' => 'Tamu Admin',
            'phone' => '081234567890',
        ]);

        Setting::create([
            'app_name' => 'Guest Management',
            'wa_gateway_api_key' => 'dummy_key',
        ]);

        Livewire::actingAs($admin)
            ->test(GuestResource\Pages\ListGuests::class)
            ->assertTableActionVisible('download_qr', $guest)
            ->assertTableActionVisible('send_wa_me', $guest)
            ->assertTableActionVisible('send_wa_gateway', $guest)
            ->assertTableBulkActionVisible('broadcast_whatsapp');
    }

    public function test_tenant_cannot_see_download_qr_and_whatsapp_buttons_and_bulk_action(): void
    {
        $tenant = Tenant::create(['name' => 'Tenant Alpha']);
        $tenantUser = User::factory()->create([
            'role' => 'tenant',
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        $event = Event::create([
            'name' => 'Event Testing',
            'location' => 'Medan',
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $guest = Guest::create([
            'event_id' => $event->id,
            'tenant_id' => $tenant->id,
            'name' => 'Tamu Tenant',
            'phone' => '081234567890',
        ]);

        Setting::create([
            'app_name' => 'Guest Management',
            'wa_gateway_api_key' => 'dummy_key',
        ]);

        Livewire::actingAs($tenantUser)
            ->test(GuestResource\Pages\ListGuests::class)
            ->assertTableActionHidden('download_qr', $guest)
            ->assertTableActionHidden('send_wa_me', $guest)
            ->assertTableActionHidden('send_wa_gateway', $guest)
            ->assertTableBulkActionHidden('broadcast_whatsapp')
            ->assertTableActionVisible('edit', $guest)
            ->assertTableActionVisible('preview_qr', $guest);
    }

    public function test_user_can_change_password_via_filament_page(): void
    {
        $user = User::factory()->create([
            'role' => 'tenant',
            'password' => Hash::make('oldpassword123'),
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(ChangePassword::class)
            ->fillForm([
                'current_password' => 'oldpassword123',
                'new_password' => 'newpassword456',
                'new_password_confirmation' => 'newpassword456',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Kata Sandi Berhasil Diperbarui');

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword456', $user->password));
    }

    public function test_user_change_password_fails_if_current_password_incorrect(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make('correctpass123'),
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(ChangePassword::class)
            ->fillForm([
                'current_password' => 'wrongpass',
                'new_password' => 'newpassword456',
                'new_password_confirmation' => 'newpassword456',
            ])
            ->call('save')
            ->assertHasFormErrors(['current_password'])
            ->assertNotified('Gagal Mengubah Kata Sandi');

        $user->refresh();
        $this->assertTrue(Hash::check('correctpass123', $user->password));
    }

    public function test_petugas_can_change_password_via_scan_endpoint(): void
    {
        $petugas = User::factory()->create([
            'role' => 'register',
            'password' => Hash::make('petugaspass123'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($petugas)->postJson('/scan/change-password', [
            'current_password' => 'petugaspass123',
            'new_password' => 'petugasbaru456',
            'new_password_confirmation' => 'petugasbaru456',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Kata sandi Anda berhasil diperbarui!',
            ]);

        $petugas->refresh();
        $this->assertTrue(Hash::check('petugasbaru456', $petugas->password));
    }
}
