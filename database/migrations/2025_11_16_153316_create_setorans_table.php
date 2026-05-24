<?php
// Contoh nama file: 2025_11_16_160003_create_setorans_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setorans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('santri_id')->constrained('santris')->onDelete('cascade');
            $table->foreignId('guru_id')->constrained('gurus')->onDelete('cascade');
            $table->date('tanggal');
            
            $table->enum('kategori_setoran', ['Hafalan', 'Murajaah', 'Tilawah']);
            
            // Detail Setoran
            $table->string('surat_start');
            $table->unsignedSmallInteger('ayat_start');
            $table->string('surat_end');
            $table->unsignedSmallInteger('ayat_end');
            $table->unsignedSmallInteger('page_start')->nullable();
            $table->unsignedSmallInteger('page_end')->nullable();
            
            // Penilaian
            $table->unsignedTinyInteger('nilai')->default(100);
            $table->unsignedSmallInteger('kesalahan_kelancaran')->default(0); 
            $table->unsignedSmallInteger('kesalahan_tajwid')->default(0);     
            $table->unsignedSmallInteger('kesalahan_pelafalan')->default(0);  
            $table->text('catatan_guru')->nullable(); 
            $table->string('audio_path')->nullable(); 
            $table->string('koreksi_image_path')->nullable(); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setorans');
    }
};