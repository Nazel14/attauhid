<?php

namespace App\Models\Keuangan\Outbound;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RkasHeader extends Model
{
    use SoftDeletes;

    protected $table = 'rkas_headers';

    protected $fillable = [
        'tahun_ajaran', 'budget_limit', 'total_allocated', 'status',
        'created_by', 'diperiksa_by', 'final_by', 'closed_by',
        'diperiksa_at', 'final_at', 'closed_at',
    ];

    protected $casts = [
        'budget_limit'    => 'decimal:2',
        'total_allocated' => 'decimal:2',
        'diperiksa_at'    => 'datetime',
        'final_at'        => 'datetime',
        'closed_at'       => 'datetime',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(RkasDetail::class, 'rkas_header_id');
    }

    // Nama relasi sengaja tidak createdBy/closedBy: Laravel men-snake_case
    // nama relasi saat serialisasi, sehingga `createdBy` menjadi `created_by`
    // dan MENIMPA kolom integer dengan nama sama di JSON.
    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function diperiksaOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diperiksa_by');
    }

    public function difinalOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'final_by');
    }

    public function ditutupOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}