<?php

namespace App\Models\Keuangan\MasterData;

use App\Models\Keuangan\Inbound\ItemTagihan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tarif extends Model
{
    use SoftDeletes;

    // Atribut #[Table] / #[Fillable] versi asli dibuang — fitur Laravel 12+.
    protected $table = 'tariffs';

    protected $fillable = [
        'billing_name',
        'nominal',
        'target_student_type',
        'period',
        'is_active',
    ];

    protected $casts = [
        'nominal'   => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function itemTagihan(): HasMany
    {
        return $this->hasMany(ItemTagihan::class, 'tariff_id');
    }
}