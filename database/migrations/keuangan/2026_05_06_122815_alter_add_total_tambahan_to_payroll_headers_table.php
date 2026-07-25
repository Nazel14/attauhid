<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_headers', function (Blueprint $table) {
            $table->integer('total_tambahan')->default(0)->after('total_potongan');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_headers', function (Blueprint $table) {
            $table->dropColumn('total_tambahan');
        });
    }
};