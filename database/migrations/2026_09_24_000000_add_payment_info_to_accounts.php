<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            // Nomor rekening & pemilik: sengaja tidak wajib, karena rekening
            // kas tunai tidak punya keduanya.
            $table->string('account_number', 50)->nullable()->after('type');
            $table->string('account_holder')->nullable()->after('account_number');

            // Path gambar QRIS di storage/app/public. Disajikan lewat route
            // /storage/{path} karena symlink diblokir di Hostinger.
            $table->string('qris_path')->nullable()->after('account_holder');

            // Penentu apakah rekening ini boleh dilihat wali di portal.
            // Default false: rekening operasional internal tidak otomatis
            // terbuka hanya karena kolom ini ditambahkan.
            $table->boolean('is_public')->default(false)->after('qris_path');

            $table->string('catatan_bayar')->nullable()->after('is_public');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn([
                'account_number', 'account_holder',
                'qris_path', 'is_public', 'catatan_bayar',
            ]);
        });
    }
};