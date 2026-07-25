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
        Schema::table('inbound_payments', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamp('diperiksa_at')->nullable();
            $table->timestamp('diketahui_at')->nullable();
            $table->timestamp('disetujui_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inbound_payments', function (Blueprint $table) {
            $table->dropColumn('diperiksa_at');
            $table->dropColumn('diketahui_at');
            $table->dropColumn('disetujui_at');
        });
    }
};
