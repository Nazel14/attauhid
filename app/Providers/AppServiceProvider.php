<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
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
        // Jejak audit data keuangan. Ditambahkan di sini, bukan di tiap
        // controller, supaya perubahan lewat jalur mana pun tetap tercatat.
        foreach ([
            \App\Models\Pembayaran::class,
            \App\Models\Pengeluaran::class,
            \App\Models\Tagihan::class,
        ] as $model) {
            $model::observe(\App\Observers\AuditObserver::class);
        }
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