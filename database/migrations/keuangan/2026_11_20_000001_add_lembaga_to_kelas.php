<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Memisahkan TK At-Tauhid dari Rumah Quran.
 *
 * Di hosting perubahan ini dijalankan manual lewat phpMyAdmin, jadi tiap
 * langkah dijaga supaya migration ini tetap aman bila suatu saat ikut
 * dijalankan sesudahnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('kelas', 'lembaga')) {
            Schema::table('kelas', function (Blueprint $table) {
                // Default 'tk': semua kelas yang sudah ada adalah kelas TK
                // sampai ditandai lain dari halaman Data Kelas.
                $table->string('lembaga', 10)->default('tk')->after('tingkat')->index();
            });
        }

        // Kolom kategori bisa berupa ENUM di sebagian instalasi; nilai baru
        // 'infaq_arahan' akan ditolak MySQL tanpa ini. VARCHAR menerima
        // semua nilai lama apa adanya.
        DB::statement("ALTER TABLE inbound_payments MODIFY kategori VARCHAR(30) NULL DEFAULT 'tagihan'");
    }

    public function down(): void
    {
        if (Schema::hasColumn('kelas', 'lembaga')) {
            Schema::table('kelas', function (Blueprint $table) {
                $table->dropIndex(['lembaga']);
                $table->dropColumn('lembaga');
            });
        }
    }
};