<?php

namespace App\Models\Keuangan\MasterData;

use App\Models\Guru;

/**
 * Pegawai adalah nama lain dari Guru — keduanya menunjuk tabel `gurus`
 * yang sama. Class ini dipertahankan supaya controller keuangan yang
 * sudah memakai `Pegawai::` tidak perlu diubah satu per satu.
 *
 * Perubahan lewat model mana pun langsung terlihat di modul satunya,
 * karena tidak ada dua baris data — hanya satu.
 *
 * Relasi keuangan (jabatan, absensi, defaultAllowances, defaultDeductions)
 * sudah didefinisikan di Guru, jadi tidak diulang di sini.
 */
class Pegawai extends Guru
{
    // Diulang meski Guru sudah menyetelnya: Eloquent menebak nama tabel
    // dari nama class konkret, jadi tanpa baris ini query-nya mencari
    // tabel 'pegawais' yang tidak ada.
    protected $table = 'gurus';
}