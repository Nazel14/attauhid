<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Edufinance membedakan pemasukan hanya lewat ada-tidaknya invoice_id.
     * Untuk memisahkan koperasi, uang pangkal, dan donasi, dibutuhkan
     * penanda eksplisit — semuanya tetap memakai satu alur approval,
     * satu buku kas, dan satu format kwitansi.
     */
    public function up(): void
    {
        Schema::table('inbound_payments', function (Blueprint $table) {
            $table->enum('kategori', ['tagihan', 'uang_masuk', 'koperasi', 'donatur', 'lainnya'])
                ->default('lainnya')
                ->after('invoice_id');

            // Nama donatur / mitra koperasi. Siswa tetap lewat student_id.
            $table->string('nama_pihak')->nullable()->after('kategori');
        });

        DB::table('inbound_payments')->whereNotNull('invoice_id')->update(['kategori' => 'tagihan']);
    }

    public function down(): void
    {
        Schema::table('inbound_payments', function (Blueprint $table) {
            $table->dropColumn(['kategori', 'nama_pihak']);
        });
    }
};