<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\ManageSettings;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsAndBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_settings_fallback_returns_expected_values(): void
    {
        $settings = Setting::get();

        $this->assertEquals('Guest Management', $settings->app_name);
        $this->assertEquals('#2f76b5', $settings->primary_color);
        $this->assertEquals('#1e4e79', $settings->secondary_color);
        $this->assertEquals('gradient', $settings->login_bg_type);
        $this->assertEquals(45, $settings->login_overlay_opacity);
    }

    public function test_admin_can_access_manage_settings_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($admin)->get('/pengaturan-tampilan');
        $response->assertStatus(200);
        $response->assertSee('Pengaturan Tampilan');
        $response->assertSee('Identitas & Logo');
        $response->assertSee('Tema & Warna');
        $response->assertSee('Halaman Login');
    }

    public function test_tenant_cannot_access_manage_settings_page(): void
    {
        $tenant = User::factory()->create(['role' => 'tenant', 'is_active' => true]);

        $response = $this->actingAs($tenant)->get('/pengaturan-tampilan');
        // CheckRoleAccess middleware redirects forbidden paths to '/'
        $response->assertRedirect('/');
    }

    public function test_admin_can_save_settings_and_updates_cache(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin);

        Livewire::test(ManageSettings::class)
            ->fillForm([
                'app_name' => 'VIP Event Portal',
                'login_subtitle' => 'Eksklusif Registrasi',
                'primary_color' => '#059669',
                'secondary_color' => '#065f46',
                'login_bg_type' => 'gradient',
                'login_overlay_opacity' => 50,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $updated = Setting::get();
        $this->assertEquals('VIP Event Portal', $updated->app_name);
        $this->assertEquals('#059669', $updated->primary_color);
        $this->assertEquals('#065f46', $updated->secondary_color);
    }

    public function test_login_page_renders_with_brand_name_and_custom_styling(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Guest Management');
        $response->assertSee('Sistem Registrasi & Manajemen Tamu Acara');
        $response->assertSee('Nama Pengguna (Username)');
        $response->assertSee('Kata Sandi');
        $response->assertSee('Masuk ke Sistem');
        $response->assertSee('--brand-primary: #2f76b5', false);
        $response->assertSee('--brand-secondary: #1e4e79', false);
    }

    public function test_register_role_login_redirects_to_scan(): void
    {
        User::factory()->create([
            'username' => 'petugaspintu',
            'password' => bcrypt('secret123'),
            'role' => 'register',
            'is_active' => true,
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'username' => 'petugaspintu',
                'password' => 'secret123',
            ])
            ->call('authenticate')
            ->assertRedirect(url('/scan'));
    }

    public function test_admin_role_login_succeeds_and_accesses_dashboard(): void
    {
        User::factory()->create([
            'username' => 'superadmin',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'username' => 'superadmin',
                'password' => 'secret123',
            ])
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticated();
    }
}
