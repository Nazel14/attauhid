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

        Schema::create('invoice_headers', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number')->unique();
            $table->foreignId('tariff_id')->constrained('tariffs');
            $table->string('title');
            $table->string('period');
            $table->date('due_date');

            $table->integer('total_students')->default(0);
            $table->decimal('total_expected_amount', 15, 2)->default(0);

            $table->enum('status', ['Draft', 'Published', 'Closed'])->default('Draft');

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();

            $table->foreignId('invoice_header_id')->nullable()->constrained('invoice_headers')->cascadeOnDelete();

            $table->foreignId('student_id')->constrained('students');

            $table->decimal('total_amount', 15, 2);
            $table->decimal('remaining_balance', 15, 2);

            $table->enum('status', ['Unpaid', 'Installment', 'Paid'])->default('Unpaid');

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('inbound_payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->foreignId('student_id')->nullable()->constrained('students');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices');
            $table->foreignId('account_id')->constrained('accounts');
            $table->decimal('nominal', 15, 2);
            $table->date('payment_date');

            $table->string('payment_method')->default('Tunai');
            $table->string('proof_of_payment')->nullable();
            $table->text('catatan')->nullable();

            $table->enum('status_approval', ['draft', 'diperiksa', 'diketahui', 'disetujui'])->default('draft');
            $table->foreignId('diperiksa_by')->nullable()->constrained('users');
            $table->foreignId('diketahui_by')->nullable()->constrained('users');
            $table->foreignId('disetujui_by')->nullable()->constrained('users');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbound_payments');
        Schema::dropIfExists('invoices');
    }
};
