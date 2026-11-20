<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSettings;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Setting;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class WhatsAppInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_number_formatting_in_whatsapp_service(): void
    {
        $this->assertEquals('628123456789', WhatsAppService::formatPhoneNumber('08123456789'));
        $this->assertEquals('628123456789', WhatsAppService::formatPhoneNumber('+62 812-3456-789'));
        $this->assertEquals('628123456789', WhatsAppService::formatPhoneNumber('0812-3456-789'));
        $this->assertEquals('6285211122233', WhatsAppService::formatPhoneNumber('85211122233'));
        $this->assertNull(WhatsAppService::formatPhoneNumber(''));
        $this->assertNull(WhatsAppService::formatPhoneNumber('123'));
    }

    public function test_public_invitation_page_renders_guest_data_and_qr_code(): void
    {
        $tenant = Tenant::create(['name' => 'Keluarga Mempelai']);
        $event = Event::create([
            'name' => 'Pernikahan Budi & Ani',
            'location' => 'Grand Ballroom Medan',
            'start_date' => '2026-12-31',
            'is_active' => true,
        ]);
        $table = Table::create([
            'event_id' => $event->id,
            'name' => 'VIP-01',
            'capacity' => 10,
        ]);

        $guest = Guest::create([
            'tenant_id' => $tenant->id,
            'event_id' => $event->id,
            'table_id' => $table->id,
            'name' => 'Bapak John Doe',
            'phone' => '081234567890',
            'adult_pax' => 2,
            'child_pax' => 1,
        ]);

        $response = $this->get(route('invitation.show', ['qr_code' => $guest->qr_code]));

        $response->assertStatus(200);
        $response->assertSee('Bapak John Doe');
        $response->assertSee('Pernikahan Budi & Ani');
        $response->assertSee('Grand Ballroom Medan');
        $response->assertSee('VIP-01');
        $response->assertSee('3 Orang'); // total pax 2+1
        $response->assertSee('Tiket Registrasi Masuk');
        $response->assertSee('<svg', false); // SVG QR Code
    }

    public function test_whatsapp_service_builds_dynamic_message(): void
    {
        $tenant = Tenant::create(['name' => 'Corporate Partner']);
        $event = Event::create([
            'name' => 'Gala Dinner 2026',
            'location' => 'JW Marriott',
            'start_date' => '2026-11-20',
            'is_active' => true,
        ]);

        $guest = Guest::create([
            'tenant_id' => $tenant->id,
            'event_id' => $event->id,
            'name' => 'Ibu Siti Aminah',
            'phone' => '085299887766',
            'adult_pax' => 2,
            'child_pax' => 0,
        ]);

        $message = WhatsAppService::buildMessage($guest);

        $this->assertStringContainsString('Ibu Siti Aminah', $message);
        $this->assertStringContainsString('Gala Dinner 2026', $message);
        $this->assertStringContainsString(route('invitation.show', ['qr_code' => $guest->qr_code]), $message);
    }

    public function test_whatsapp_service_generates_wa_me_url(): void
    {
        $tenant = Tenant::create(['name' => 'Mitra']);
        $event = Event::create([
            'name' => 'Annual Meeting',
            'location' => 'Hotel Tiara',
            'start_date' => '2026-11-25',
            'is_active' => true,
        ]);

        $guest = Guest::create([
            'tenant_id' => $tenant->id,
            'event_id' => $event->id,
            'name' => 'Dr. Hendra',
            'phone' => '081377889900',
        ]);

        $waMeUrl = WhatsAppService::generateWaMeUrl($guest);

        $this->assertNotNull($waMeUrl);
        $this->assertStringStartsWith('https://wa.me/6281377889900?text=', $waMeUrl);
        $this->assertStringContainsString(rawurlencode('Dr. Hendra'), $waMeUrl);
    }

    public function test_admin_can_save_whatsapp_settings_in_manage_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin);

        Livewire::test(ManageSettings::class)
            ->fillForm([
                'wa_default_method' => 'gateway',
                'wa_message_template' => 'Yth. {nama}, tiket acara {acara} Anda: {link_tiket}',
                'wa_gateway_provider' => 'fonnte',
                'wa_gateway_api_key' => 'secret_fonnte_token_123',
                'wa_gateway_sender' => '6281122334455',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $saved = Setting::get();
        $this->assertEquals('gateway', $saved->wa_default_method);
        $this->assertEquals('Yth. {nama}, tiket acara {acara} Anda: {link_tiket}', $saved->wa_message_template);
        $this->assertEquals('fonnte', $saved->wa_gateway_provider);
        $this->assertEquals('secret_fonnte_token_123', $saved->wa_gateway_api_key);
        $this->assertEquals('6281122334455', $saved->wa_gateway_sender);
    }

    public function test_whatsapp_gateway_send_via_http_client(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true, 'message' => 'Message queued'], 200),
        ]);

        $setting = Setting::first() ?? new Setting;
        $setting->wa_gateway_provider = 'fonnte';
        $setting->wa_gateway_api_key = 'dummy_token_123';
        $setting->save();

        $tenant = Tenant::create(['name' => 'Penyelenggara']);
        $event = Event::create([
            'name' => 'Seminar Bisnis',
            'location' => 'Jakarta Hall',
            'start_date' => '2026-11-20',
            'is_active' => true,
        ]);

        $guest = Guest::create([
            'tenant_id' => $tenant->id,
            'event_id' => $event->id,
            'name' => 'Pak Rudi',
            'phone' => '081299881122',
        ]);

        $this->assertNull($guest->wa_sent_at);

        $result = WhatsAppService::sendViaGateway($guest);

        $this->assertTrue($result['success']);
        $this->assertNotNull($guest->fresh()->wa_sent_at);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.fonnte.com/send' &&
                $request->hasHeader('Authorization', 'dummy_token_123');
        });
    }
}
