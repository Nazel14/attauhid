<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah ke tabel Pegawai (sesuaikan nama tabel jika berbeda)
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('class_id')->nullable()->after('position_id')->constrained('classes')->nullOnDelete();
        });

        // 2. Tambah ke tabel Siswa (sesuaikan nama tabel jika berbeda)
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('class_id')->nullable()->after('student_type')->constrained('classes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropColumn('class_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropColumn('class_id');
        });
    }
};