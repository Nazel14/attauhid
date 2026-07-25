<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inbound_payments', function (Blueprint $table) {
            if (Schema::hasColumn('inbound_payments', 'diketahui_at')) {
                $table->dropForeign(['diketahui_by']);
                $table->dropColumn(['diketahui_at', 'diketahui_by']);
            }
        });

        Schema::table('payroll_headers', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_headers', 'diketahui_at')) {
                $table->dropForeign(['diketahui_by']);
                $table->dropColumn(['diketahui_at', 'diketahui_by']);
            }
        });

        Schema::table('outbound_requests', function (Blueprint $table) {
            if (Schema::hasColumn('outbound_requests', 'diketahui_at')) {
                $table->dropForeign(['diketahui_by']);
                $table->dropColumn(['diketahui_at', 'diketahui_by']);
            }
        });

        Schema::table('rkas_headers', function (Blueprint $table) {
            if (Schema::hasColumn('rkas_headers', 'diketahui_at')) {
                $table->dropForeign(['diketahui_by']);
                $table->dropColumn(['diketahui_at', 'diketahui_by']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('inbound_payments', function (Blueprint $table) {
            $table->foreignId('diketahui_by')->nullable()->after('diperiksa_by')->constrained('users');
            $table->timestamp('diketahui_at')->nullable()->after('diperiksa_at');
        });

        Schema::table('payroll_headers', function (Blueprint $table) {
            $table->foreignId('diketahui_by')->nullable()->after('diperiksa_by')->constrained('users');
            $table->timestamp('diketahui_at')->nullable()->after('diperiksa_at');
        });

        Schema::table('outbound_requests', function (Blueprint $table) {
            $table->foreignId('diketahui_by')->nullable()->after('diperiksa_by')->constrained('users');
            $table->timestamp('diketahui_at')->nullable()->after('diperiksa_at');
        });

        Schema::table('rkas_headers', function (Blueprint $table) {
            $table->foreignId('diketahui_by')->nullable()->after('diperiksa_by')->constrained('users');
            $table->timestamp('diketahui_at')->nullable()->after('diperiksa_at');
        });
    }
};