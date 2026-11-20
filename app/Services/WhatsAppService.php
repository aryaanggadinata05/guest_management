<?php

namespace App\Services;

use App\Models\Guest;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Normalisasi nomor telepon ke format internasional (misal: 628123456789)
     */
    public static function formatPhoneNumber(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        // Hapus semua karakter non-angka
        $cleaned = preg_replace('/[^0-9]/', '', (string) $phone);

        if (blank($cleaned) || strlen($cleaned) < 8) {
            return null;
        }

        // Ubah awalan 0 menjadi 62
        if (str_starts_with($cleaned, '0')) {
            $cleaned = '62'.substr($cleaned, 1);
        } elseif (str_starts_with($cleaned, '8')) {
            // Jika diawali 8..., tambahkan 62
            $cleaned = '62'.$cleaned;
        }

        return $cleaned;
    }

    /**
     * Susun teks pesan dari template dinamis berdasarkan data tamu
     */
    public static function buildMessage(Guest $guest, ?string $customTemplate = null): string
    {
        $settings = Setting::get();
        $template = $customTemplate ?: ($settings->wa_message_template ?: Setting::getDefaultWaTemplate());

        $tableName = 'Sesuai Arahan Panitia';
        if ($guest->table) {
            $tableName = $guest->table->name;
        }

        $totalPax = ($guest->adult_pax ?? 0) + ($guest->child_pax ?? 0);
        $ticketUrl = route('invitation.show', ['qr_code' => $guest->qr_code]);

        $replacements = [
            '{nama}' => $guest->name,
            '{acara}' => $guest->event?->name ?? 'Acara',
            '{lokasi}' => $guest->event?->location ?? 'Lokasi Acara',
            '{tanggal}' => $guest->event?->start_date ? date('d F Y', strtotime($guest->event->start_date)) : '',
            '{meja}' => $tableName,
            '{pax}' => (string) ($totalPax ?: 1),
            '{dewasa}' => (string) ($guest->adult_pax ?? 0),
            '{anak}' => (string) ($guest->child_pax ?? 0),
            '{link_tiket}' => $ticketUrl,
            '{qr_code}' => $guest->qr_code,
            '{pengundang}' => $guest->tenant?->name ?? 'Panitia',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Hasilkan URL wa.me untuk pengiriman langsung
     */
    public static function generateWaMeUrl(Guest $guest, ?string $customMessage = null): ?string
    {
        $phone = self::formatPhoneNumber($guest->phone);

        if (! $phone) {
            return null;
        }

        $message = $customMessage ?: self::buildMessage($guest);

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }

    /**
     * Kirim pesan ke tamu melalui WhatsApp Gateway API (Fase 2)
     *
     * @return array{success: bool, message: string}
     */
    public static function sendViaGateway(Guest $guest, ?string $customMessage = null): array
    {
        $phone = self::formatPhoneNumber($guest->phone);

        if (! $phone) {
            return [
                'success' => false,
                'message' => "Nomor telepon untuk tamu '{$guest->name}' tidak valid atau kosong.",
            ];
        }

        $settings = Setting::get();

        if (blank($settings->wa_gateway_api_key)) {
            return [
                'success' => false,
                'message' => 'API Key / Token WhatsApp Gateway belum diisi di Pengaturan Tampilan & Sistem.',
            ];
        }

        $message = $customMessage ?: self::buildMessage($guest);
        $provider = $settings->wa_gateway_provider ?: 'fonnte';

        try {
            $response = match ($provider) {
                'fonnte' => Http::timeout(15)
                    ->withHeaders(['Authorization' => $settings->wa_gateway_api_key])
                    ->asForm()
                    ->post('https://api.fonnte.com/send', [
                        'target' => $phone,
                        'message' => $message,
                        'countryCode' => '62',
                    ]),

                'wablas' => Http::timeout(15)
                    ->withHeaders(['Authorization' => $settings->wa_gateway_api_key])
                    ->asForm()
                    ->post(rtrim($settings->wa_gateway_endpoint ?: 'https://phone.wablas.com', '/').'/api/send-message', [
                        'phone' => $phone,
                        'message' => $message,
                    ]),

                default => Http::timeout(15)
                    ->withHeaders([
                        'Authorization' => $settings->wa_gateway_api_key,
                        'Accept' => 'application/json',
                    ])
                    ->post($settings->wa_gateway_endpoint ?: 'https://api.fonnte.com/send', [
                        'target' => $phone,
                        'phone' => $phone,
                        'message' => $message,
                    ]),
            };

            if ($response->successful()) {
                $guest->update(['wa_sent_at' => now()]);

                return [
                    'success' => true,
                    'message' => "Undangan berhasil dikirim via WhatsApp Gateway ke {$guest->name}.",
                ];
            }

            Log::warning('WhatsApp Gateway Error Response', [
                'provider' => $provider,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'Gateway menolak pesan: '.($response->json('reason') ?? $response->json('message') ?? $response->body()),
            ];
        } catch (\Throwable $e) {
            Log::error('WhatsApp Gateway Exception', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Gagal terhubung ke server WhatsApp Gateway: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Kirim pesan uji coba ke nomor tujuan untuk memeriksa koneksi gateway
     *
     * @return array{success: bool, message: string}
     */
    public static function sendTestMessage(string $targetPhone, ?string $customMessage = null): array
    {
        $phone = self::formatPhoneNumber($targetPhone);

        if (! $phone) {
            return [
                'success' => false,
                'message' => 'Nomor tujuan pengujian tidak valid.',
            ];
        }

        $settings = Setting::get();

        if (blank($settings->wa_gateway_api_key)) {
            return [
                'success' => false,
                'message' => 'API Key Gateway belum diisi.',
            ];
        }

        $message = $customMessage ?: "🔔 *Pesan Pengujian Sistem Guest Management*\n\nKoneksi WhatsApp Gateway berhasil terhubung dengan baik! Sistem siap melakukan pengiriman undangan.\n\nWaktu: ".now()->translatedFormat('d F Y, H:i:s');
        $provider = $settings->wa_gateway_provider ?: 'fonnte';

        try {
            $response = match ($provider) {
                'fonnte' => Http::timeout(15)
                    ->withHeaders(['Authorization' => $settings->wa_gateway_api_key])
                    ->asForm()
                    ->post('https://api.fonnte.com/send', [
                        'target' => $phone,
                        'message' => $message,
                        'countryCode' => '62',
                    ]),

                'wablas' => Http::timeout(15)
                    ->withHeaders(['Authorization' => $settings->wa_gateway_api_key])
                    ->asForm()
                    ->post(rtrim($settings->wa_gateway_endpoint ?: 'https://phone.wablas.com', '/').'/api/send-message', [
                        'phone' => $phone,
                        'message' => $message,
                    ]),

                default => Http::timeout(15)
                    ->withHeaders([
                        'Authorization' => $settings->wa_gateway_api_key,
                        'Accept' => 'application/json',
                    ])
                    ->post($settings->wa_gateway_endpoint ?: 'https://api.fonnte.com/send', [
                        'target' => $phone,
                        'phone' => $phone,
                        'message' => $message,
                    ]),
            };

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => "Pesan uji coba berhasil terkirim ke {$phone}.",
                ];
            }

            return [
                'success' => false,
                'message' => 'Respons error gateway ('.$response->status().'): '.($response->json('reason') ?? $response->json('message') ?? $response->body()),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Koneksi gagal: '.$e->getMessage(),
            ];
        }
    }
}
