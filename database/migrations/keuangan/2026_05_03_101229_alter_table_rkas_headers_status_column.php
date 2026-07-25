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
        Schema::table('rkas_headers', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->enum('status', ['Draft', 'Diperiksa', 'Diketahui', 'Final','Closed'])->default('Draft');

            $table->foreignId('created_by')->nullable()->constrained('users'); 

            $table->foreignId('diperiksa_by')->nullable()->constrained('users'); 
            $table->timestamp('diperiksa_at')->nullable();
            
            $table->foreignId('diketahui_by')->nullable()->constrained('users'); 
            $table->timestamp('diketahui_at')->nullable();
            
            $table->foreignId('final_by')->nullable()->constrained('users'); 
            $table->timestamp('final_at')->nullable();

            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->timestamp('closed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('rkas_headers', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('diperiksa_by');
            $table->dropColumn('diperiksa_at');
            $table->dropColumn('diketahui_by');
            $table->dropColumn('diketahui_at');
            $table->dropColumn('final_by');
            $table->dropColumn('final_by_at');
            $table->dropColumn('closed_by');
            $table->dropColumn('closed_at');
            $table->enum('status', ['Draft', 'Diperiksa', 'Diketahui', 'Final'])->default('Draft');
        });
    }
};
