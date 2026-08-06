<?php

use App\Http\Controllers\Keuangan\DashboardController;
use App\Http\Controllers\Keuangan\Finance\LaporanController;
use App\Http\Controllers\Keuangan\Inbound\PembayaranController;
use App\Http\Controllers\Keuangan\Inbound\TagihanController;
use App\Http\Controllers\Keuangan\Utils\MediaController;
use App\Http\Controllers\Keuangan\MasterData\JabatanController;
use App\Http\Controllers\Keuangan\MasterData\KelasController;
use App\Http\Controllers\Keuangan\MasterData\PegawaiController;
use App\Http\Controllers\Keuangan\MasterData\RekeningController;
use App\Http\Controllers\Keuangan\MasterData\RkasTemplateController;
use App\Http\Controllers\Keuangan\MasterData\SiswaController;
use App\Http\Controllers\Keuangan\MasterData\TarifController;
use App\Http\Controllers\Keuangan\Outbound\PengeluaranController;
use App\Http\Controllers\Keuangan\Payroll\AbsensiController;
use App\Http\Controllers\Keuangan\Payroll\KomponenGajiController;
use App\Http\Controllers\Keuangan\Payroll\PayrollController;
use App\Http\Controllers\Keuangan\Outbound\RkasController;
use App\Http\Controllers\System\RoleController;
use App\Http\Controllers\System\UserController;
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
    // Sumber tunggal: config('modules.keuangan_roles').
    // Tambah role keuangan baru cukup di config, tidak perlu sentuh file ini.
    'role:' . implode(',', config('modules.keuangan_roles')),
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

        // Daftar santri per kelas. Param bisa id kelas atau 'tanpa-kelas'
        // untuk siswa yang belum dapat pembagian — mereka tetap harus
        // terjangkau karena tagihannya jalan terus.
        Route::get('/siswa/kelas/{kelas}', [SiswaController::class, 'detail'])
            ->middleware('permission:master.siswa.lihat')->name('siswa.detail');

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

        /* ---------- 4e. PENGAJUAN PENGELUARAN ---------- */
        Route::get('/pengajuan', [PengeluaranController::class, 'index'])
            ->middleware('permission:pengeluaran.lihat')->name('pengajuan');
        Route::post('/pengajuan', [PengeluaranController::class, 'store'])
            ->middleware('permission:pengeluaran.buat')->name('pengajuan.store');
        Route::put('/pengajuan/{pengeluaran}', [PengeluaranController::class, 'update'])
            ->middleware('permission:pengeluaran.edit')->name('pengajuan.update');
        Route::delete('/pengajuan/{pengeluaran}', [PengeluaranController::class, 'destroy'])
            ->middleware('permission:pengeluaran.hapus')->name('pengajuan.destroy');

        // Gerbang per tahap ada di controller — satu route melayani dua stage.
        Route::post('/pengajuan/{pengeluaran}/approve', [PengeluaranController::class, 'approve'])
            ->middleware('permission:pengeluaran.lihat')->name('pengajuan.approve');

        Route::post('/pengajuan/{pengeluaran}/lpj', [PengeluaranController::class, 'storeLpj'])
            ->middleware('permission:pengeluaran.buat_lpj')->name('pengajuan.store_lpj');

        // Versi asli: '/pengeluaran/pengajuan/{id}/complete-refund' di dalam
        // prefix 'pengeluaran' — URL-nya jadi berulang dua kali.
        Route::post('/pengajuan/{pengeluaran}/complete-refund', [PengeluaranController::class, 'completeRefund'])
            ->middleware('permission:pengeluaran.approve_selesai')->name('pengajuan.complete_refund');
    });

    /* ---------- 4d. PEMASUKAN ---------- */
    Route::prefix('pemasukan')->name('pemasukan.')->group(function () {

        Route::get('/kelola-tagihan', [TagihanController::class, 'index'])
            ->middleware('permission:tagihan.lihat')->name('tagihan.index');

        Route::post('/kelola-tagihan/header', [TagihanController::class, 'storeHeader'])
            ->middleware('permission:tagihan.buat_batch')->name('tagihan.store_header');
        Route::put('/kelola-tagihan/header/{header}', [TagihanController::class, 'updateHeader'])
            ->middleware('permission:tagihan.edit_batch')->name('tagihan.update_header');

        // Versi asli: 'tagihan.tutup_batch|tagihan.hapus_rincian' — kombinasi
        // yang tidak jelas maksudnya. Diganti permission yang memang untuk ini.
        Route::delete('/kelola-tagihan/header/{header}', [TagihanController::class, 'destroyHeader'])
            ->middleware('permission:tagihan.hapus_batch')->name('tagihan.destroy_header');

        Route::post('/kelola-tagihan/header/{header}/generate', [TagihanController::class, 'autoGenerate'])
            ->middleware('permission:tagihan.buat_rincian')->name('tagihan.generate');
        Route::post('/kelola-tagihan/header/{header}/publish', [TagihanController::class, 'publishHeader'])
            ->middleware('permission:tagihan.publish')->name('tagihan.publish_header');
        Route::post('/kelola-tagihan/header/{header}/close', [TagihanController::class, 'closeHeader'])
            ->middleware('permission:tagihan.tutup_batch')->name('tagihan.close_header');
        Route::get('/kelola-tagihan/header/{header}/print-bulk', [TagihanController::class, 'printBulk'])
            ->middleware('permission:tagihan.cetak_massal')->name('tagihan.print_bulk');

        // Cetak satuan — memakai permission yang sama dengan cetak massal
        // agar tidak perlu menambah baris baru di tabel permissions.
        Route::get('/kelola-tagihan/detail/{tagihan}/print', [TagihanController::class, 'printSingle'])
            ->middleware('permission:tagihan.cetak_massal')->name('tagihan.print');

        Route::post('/kelola-tagihan/detail', [TagihanController::class, 'storeDetail'])
            ->middleware('permission:tagihan.buat_rincian')->name('tagihan.store_detail');
        Route::put('/kelola-tagihan/detail/{tagihan}', [TagihanController::class, 'updateDetail'])
            ->middleware('permission:tagihan.edit_rincian')->name('tagihan.update_detail');
        Route::delete('/kelola-tagihan/detail/{tagihan}', [TagihanController::class, 'destroy'])
            ->middleware('permission:tagihan.hapus_rincian')->name('tagihan.destroy');

        /* Pembayaran — satu alur untuk semua kategori pemasukan:
           tagihan, uang masuk, koperasi, donatur. */
        Route::get('/pembayaran', [PembayaranController::class, 'index'])
            ->middleware('permission:pembayaran.lihat')->name('pembayaran.index');
        Route::post('/pembayaran', [PembayaranController::class, 'store'])
            ->middleware('permission:pembayaran.buat')->name('pembayaran.store');
        Route::put('/pembayaran/{pembayaran}', [PembayaranController::class, 'update'])
            ->middleware('permission:pembayaran.edit')->name('pembayaran.update');
        Route::delete('/pembayaran/{pembayaran}', [PembayaranController::class, 'destroy'])
            ->middleware('permission:pembayaran.hapus')->name('pembayaran.destroy');
        Route::get('/pembayaran/{pembayaran}/print', [PembayaranController::class, 'printKwitansi'])
            ->middleware('permission:pembayaran.cetak')->name('pembayaran.print');

        // Gerbang per tahap ada di controller — satu route melayani dua stage.
        Route::post('/pembayaran/{pembayaran}/approve', [PembayaranController::class, 'approve'])
            ->middleware('permission:pembayaran.lihat')->name('pembayaran.approve');
    });

    // Bukti pembayaran disimpan di storage/app, disajikan lewat controller
    // yang mengecek permission.
    Route::get('/media/{media}', [MediaController::class, 'show'])
        ->middleware('permission:pembayaran.lihat')->name('media.show');

    /* ---------- 4f. PENGGAJIAN ---------- */
    Route::prefix('penggajian')->name('penggajian.')->group(function () {

        // Absensi
        Route::get('/absensi', [AbsensiController::class, 'index'])
            ->middleware('permission:absensi.lihat')->name('absensi');
        Route::get('/absensi/holidays', [AbsensiController::class, 'getHolidays'])
            ->middleware('permission:absensi.lihat')->name('absensi.holidays');
        Route::post('/absensi/update', [AbsensiController::class, 'update'])
            ->middleware('permission:absensi.kelola')->name('absensi.update');
        Route::post('/absensi/bulk-hadir', [AbsensiController::class, 'bulkHadir'])
            ->middleware('permission:absensi.kelola')->name('absensi.bulk_hadir');

        // Komponen gaji: tunjangan & potongan
        Route::get('/komponen', [KomponenGajiController::class, 'index'])
            ->middleware('permission:tunjangan.lihat|potongan.lihat')->name('komponen');

        Route::post('/komponen/tunjangan', [KomponenGajiController::class, 'storeTunjangan'])
            ->middleware('permission:tunjangan.buat')->name('komponen.tunjangan.store');
        Route::put('/komponen/tunjangan/{tunjangan}', [KomponenGajiController::class, 'updateTunjangan'])
            ->middleware('permission:tunjangan.edit')->name('komponen.tunjangan.update');
        Route::delete('/komponen/tunjangan/{tunjangan}', [KomponenGajiController::class, 'destroyTunjangan'])
            ->middleware('permission:tunjangan.hapus')->name('komponen.tunjangan.destroy');

        Route::post('/komponen/potongan', [KomponenGajiController::class, 'storePotongan'])
            ->middleware('permission:potongan.buat')->name('komponen.potongan.store');
        Route::put('/komponen/potongan/{potongan}', [KomponenGajiController::class, 'updatePotongan'])
            ->middleware('permission:potongan.edit')->name('komponen.potongan.update');
        Route::delete('/komponen/potongan/{potongan}', [KomponenGajiController::class, 'destroyPotongan'])
            ->middleware('permission:potongan.hapus')->name('komponen.potongan.destroy');

        // Transaksi payroll
        Route::get('/data', [PayrollController::class, 'index'])
            ->middleware('permission:penggajian.lihat')->name('data');
        Route::post('/data/generate', [PayrollController::class, 'autoGenerate'])
            ->middleware('permission:penggajian.generate_batch')->name('generate');
        Route::delete('/header/{header}', [PayrollController::class, 'destroyHeader'])
            ->middleware('permission:penggajian.hapus_batch')->name('destroy_header');
        Route::get('/header/{header}/print', [PayrollController::class, 'printBulk'])
            ->middleware('permission:penggajian.cetak')->name('print_bulk');

        // Gerbang per tahap ada di controller — satu route melayani tiga stage.
        Route::post('/header/{header}/approve', [PayrollController::class, 'approve'])
            ->middleware('permission:penggajian.lihat')->name('approve');

        Route::post('/detail', [PayrollController::class, 'storeDetail'])
            ->middleware('permission:penggajian.buat_rincian')->name('store_detail');
        Route::put('/detail/{detail}', [PayrollController::class, 'updateDetail'])
            ->middleware('permission:penggajian.edit_rincian')->name('update_detail');
        Route::delete('/detail/{detail}', [PayrollController::class, 'destroyDetail'])
            ->middleware('permission:penggajian.hapus_rincian')->name('destroy_detail');
    });

    /* ---------- 4g. LAPORAN ---------- */
    Route::prefix('laporan')->name('laporan.')->group(function () {

        Route::get('/buku-kas-umum', [LaporanController::class, 'bukuKasUmum'])
            ->middleware('permission:laporan.buku_kas')->name('buku_kas_umum');
        Route::get('/buku-kas-umum/cetak', [LaporanController::class, 'cetakBukuKas'])
            ->middleware('permission:laporan.buku_kas')->name('buku_kas_umum.cetak');

        Route::get('/tunggakan', [LaporanController::class, 'tunggakan'])
            ->middleware('permission:laporan.tunggakan')->name('tunggakan');

        Route::get('/realisasi-rkas', [LaporanController::class, 'realisasiRkas'])
            ->middleware('permission:laporan.rkas')->name('realisasi_rkas');
    });

    // Pencatatan tindakan penagihan — di luar grup laporan karena juga
    // dipanggil dari halaman Kelola Tagihan.
    Route::post('/penagihan/catat', [LaporanController::class, 'catatPenagihan'])
        ->middleware('permission:tagihan.catat_log')->name('penagihan.catat');

    /* ---------- 4h. PENGATURAN SISTEM ---------- */
    // Digembok ke super-admin saja. Kalau permission 'sistem.*' sudah ada
    // di tabel permissions, tukar 'role:super-admin' dengan
    // 'permission:sistem.role.lihat' dst. per route.
    Route::middleware('role:super-admin')->prefix('pengaturan')->name('pengaturan.')->group(function () {

        Route::prefix('role')->name('role.')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
            Route::get('/create', [RoleController::class, 'create'])->name('create');
            Route::post('/', [RoleController::class, 'store'])->name('store');
            Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
            Route::put('/{role}', [RoleController::class, 'update'])->name('update');
            Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('user')->name('user.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        });
    });
});
