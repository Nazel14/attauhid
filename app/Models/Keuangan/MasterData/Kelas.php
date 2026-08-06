<?php

namespace App\Models\Keuangan\MasterData;

use App\Models\Kelas as KelasRaport;

/**
 * Alias supaya controller keuangan yang sudah memakai namespace ini
 * tidak perlu diubah. Menunjuk tabel `kelas` yang sama dengan raport.
 *
 * Catatan: kolom namanya `name`, BUKAN `nama_kelas` seperti di tabel
 * `classes` yang lama. Semua referensi 'nama_kelas' di controller dan
 * JSX keuangan harus diganti jadi 'name'.
 */
class Kelas extends KelasRaport
{
    // Eloquent menebak nama tabel dari nama class konkret, jadi
    // $table harus disebut ulang di setiap subclass.
    protected $table = 'kelas';
}