<?php

namespace App\Models\Keuangan\Inbound;

use App\Models\Keuangan\MasterData\Tarif;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceHeader extends Model
{
    use SoftDeletes;

    protected $table = 'invoice_headers';

        protected $fillable = [
        'batch_number', 'tariff_id', 'title', 'period', 'due_date',
        // Hilang di versi sebelumnya. storeHeader() menulis nilainya, tapi
        // Laravel membuang kunci yang tidak fillable tanpa pesan apa pun —
        // sehingga snapshot targetnya tidak pernah tersimpan sekali pun.
        'target_summary',
        'total_students', 'total_expected_amount', 'status',
    ];

    protected $casts = [
        'due_date'              => 'date',
        'total_students'        => 'integer',
        'total_expected_amount' => 'decimal:2',
    ];

    public function tarif(): BelongsTo
    {
        return $this->belongsTo(Tarif::class, 'tariff_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Tagihan::class, 'invoice_header_id');
    }
}