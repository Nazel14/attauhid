<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('outbound_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rkas_id')->constrained('rkas_details');
            $table->foreignId('account_id')->constrained('accounts'); // Sumber dana
            
            $table->date('tanggal_pengajuan'); // [BARU]
            $table->decimal('nominal_pengajuan', 15, 2);
            $table->text('deskripsi');
            
            // Kolom LPJ & Refund
            $table->decimal('nominal_realisasi', 15, 2)->default(0); // [BARU] Uang yang benar-benar habis dipakai
            $table->decimal('nominal_refund', 15, 2)->default(0); // Sisa uang yang dikembalikan
            $table->boolean('is_refund_completed')->default(false);
            $table->boolean('is_over_budget_warning')->default(false);

            // Alur Persetujuan Linier
            $table->enum('status_approval', ['draft', 'diperiksa', 'diketahui', 'disetujui', 'selesai'])->default('draft');
            
            $table->foreignId('requested_by')->constrained('users'); 
            
            $table->foreignId('diperiksa_by')->nullable()->constrained('users'); 
            $table->timestamp('diperiksa_at')->nullable();
            
            $table->foreignId('disetujui_by')->nullable()->constrained('users'); 
            $table->timestamp('disetujui_at')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outbound_requests');
    }
};
