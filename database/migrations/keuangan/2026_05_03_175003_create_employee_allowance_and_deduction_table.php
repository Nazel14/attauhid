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
        Schema::create('employee_allowance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('allowance_id')->constrained('allowances')->cascadeOnDelete();
            $table->decimal('nominal_override', 15, 2)->nullable(); // Jika ingin nominal khusus per orang
            $table->timestamps();
        });

        // Tabel Pivot Potongan
        Schema::create('employee_deduction', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('deduction_id')->constrained('deductions')->cascadeOnDelete();
            $table->decimal('nominal_override', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_deduction');
        Schema::dropIfExists('employee_allowance');
    }
};
