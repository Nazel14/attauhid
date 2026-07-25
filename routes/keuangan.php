<?php

use Illuminate\Support\Facades\Route;

/* ======================================
 * MODUL KEUANGAN SEKOLAH
 * Di-load dari web.php bila config('modules.keuangan') aktif.
 *
 * Semua route dibungkus gerbang role di level grup. Di edufinance asli
 * hanya ada middleware 'permission:', sehingga user modul lain (santri,
 * guru) masih bisa mengetuk URL-nya dan baru ditolak di lapis permission.
 * Di sini modulnya benar-benar tertutup untuk role di luar keuangan.
 * ====================================== */

Route::middleware([
    'auth',
    'role:super-admin,bendahara,kepala-sekolah,pihak-yayasan,hrd',
])->prefix('keuangan')->name('keuangan.')->group(function () {

    // Placeholder — diganti DashboardController pada tahap 4g.
    Route::get('/dashboard', function () {
        return response()->json([
            'modul'       => 'keuangan',
            'user'        => auth()->user()->name,
            'roles'       => auth()->user()->getRoleNames(),
            'permissions' => auth()->user()->getAllPermissions()->count(),
        ]);
    })->name('dashboard');

    // 4b master-data | 4c rkas | 4d pemasukan
    // 4e pengeluaran | 4f penggajian | 4g laporan | 4h pengaturan
});