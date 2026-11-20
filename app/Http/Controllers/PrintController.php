<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PrintController extends Controller
{
    public function qrCodes(): View|RedirectResponse
    {
        $ids = session('print_qr_ids', []);

        if (empty($ids)) {
            return redirect('/')->with('error', 'Tidak ada data tamu yang dipilih untuk dicetak.');
        }

        $query = Guest::with(['event', 'tenant'])->whereIn('id', $ids);

        // Keamanan: Jika yang mencetak adalah tenant, batasi hanya tamu miliknya sendiri
        if (auth()->check() && auth()->user()->role === 'tenant') {
            $tenantId = auth()->user()->tenant_id ?? auth()->user()->tenant?->id;
            $query->where('tenant_id', $tenantId);
        }

        // Muat relasi event & tenant, lalu urutkan sesuai hierarki
        $guests = $query->get()
            ->sortBy([
                ['event.name', 'asc'],
                ['tenant.name', 'asc'],
                ['name', 'asc'],
            ]);

        if ($guests->isEmpty()) {
            return redirect('/')->with('error', 'Tidak ada data tamu yang dapat dicetak.');
        }

        return view('print-qr', compact('guests'));
    }
}
