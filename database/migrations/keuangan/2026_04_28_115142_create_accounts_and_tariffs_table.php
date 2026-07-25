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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_name');
            $table->enum('type',['Tunai','Bank','E-Wallet']);
            $table->decimal('balance', 15, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('tariffs', function (Blueprint $table) {
            $table->id();
            $table->string('billing_name');
            $table->decimal('nominal', 15, 2);
            $table->boolean('is_active');
            $table->enum('target_student_type', ['Reguler', 'Fullday', 'All', 'Donasi']);
            $table->enum('period', ['Bulanan', 'Semester', 'Tahunan', 'Sekali Bayar']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tariffs');
        Schema::dropIfExists('accounts');
    }
};
