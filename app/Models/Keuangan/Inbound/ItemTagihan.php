<?php

namespace App\Models\Keuangan\Inbound;

use Illuminate\Database\Eloquent\Model;

/**
 * STUB — dilengkapi pada tahap 4d.
 * Sekarang hanya dibutuhkan agar relasi Tarif::itemTagihan() bisa dipanggil.
 */
class ItemTagihan extends Model
{
    protected $table = 'invoice_items';

    protected $guarded = ['id'];
}