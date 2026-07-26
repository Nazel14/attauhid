<?php

namespace App\Models\Keuangan\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Potongan extends Model
{
    use SoftDeletes;

    protected $table = 'deductions';

    protected $fillable = ['nama_potongan', 'jenis', 'nominal_default'];

    protected $casts = [
        'nominal_default' => 'decimal:2',
    ];
}