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
        // Guard: drop dulu jika masih ada (workaround PostgreSQL migrate:fresh)
        Schema::dropIfExists('payroll_details');
        Schema::dropIfExists('payroll_headers');
        Schema::dropIfExists('attendances');

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->date('date');
            $table->enum('status', ['Hadir', 'Sakit', 'Izin', 'Alfa']);
            $table->timestamps();
        });

        Schema::create('payroll_headers', function (Blueprint $table) {
            $table->id();
            $table->string('periode_bulan', 2); 
            $table->string('periode_tahun', 4); 

            $table->decimal('total_gaji_pokok', 15, 2)->default(0);
            $table->decimal('total_tunjangan', 15, 2)->default(0);
            $table->decimal('total_potongan', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);

            $table->enum('status_approval', ['draft', 'diperiksa', 'diketahui', 'disetujui', 'dibayar'])->default('draft');

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('diperiksa_by')->nullable()->constrained('users');
            $table->timestamp('diperiksa_at')->nullable();
            $table->foreignId('diketahui_by')->nullable()->constrained('users');
            $table->timestamp('diketahui_at')->nullable();
            $table->foreignId('disetujui_by')->nullable()->constrained('users');
            $table->timestamp('disetujui_at')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('payroll_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_header_id')->constrained('payroll_headers')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees');

            $table->decimal('gaji_pokok', 15, 2);
            $table->decimal('total_tunjangan', 15, 2)->default(0);
            $table->decimal('total_potongan', 15, 2)->default(0);
            $table->decimal('take_home_pay', 15, 2);
            $table->boolean('is_paid')->default(false);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_headers');
        Schema::dropIfExists('payroll_details');
        Schema::dropIfExists('attendances');
    }
};
