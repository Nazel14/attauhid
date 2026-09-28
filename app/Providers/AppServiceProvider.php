<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Model keuangan yang perubahannya wajib meninggalkan jejak.
     *
     * Dipisah jadi konstanta agar penambahan model cukup satu baris, dan
     * agar alasan pemilihannya terdokumentasi di satu tempat.
     *
     * Sengaja TIDAK dimasukkan:
     * - PayrollDetail, PayrollTunjangan, PayrollPotongan — nilainya dihitung
     *   ulang otomatis setiap kali pegawai ditambah atau subsidi kelas
     *   dihitung, sehingga satu klik bisa melahirkan belasan baris log yang
     *   bukan keputusan manusia. PayrollHeader sudah mencatat grand_total
     *   dan status_approval, yang justru dibaca saat ada sengketa gaji.
     * - Absensi — volumenya harian dan bukan uang secara langsung.
     * - Santri, Guru, Kelas — controller-nya sudah memanggil
     *   LogActivity::create() sendiri, jadi akan tercatat dua kali.
     */
    private const MODEL_DIAUDIT = [
        // Transaksi: uang benar-benar bergerak.
        \App\Models\Keuangan\Inbound\Pembayaran::class,
        \App\Models\Keuangan\Inbound\Tagihan::class,
        \App\Models\Keuangan\Inbound\InvoiceHeader::class,
        \App\Models\Keuangan\Inbound\ItemTagihan::class,
        \App\Models\Keuangan\Outbound\Pengeluaran::class,
        \App\Models\Keuangan\Payroll\PayrollHeader::class,
        \App\Models\Keuangan\Finance\MutasiKas::class,

        // Saldo dan anggaran.
        \App\Models\Keuangan\MasterData\Rekening::class,
        \App\Models\Keuangan\Outbound\RkasHeader::class,
        \App\Models\Keuangan\Outbound\RkasDetail::class,

        // Master data penentu nominal. Jarang diubah, tapi sekali diubah
        // ikut menggeser gaji atau tagihan semua orang — justru karena
        // jarang, jejaknya murah dan sangat berharga.
        \App\Models\Keuangan\MasterData\Jabatan::class,
        \App\Models\Keuangan\MasterData\Tunjangan::class,
        \App\Models\Keuangan\MasterData\Potongan::class,
        \App\Models\Keuangan\MasterData\Tarif::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ✅ Set default string length untuk MySQL
        Schema::defaultStringLength(191);

        // ✅ Force timezone untuk semua koneksi database
        if (config('database.default') === 'mysql') {
            DB::statement("SET time_zone = '+07:00'");
        }

        // ✅ Set PHP timezone (backup)
        date_default_timezone_set('Asia/Jakarta');

        // Migrasi modul keuangan hanya dimuat bila modulnya aktif, supaya
        // instalasi raport-only tidak ikut membuat 25 tabel keuangan.
        if (config('modules.keuangan')) {
            $this->loadMigrationsFrom(database_path('migrations/keuangan'));

            // Jejak audit dipasang di sini, bukan di tiap controller, supaya
            // perubahan lewat jalur mana pun tetap tercatat — dan hanya bila
            // modul keuangannya memang aktif, karena kelas-kelas ini ikut
            // di-autoload begitu observe() dipanggil.
            foreach (self::MODEL_DIAUDIT as $model) {
                $model::observe(\App\Observers\AuditObserver::class);
            }
        }

        // Jaring pengaman super-admin.
        //
        // Kunci di UI Manajemen Role hanya mencegah kesalahan lewat form.
        // Baris di role_has_permissions masih bisa rusak lewat seeder, tinker,
        // atau SQL langsung di phpMyAdmin. Dengan Gate::before ini, akses
        // super-admin tidak lagi bergantung pada isi tabel tersebut —
        // pemulihan lewat tinker tidak pernah jadi satu-satunya jalan.
        //
        // return null (bukan false) supaya pengecekan normal tetap berjalan
        // untuk user non-super-admin.
        Gate::before(function ($user, $ability) {
            // Saat migrasi awal tabel roles belum ada; jangan sampai
            // artisan gagal hanya karena pengecekan ini.
            if (! Schema::hasTable('roles')) {
                return null;
            }

            return $user->hasRole('super-admin') ? true : null;
        });
    }
}