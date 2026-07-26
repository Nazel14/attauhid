<?php

use App\Http\Controllers\Keuangan\DashboardController;
use App\Http\Controllers\Keuangan\MasterData\JabatanController;
use App\Http\Controllers\Keuangan\MasterData\KelasController;
use App\Http\Controllers\Keuangan\MasterData\PegawaiController;
use App\Http\Controllers\Keuangan\MasterData\RekeningController;
use App\Http\Controllers\Keuangan\MasterData\RkasTemplateController;
use App\Http\Controllers\Keuangan\MasterData\SiswaController;
use App\Http\Controllers\Keuangan\MasterData\TarifController;
use App\Http\Controllers\Keuangan\Outbound\RkasController;
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

    /* ---------- DASHBOARD ---------- */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /* ---------- 4b. MASTER DATA ---------- */
    Route::prefix('master-data')->name('master-data.')->group(function () {

        // Rekening (accounts)
        Route::get('/rekening', [RekeningController::class, 'index'])
            ->middleware('permission:master.rekening.lihat')
            ->name('rekening');

        Route::post('/rekening', [RekeningController::class, 'store'])
            ->middleware('permission:master.rekening.buat')
            ->name('rekening.store');

        Route::put('/rekening/{rekening}', [RekeningController::class, 'update'])
            ->middleware('permission:master.rekening.edit')
            ->name('rekening.update');

        Route::delete('/rekening/{rekening}', [RekeningController::class, 'destroy'])
            ->middleware('permission:master.rekening.hapus')
            ->name('rekening.destroy');

        // Tarif (tariffs)
        Route::get('/tarif', [TarifController::class, 'index'])
            ->middleware('permission:master.tarif.lihat')
            ->name('tarif');

        Route::post('/tarif', [TarifController::class, 'store'])
            ->middleware('permission:master.tarif.buat')
            ->name('tarif.store');

        Route::put('/tarif/{tarif}', [TarifController::class, 'update'])
            ->middleware('permission:master.tarif.edit')
            ->name('tarif.update');

        Route::delete('/tarif/{tarif}', [TarifController::class, 'destroy'])
            ->middleware('permission:master.tarif.hapus')
            ->name('tarif.destroy');

        // Jabatan (positions)
        Route::get('/jabatan', [JabatanController::class, 'index'])
            ->middleware('permission:master.jabatan.lihat')
            ->name('jabatan');

        Route::post('/jabatan', [JabatanController::class, 'store'])
            ->middleware('permission:master.jabatan.buat')
            ->name('jabatan.store');

        Route::put('/jabatan/{jabatan}', [JabatanController::class, 'update'])
            ->middleware('permission:master.jabatan.edit')
            ->name('jabatan.update');

        Route::delete('/jabatan/{jabatan}', [JabatanController::class, 'destroy'])
            ->middleware('permission:master.jabatan.hapus')
            ->name('jabatan.destroy');

        // Kelas (classes) — sub-grup, karena route name-nya bertingkat
        Route::prefix('kelas')->name('kelas.')->group(function () {
            Route::get('/', [KelasController::class, 'index'])
                ->middleware('permission:master.kelas.lihat')->name('index');
            Route::post('/', [KelasController::class, 'store'])
                ->middleware('permission:master.kelas.buat')->name('store');
            Route::put('/{id}', [KelasController::class, 'update'])
                ->middleware('permission:master.kelas.edit')->name('update');
            Route::delete('/{id}', [KelasController::class, 'destroy'])
                ->middleware('permission:master.kelas.hapus')->name('destroy');
        });

        // Siswa (students) — tanpa destroy, permission master.siswa.hapus
        // memang tidak ada di daftar 90 permission.
        Route::get('/siswa', [SiswaController::class, 'index'])
            ->middleware('permission:master.siswa.lihat')->name('siswa');
        Route::post('/siswa', [SiswaController::class, 'store'])
            ->middleware('permission:master.siswa.buat')->name('siswa.store');
        Route::put('/siswa/{siswa}', [SiswaController::class, 'update'])
            ->middleware('permission:master.siswa.edit')->name('siswa.update');

        // Pegawai (employees)
        Route::get('/pegawai', [PegawaiController::class, 'index'])
            ->middleware('permission:master.pegawai.lihat')->name('pegawai');
        Route::post('/pegawai', [PegawaiController::class, 'store'])
            ->middleware('permission:master.pegawai.buat')->name('pegawai.store');
        Route::put('/pegawai/{pegawai}', [PegawaiController::class, 'update'])
            ->middleware('permission:master.pegawai.edit')->name('pegawai.update');
        Route::post('/pegawai/{pegawai}/salary-components', [PegawaiController::class, 'updateSalaryComponents'])
            ->middleware('permission:master.pegawai.kelola_gaji')->name('pegawai.update_salary');
        Route::delete('/pegawai/{pegawai}', [PegawaiController::class, 'destroy'])
            ->middleware('permission:master.pegawai.hapus')->name('pegawai.destroy');

        // Template RKAS (rkas_templates)
        Route::get('/rkas-template', [RkasTemplateController::class, 'index'])
            ->middleware('permission:master.rkas.lihat')->name('rkas_template.index');
        Route::post('/rkas-template', [RkasTemplateController::class, 'store'])
            ->middleware('permission:master.rkas.buat')->name('rkas_template.store');
        Route::put('/rkas-template/{template}', [RkasTemplateController::class, 'update'])
            ->middleware('permission:master.rkas.edit')->name('rkas_template.update');
        Route::delete('/rkas-template/{template}', [RkasTemplateController::class, 'destroy'])
            ->middleware('permission:master.rkas.hapus')->name('rkas_template.destroy');
    });

    /* ---------- 4c. RKAS ---------- */
    Route::prefix('pengeluaran')->name('pengeluaran.')->group(function () {

        Route::get('/rkas-header', [RkasController::class, 'index'])
            ->middleware('permission:rkas.lihat')->name('rkas');
        Route::post('/rkas-header', [RkasController::class, 'store'])
            ->middleware('permission:rkas.buat_batch')->name('rkas.store');
        Route::put('/rkas-header/{header}', [RkasController::class, 'update'])
            ->middleware('permission:rkas.edit_batch')->name('rkas.update');
        Route::delete('/rkas-header/{header}', [RkasController::class, 'destroy'])
            ->middleware('permission:rkas.hapus_batch')->name('rkas.destroy');

        // Gerbang per tahap ada di dalam controller (approve_diperiksa /
        // approve_final / approve_tutup_batch), karena satu route melayani tiga stage.
        Route::post('/rkas-header/{header}/approve', [RkasController::class, 'approve'])
            ->middleware('permission:rkas.lihat')->name('rkas.approve');

        Route::post('/rkas/{header}/generate', [RkasController::class, 'generateStruktur'])
            ->middleware('permission:rkas.buat_rincian')->name('rkas.generate_struktur');
        Route::post('/rkas-detail/{header}/kegiatan', [RkasController::class, 'storeKegiatan'])
            ->middleware('permission:rkas.buat_rincian')->name('rkas.store_kegiatan');
        Route::put('/rkas-detail/kegiatan/{detail}', [RkasController::class, 'updateKegiatan'])
            ->middleware('permission:rkas.edit_rincian')->name('rkas.update_kegiatan');
        Route::delete('/rkas-detail/kegiatan/{detail}', [RkasController::class, 'destroyKegiatan'])
            ->middleware('permission:rkas.hapus_rincian')->name('rkas.destroy_kegiatan');
    });

    // 4d pemasukan | 4e pengeluaran
    // 4f penggajian | 4g laporan | 4h pengaturan
});
