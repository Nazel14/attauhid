<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Konfirmasi pembayaran oleh wali + penolakan oleh bendahara.
 *
 * Di hosting dijalankan manual lewat phpMyAdmin, jadi tiap langkah dijaga
 * supaya migration ini tetap aman bila suatu saat ikut dijalankan sesudahnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inbound_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('inbound_payments', 'sumber')) {
                $table->string('sumber', 10)->default('bendahara')->after('kategori')->index();
            }
            if (! Schema::hasColumn('inbound_payments', 'ditolak_by')) {
                $table->unsignedBigInteger('ditolak_by')->nullable();
            }
            if (! Schema::hasColumn('inbound_payments', 'ditolak_at')) {
                $table->timestamp('ditolak_at')->nullable();
            }
            if (! Schema::hasColumn('inbound_payments', 'alasan_tolak')) {
                $table->string('alasan_tolak', 255)->nullable();
            }
        });

        // status_approval bisa berupa ENUM yang belum mengenal 'ditolak'.
        DB::statement("ALTER TABLE inbound_payments MODIFY status_approval VARCHAR(20) NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        Schema::table('inbound_payments', function (Blueprint $table) {
            foreach (['alasan_tolak', 'ditolak_at', 'ditolak_by'] as $kolom) {
                if (Schema::hasColumn('inbound_payments', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
            if (Schema::hasColumn('inbound_payments', 'sumber')) {
                $table->dropIndex(['sumber']);
                $table->dropColumn('sumber');
            }
        });
    }
};