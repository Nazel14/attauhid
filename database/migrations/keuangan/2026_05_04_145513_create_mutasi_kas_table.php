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
        Schema::create('mutasi_kas', function (Blueprint $table) {
            $table->id();
            // Relasi ke tabel master rekening/kas
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            
            $table->dateTime('tanggal_transaksi');
            $table->string('keterangan'); 
            
            $table->enum('jenis', ['Debit', 'Kredit']); // Debit = Uang Masuk, Kredit = Uang Keluar
            $table->decimal('nominal', 15, 2);
            
            // Kunci utama untuk laporan Buku Kas (Running Balance)
            $table->decimal('saldo_setelah_transaksi', 15, 2); 
            
            // Relasi Polimorfik (Agar bisa terhubung ke Pengeluaran, Gaji, SPP, dll)
            $table->string('reference_type')->nullable(); 
            $table->unsignedBigInteger('reference_id')->nullable(); 
            
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutasi_kas');
    }
};
