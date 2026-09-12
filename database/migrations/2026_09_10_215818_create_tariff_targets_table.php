<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariff_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tariff_id')
                  ->constrained('tariffs')
                  ->cascadeOnDelete();
            $table->string('target_type', 30);
            $table->string('target_value', 100);
            $table->timestamps();

            $table->index(['tariff_id', 'target_type']);
            $table->unique(['tariff_id', 'target_type', 'target_value'], 'tariff_target_unique');
        });

        Schema::table('tariffs', function (Blueprint $table) {
            $table->boolean('is_donation')->default(false)->after('nominal');
        });

        Schema::table('invoice_headers', function (Blueprint $table) {
            $table->string('target_summary', 255)->nullable()->after('period');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_headers', function (Blueprint $table) {
            $table->dropColumn('target_summary');
        });

        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropColumn('is_donation');
        });

        Schema::dropIfExists('tariff_targets');
    }
};