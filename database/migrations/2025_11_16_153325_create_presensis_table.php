<?php
// Contoh nama file: 2025_11_16_160004_create_presensis_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presensis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('santri_id')->constrained('santris')->onDelete('cascade');
            $table->date('tanggal');
            $table->enum('sesi', ['Subuh', 'Pagi', 'Sore', 'Malam']);
            $table->enum('status', ['Hadir', 'Izin', 'Sakit', 'Alpha']);
            $table->unsignedTinyInteger('poin')->default(0); 
            
            // Kombinasi unik
            $table->unique(['santri_id', 'tanggal', 'sesi']); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presensis');
    }
};