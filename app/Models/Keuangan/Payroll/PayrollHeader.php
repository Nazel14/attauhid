<?php

namespace App\Models\Keuangan\Payroll;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * STUB — dilengkapi pada tahap 4f.
 */
class PayrollHeader extends Model
{
    use SoftDeletes;

    protected $table = 'payroll_headers';

    protected $guarded = ['id'];
}