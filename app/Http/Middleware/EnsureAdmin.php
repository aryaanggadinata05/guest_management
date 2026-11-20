<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || auth()->user()->role !== 'admin') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses Ditolak. Fitur ini hanya dapat diakses oleh Administrator.',
                ], 403);
            }

            abort(403, 'Akses Ditolak. Fitur ini hanya dapat diakses oleh Administrator.');
        }

        return $next($request);
    }
}
