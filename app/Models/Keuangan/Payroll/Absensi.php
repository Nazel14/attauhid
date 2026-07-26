<?php

namespace App\Models\Keuangan\Payroll;

use Illuminate\Database\Eloquent\Model;

/**
 * STUB — dilengkapi pada tahap 4f.
 * Dibutuhkan agar relasi Pegawai::absensi() bisa dipanggil.
 */
class Absensi extends Model
{
    protected $table = 'attendances';

    protected $guarded = ['id'];
}