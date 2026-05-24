<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        // ✅ Set default string length untuk MySQL
        Schema::defaultStringLength(191);
        
        // ✅ Force timezone untuk semua koneksi database
        if (config('database.default') === 'mysql') {
            DB::statement("SET time_zone = '+07:00'");
        }
        
        // ✅ Set PHP timezone (backup)
        date_default_timezone_set('Asia/Jakarta');
    }
}