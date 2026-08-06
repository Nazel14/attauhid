<?php

namespace App\Models\Keuangan\MasterData;

use App\Models\Santri;

/**
 * Siswa adalah nama lain dari Santri — keduanya menunjuk tabel `santris`.
 * Class ini dipertahankan supaya controller keuangan yang sudah memakai
 * `Siswa::` tidak perlu diubah satu per satu.
 *
 * Perbedaan nama kolom yang perlu diingat:
 *   students.class    → santris.angkatan  (tahun masuk)
 *   students.class_id → santris.kelas_id  (rombel)
 *
 * Relasi tagihan/pembayaran/kelas sudah ada di Santri.
 */
class Siswa extends Santri
{
    // Eloquent menebak nama tabel dari nama class konkret, jadi
    // $table harus disebut ulang di setiap subclass.
    protected $table = 'santris';
}