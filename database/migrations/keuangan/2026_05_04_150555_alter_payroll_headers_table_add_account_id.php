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
        Schema::table('payroll_headers', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->constrained('accounts');
            $table->foreignId('dibayar_by')->nullable()->constrained('users');
            $table->timestamp('dibayar_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_headers', function (Blueprint $table) {
            $table->dropColumn('account_id')->nullable()->constrained('accounts');
            $table->dropColumn('dibayar_by')->nullable()->constrained('accounts');
            $table->dropColumn('dibayar_at')->nullable()->constrained('accounts');
        });
    }
};
