<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. TABEL HEADER (Identitas Anggaran)
        Schema::create('rkas_headers', function (Blueprint $table) {
            $table->id();
            $table->string('tahun_ajaran'); // Cth: 2026/2027
            $table->decimal('budget_limit', 15, 2); // Pagu total dari Yayasan/Pusat
            $table->decimal('total_allocated', 15, 2)->default(0); // Total yang sudah direncanakan

            $table->enum('status', ['Draft', 'Diperiksa', 'Diketahui', 'Final'])->default('Draft');

            $table->softDeletes();
            $table->timestamps();
        });

        // 2. TABEL DETAIL (Rincian Kegiatan)
        Schema::create('rkas_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rkas_header_id')->constrained('rkas_headers')->cascadeOnDelete();

            // Relasi ke template agar tahu ini bagian dari standar mana
            $table->foreignId('rkas_template_id')->nullable()->constrained('rkas_templates')->nullOnDelete();

            // Parent ID untuk struktur pohon di dalam detail itu sendiri
            $table->foreignId('parent_id')->nullable()->constrained('rkas_details')->cascadeOnDelete();

            $table->string('kode_kegiatan'); // 1.1 atau 1.1.1
            $table->string('nama_kegiatan');

            // Boleh null karena baris Parent (judul) tidak memiliki rincian ini
            $table->integer('volume_1')->nullable(); // Cth: 1, 12
            $table->string('satuan_1')->nullable();  // Cth: Keg/Tahun, Bulan, Paket

            $table->integer('volume_2')->nullable(); // Cth: 13, 50 (Opsional)
            $table->string('satuan_2')->nullable();  // Cth: Guru, Siswa, Orang (Opsional)

            $table->decimal('harga_satuan', 15, 2)->nullable(); // Cth: 500000
            // ----------------------------------------------------

            $table->decimal('pagu_anggaran', 15, 2)->default(0); // Hasil kali (Otomatis)
            $table->decimal('nominal_realisasi', 15, 2)->default(0);

            // Flag untuk membedakan baris judul (kuning/oren) dan baris input (putih)
            $table->boolean('is_parent')->default(false);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rkas_details');
        Schema::dropIfExists('rkas_headers');
    }
};
