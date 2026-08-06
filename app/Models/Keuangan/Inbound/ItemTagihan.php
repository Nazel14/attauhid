<?php

namespace App\Models\Keuangan\Inbound;

use App\Models\Keuangan\MasterData\Tarif;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemTagihan extends Model
{
    protected $table = 'invoice_items';

    protected $fillable = ['invoice_id', 'tariff_id', 'deskripsi', 'nominal', 'diskon'];

    protected $casts = [
        'nominal' => 'decimal:2',
        'diskon'  => 'decimal:2',
    ];

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class, 'invoice_id');
    }

    public function tarif(): BelongsTo
    {
        return $this->belongsTo(Tarif::class, 'tariff_id');
    }
}