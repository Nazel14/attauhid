<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            
            // --- Kolom Data Utama (Wajib Didefinisikan Dulu) ---
            $table->string('name'); // <-- INI YANG HILANG ATAU SALAH URUTAN
            $table->string('tahun_ajaran');
            
            // --- Foreign Key ---
            $table->foreignId('guru_id')->constrained('gurus');

            // --- Batasan Unique (Diterapkan SETELAH Kolom Didefinisikan) ---
            // name dan tahun_ajaran harus unik gabungan
            $table->unique(['name', 'tahun_ajaran']); 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
   public function down(): void
    {
        // 1. Hapus Foreign Key dulu
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropForeign(['guru_id']);
        });

        // 2. Baru hapus tabel
        Schema::dropIfExists('kelas');
    }
};