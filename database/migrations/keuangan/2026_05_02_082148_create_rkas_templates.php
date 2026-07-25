<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rkas_templates', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('parent_id')->nullable()->constrained('rkas_templates')->cascadeOnDelete();
            
            $table->string('kode_judul'); 
            $table->string('nama_judul'); 
            
            $table->integer('level')->default(1); 

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rkas_templates');
    }
};