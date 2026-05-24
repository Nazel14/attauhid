<?php
// Contoh nama file: 2025_11_16_160002_create_santris_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('santris', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke User (jika Santri punya akun login, opsional)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            // Relasi ke Kelas & Guru
            $table->foreignId('kelas_id')->constrained('kelas')->onDelete('cascade');
            $table->foreignId('guru_id')->constrained('gurus')->onDelete('cascade');
            // Akun login wali santri (Role: wali)
            $table->foreignId('wali_user_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->string('nis')->unique();
            $table->string('name');
            $table->enum('gender', ['Laki-laki', 'Perempuan']);
            $table->string('password_wali'); // Password untuk login wali
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('santris');
    }
};