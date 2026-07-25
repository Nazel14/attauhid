<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('nis')->unique()->nullable();
            $table->string('name');
            $table->string('parent_name');
            $table->string('parent_contact');
            $table->enum('student_type', ['Reguler', 'Fullday']);
            $table->string('class'); 
            $table->boolean('is_active')->default(true); 
            $table->boolean('is_raport_held')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('students'); }
};
