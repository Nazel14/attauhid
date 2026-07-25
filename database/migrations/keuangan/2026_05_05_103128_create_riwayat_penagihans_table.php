<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_penagihan', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke Siswa yang ditagih
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            
            // Relasi opsional ke Tagihan spesifik (jika menagih 1 invoice tertentu)
            // Jika menagih global (semua tunggakan siswa), ini bisa null
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->cascadeOnDelete();
            
            $table->dateTime('tanggal_penagihan');
            $table->enum('metode', ['WhatsApp', 'Surat Peringatan', 'Telepon', 'Lainnya']);
            
            $table->string('keterangan')->nullable(); // Cth: "Surat Peringatan 1 (SP1)" atau "Pesan WA ke Ayah"
            
            // Siapa petugas/bendahara yang melakukan penagihan
            $table->foreignId('ditagih_oleh')->constrained('users');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_penagihan');
    }
};