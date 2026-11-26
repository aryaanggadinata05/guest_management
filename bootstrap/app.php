<?php

use App\Http\Middleware\AddOwnershipHeader;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->append(AddOwnershipHeader::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Penanganan terpusat error 403 & 404:
        // - Admin & Tenant dialihkan otomatis ke Home / Dashboard ('/')
        // - Petugas (Register) dialihkan otomatis ke Meja Registrasi ('/scan')
        // - Tamu belum login dialihkan ke Login ('/login')
        $exceptions->render(function (Throwable $e, Request $request) {
            // Jangan ganggu request yang berbentuk JSON/API atau Livewire
            if ($request->is('api/*') || $request->expectsJson() || $request->is('livewire/*')) {
                return null;
            }

            $is403 = false;
            $is404 = false;

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $is403 = ($status === 403);
                $is404 = ($status === 404);
            } elseif ($e instanceof AuthorizationException) {
                $is403 = true;
            }

            if (! $is403 && ! $is404) {
                return null;
            }

            // Tentukan target redirect berdasarkan status login dan role user
            if (auth()->check()) {
                $user = auth()->user();

                if (! $user->is_active) {
                    auth()->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect('/login')->with('error', 'Akun Anda dinonaktifkan.');
                }

                if ($user->role === 'register') {
                    // Cegah loop jika request sudah di /scan
                    if ($request->is('scan')) {
                        return null;
                    }

                    return redirect('/scan');
                }

                // User dengan peran 'admin' dan 'tenant' diarahkan ke dashboard ('/')
                // Cegah loop jika request sudah di home ('/')
                if ($request->is('/') || $request->path() === '' || $request->path() === '/') {
                    return null;
                }

                return redirect('/');
            }

            // Pengguna belum login
            if ($request->is('login') || $request->is('login-redirect')) {
                return null;
            }

            return redirect('/login');
        });
    })->create();
