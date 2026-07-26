<?php

namespace App\Models\Keuangan\Inbound;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * STUB — dilengkapi pada tahap 4d.
 * Sekarang hanya dibutuhkan agar relasi Siswa::tagihan() bisa dipanggil.
 */
class Tagihan extends Model
{
    use SoftDeletes;

    protected $table = 'invoices';

    protected $guarded = ['id'];
}