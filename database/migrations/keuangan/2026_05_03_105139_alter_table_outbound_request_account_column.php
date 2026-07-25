<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outbound_requests', function (Blueprint $table) {
            $table->dropForeign(['account_id']);   // buang FK dulu
            $table->dropColumn('account_id');       // baru drop kolom
        });

        Schema::table('outbound_requests', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->constrained('accounts');
        });
    }

    public function down(): void
    {
        Schema::table('outbound_requests', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropColumn('account_id');
        });

        Schema::table('outbound_requests', function (Blueprint $table) {
            $table->foreignId('account_id')->constrained('accounts');
        });
    }
};