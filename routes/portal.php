<?php

use App\Http\Controllers\Portal\KeuanganController as PortalKeuanganController;
use App\Http\Controllers\SantriController;
use Illuminate\Support\Facades\Route;

/* ======================================
 * PORTAL EKSTERNAL (Wali / Santri)
 *
 * Dimuat langsung dari web.php, di luar gerbang modul, karena
 * portal harus tetap hidup meski salah satu modul dimatikan.
 * Isi menunya yang menyesuaikan, bukan seluruh portalnya.
 * ====================================== */

Route::middleware([
    'auth',
    'role:' . implode(',', config('modules.role_eksternal')),
])->prefix('santri')->name('santri.')->group(function () {

    Route::get('/dashboard', [SantriController::class, 'dashboard'])->name('dashboard');

    /* ---------- Bagian Raport ---------- */
    if (config('modules.raport')) {
        Route::get('/rapot', [SantriController::class, 'rapotShow'])->name('rapot.show');
        Route::get('/rapot/pdf', [SantriController::class, 'downloadRapotPdf'])->name('rapot.pdf');
        Route::get('/setorans', [SantriController::class, 'setorans'])->name('setorans.detail');
        Route::get('/presensis', [SantriController::class, 'presensis'])->name('presensis.detail');
        Route::get('/setorans/{setoran}/audio', [SantriController::class, 'getSetoranAudio'])->name('setorans.audio');
        Route::get('/setorans/{setoran}/pdf', [SantriController::class, 'getSetoranPdf'])->name('setorans.pdf');
    }

    /* ---------- Bagian Keuangan ---------- */
    if (config('modules.keuangan')) {
        Route::get('/keuangan', [PortalKeuanganController::class, 'index'])
            ->name('keuangan.index');

        Route::get('/keuangan/kwitansi/{pembayaran}', [PortalKeuanganController::class, 'printKwitansi'])
            ->whereNumber('pembayaran')
            ->name('keuangan.kwitansi');

        // Wali mengirim bukti transfer. Masuk sebagai draft di halaman
        // Pembayaran bendahara — tidak mengubah saldo sebelum disetujui.
        // throttle: cegah tombol kirim ditekan berulang-ulang.
        Route::post('/keuangan/konfirmasi', [PortalKeuanganController::class, 'kirimKonfirmasi'])
            ->middleware('throttle:10,1')
            ->name('keuangan.konfirmasi');

        Route::delete('/keuangan/konfirmasi/{pembayaran}', [PortalKeuanganController::class, 'batalkanKonfirmasi'])
            ->whereNumber('pembayaran')
            ->name('keuangan.konfirmasi.batal');
    }

    /* ---------- Komplain / Laporan Data ---------- */
    Route::prefix('laporan')->name('laporan.')->controller(\App\Http\Controllers\LaporanController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{laporan}', 'show')->whereNumber('laporan')->name('show');
        Route::post('/{laporan}/balas', 'reply')->whereNumber('laporan')->name('reply');
        Route::post('/{laporan}/selesai', 'selesai')->whereNumber('laporan')->name('selesai');
        Route::delete('/{laporan}', 'destroy')->whereNumber('laporan')->name('destroy');
        Route::put('/{laporan}/pesan/{pesan}', 'updatePesan')->whereNumber('laporan')->whereNumber('pesan')->name('pesan.update');
        Route::delete('/{laporan}/pesan/{pesan}', 'destroyPesan')->whereNumber('laporan')->whereNumber('pesan')->name('pesan.destroy');
    });
});