<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * log_activities.role dibuat ENUM('admin','guru','santri'), sehingga
     * aktivitas user keuangan gagal dicatat. Karena penulisannya dibungkus
     * try/catch di AuthenticatedSessionController, kegagalan itu senyap —
     * hanya muncul di storage/logs.
     *
     * Tabel log adalah catatan historis; mengunci nilainya dengan ENUM
     * berarti setiap role baru memaksa ALTER TABLE. Diubah jadi VARCHAR.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `log_activities` MODIFY COLUMN `role` VARCHAR(50) NULL");
    }

    public function down(): void
    {
        // Akan gagal bila ada baris dengan role di luar ketiga nilai lama.
        DB::statement("
            ALTER TABLE `log_activities`
            MODIFY COLUMN `role` ENUM('admin','guru','santri') NULL
        ");
    }
};