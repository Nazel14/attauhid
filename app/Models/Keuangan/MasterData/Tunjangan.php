<?php

namespace App\Models\Keuangan\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tunjangan extends Model
{
    use SoftDeletes;

    protected $table = 'allowances';

    protected $fillable = ['nama_tunjangan', 'jenis', 'nominal_default'];

    protected $casts = [
        'nominal_default' => 'decimal:2',
    ];
}