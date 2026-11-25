<?php

use App\Filament\Pages\Auth\Login;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScanController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureScanAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Halaman Publik E-Tiket Tamu (Akses tanpa login)
Route::get('/invitation/{qr_code}', [InvitationController::class, 'show'])->name('invitation.show');

// Alias route login agar redirect()->route('login') mengarah ke Filament login
Route::name('login')->get('/login-redirect', function () {
    return redirect()->route('filament.admin.auth.login');
});

// 2. Rute Logout Khusus Petugas
Route::post('/logout-petugas', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->name('logout.petugas');

// Rute yang membutuhkan autentikasi
Route::middleware(['auth'])->group(function () {
    // Meja Registrasi (Hanya untuk Admin dan Petugas Register)
    Route::middleware([EnsureScanAccess::class])->group(function () {
        Route::get('/scan', [ScanController::class, 'index'])->name('scan.index');
        Route::get('/scan/stats', [ScanController::class, 'stats'])->name('scan.stats');
        Route::post('/scan/verify', [ScanController::class, 'verify'])->name('scan.verify');
        Route::post('/scan/submit', [ScanController::class, 'submit'])->name('scan.submit');
        Route::post('/scan/search', [ScanController::class, 'search'])->name('scan.search');
        Route::post('/scan/preferences', [ScanController::class, 'updatePreferences'])->name('scan.preferences');
        Route::post('/scan/change-password', [ScanController::class, 'changePassword'])->name('scan.change-password');
        Route::get('/scan/walk-in-options', [ScanController::class, 'walkInOptions'])->name('scan.walkin.options');
        Route::post('/scan/walk-in', [ScanController::class, 'walkIn'])->name('scan.walkin');
    });

    // Cetak QR Code
    Route::get('/print-qr', [PrintController::class, 'qrCodes'])->name('print.qr');

    // Laporan Eksekutif (Hanya untuk Admin)
    Route::get('/report/download/{event_id}', [ReportController::class, 'downloadExcel'])
        ->middleware([EnsureAdmin::class])
        ->name('report.download');
});
