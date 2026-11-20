<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureScanAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! auth()->user()->is_active || ! in_array(auth()->user()->role, ['admin', 'register'], true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses Ditolak. Akun tidak aktif atau tidak memiliki izin untuk mengakses meja registrasi.',
                ], 403);
            }

            abort(403, 'Akses Ditolak. Akun Anda dinonaktifkan atau tidak memiliki izin untuk meja registrasi.');
        }

        return $next($request);
    }
}
