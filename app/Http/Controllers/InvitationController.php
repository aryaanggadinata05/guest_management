<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class InvitationController extends Controller
{
    /**
     * Tampilkan halaman kartu e-tiket publik untuk tamu
     */
    public function show(string $qr_code): View
    {
        $guest = Guest::with(['event', 'tenant', 'table'])
            ->where('qr_code', $qr_code)
            ->firstOrFail();

        $settings = Setting::get();

        $qrCodeSvg = QrCode::format('svg')
            ->size(240)
            ->margin(1)
            ->generate($guest->qr_code);

        return view('invitation.show', [
            'guest' => $guest,
            'settings' => $settings,
            'qrCodeSvg' => (string) $qrCodeSvg,
        ]);
    }
}
