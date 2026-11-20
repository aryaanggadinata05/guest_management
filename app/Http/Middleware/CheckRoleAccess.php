<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // Pastikan user sudah login
        if (auth()->check()) {
            if (! auth()->user()->is_active) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('/login')->with('error', 'Akun Anda dinonaktifkan. Silakan hubungi Administrator.');
            }

            $role = auth()->user()->role;

            // 1. Jika Petugas Scanner mencoba mengakses Filament (/admin atau URL apa pun di bawah panel)
            if ($role === 'register') {
                // Kecuali mereka memang sudah di halaman /scan, kita tendang ke /scan
                if (! $request->is('scan*') && ! $request->is('livewire*')) {
                    return redirect('/scan');
                }
            }

            // 2. Jika Tenant mencoba mengakses URL terlarang secara manual
            // Misalnya: /admin/users, /admin/events, /admin/tenants, /admin/tables
            if ($role === 'tenant') {
                $forbiddenPaths = [
                    'users*',
                    'events*',
                    'tenants*',
                    'manajemen-meja*',
                    'tables*',
                    'pengaturan-tampilan*',
                    'denah-meja*',
                ];

                foreach ($forbiddenPaths as $path) {
                    if ($request->is($path)) {
                        // Jika cocok dengan pola terlarang, kembalikan ke halaman awal dashboard
                        return redirect('/');
                    }
                }
            }
        }

        // Jika lolos semua pemeriksaan, lanjutkan request seperti biasa
        return $next($request);
    }
}
