<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_name',
        'login_subtitle',
        'logo_path',
        'favicon_path',
        'primary_color',
        'secondary_color',
        'login_bg_type',
        'login_bg_image',
        'login_overlay_opacity',
        'wa_default_method',
        'wa_message_template',
        'wa_gateway_provider',
        'wa_gateway_api_key',
        'wa_gateway_sender',
        'wa_gateway_endpoint',
    ];

    protected $casts = [
        'login_overlay_opacity' => 'integer',
    ];

    /**
     * Ambil pengaturan saat ini langsung dari database dengan fallback aman
     */
    public static function get(): self
    {
        try {
            if (! Schema::hasTable('settings')) {
                return self::defaultInstance();
            }

            return self::query()->first() ?? self::defaultInstance();
        } catch (\Throwable) {
            return self::defaultInstance();
        }
    }

    /**
     * URL Logo Resmi Aplikasi
     */
    public function getLogoUrl(): ?string
    {
        if (! filled($this->logo_path)) {
            return null;
        }

        $path = ltrim((string) $this->logo_path, '/');
        $path = preg_replace('#^storage/#', '', $path);

        return asset('storage/'.$path);
    }

    /**
     * URL Favicon Tab Browser
     */
    public function getFaviconUrl(): ?string
    {
        if (! filled($this->favicon_path)) {
            return null;
        }

        $path = ltrim((string) $this->favicon_path, '/');
        $path = preg_replace('#^storage/#', '', $path);

        return asset('storage/'.$path);
    }

    /**
     * URL Wallpaper Layar Login
     */
    public function getLoginBgUrl(): ?string
    {
        if (! filled($this->login_bg_image)) {
            return null;
        }

        $path = ltrim((string) $this->login_bg_image, '/');
        $path = preg_replace('#^storage/#', '', $path);

        return asset('storage/'.$path);
    }

    /**
     * Template pesan default WhatsApp
     */
    public static function getDefaultWaTemplate(): string
    {
        return "Halo {nama},\n\nAnda diundang menghadiri acara {acara}.\nMeja: {meja} ({pax} Pax)\n\nSilakan tunjukkan QR Code tiket Anda saat tiba melalui tautan berikut:\n{link_tiket}\n\nTerima kasih!";
    }

    /**
     * Instance bawaan saat database kosong atau belum dimigrasi
     */
    public static function defaultInstance(): self
    {
        return new self([
            'app_name' => 'Guest Management',
            'login_subtitle' => 'Sistem Registrasi & Manajemen Tamu Acara',
            'logo_path' => null,
            'favicon_path' => null,
            'primary_color' => '#2f76b5',
            'secondary_color' => '#1e4e79',
            'login_bg_type' => 'gradient',
            'login_bg_image' => null,
            'login_overlay_opacity' => 45,
            'wa_default_method' => 'wa_me',
            'wa_message_template' => self::getDefaultWaTemplate(),
            'wa_gateway_provider' => 'fonnte',
            'wa_gateway_api_key' => null,
            'wa_gateway_sender' => null,
            'wa_gateway_endpoint' => null,
        ]);
    }

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('app_settings');
        });

        static::deleted(function () {
            Cache::forget('app_settings');
        });
    }
}
