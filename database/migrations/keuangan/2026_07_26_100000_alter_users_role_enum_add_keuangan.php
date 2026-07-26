<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Kolom users.role dibuat sebagai ENUM dengan tiga nilai modul raport.
     * Modul keuangan menambah empat role baru, sehingga enum-nya perlu
     * dilebarkan — tanpa ini INSERT ditolak dengan "Data truncated".
     *
     * Migrasi ini berada di folder keuangan agar instalasi raport-only
     * tidak ikut membawa nilai enum yang tidak dipakainya.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE `users`
            MODIFY COLUMN `role`
            ENUM('admin','guru','santri','bendahara','kepala-sekolah','pihak-yayasan','hrd')
            NOT NULL DEFAULT 'guru'
        ");
    }

    public function down(): void
    {
        // Akan gagal bila masih ada user dengan role keuangan — memang
        // disengaja, supaya data tidak diam-diam terpotong jadi string kosong.
        DB::statement("
            ALTER TABLE `users`
            MODIFY COLUMN `role`
            ENUM('admin','guru','santri')
            NOT NULL DEFAULT 'guru'
        ");
    }
};