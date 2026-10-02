<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();

            // LONGTEXT: menampung gambar tanda tangan dalam bentuk
            // data-URI base64, bukan hanya teks pendek.
            $table->longText('value')->nullable();

            $table->timestamp('updated_at')->nullable();

            // Tanpa foreign key — hanya catatan siapa yang terakhir
            // mengubah. Mengikatnya ke users akan membuat penghapusan
            // akun gagal dengan error 1451 yang tidak ada hubungannya.
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};