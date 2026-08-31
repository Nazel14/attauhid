<?php

namespace App\Models\Keuangan\Outbound;

use App\Models\Keuangan\MasterData\RkasTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RkasDetail extends Model
{
    use SoftDeletes;

    protected $table = 'rkas_details';

    protected $fillable = [
        'rkas_header_id', 'rkas_template_id', 'parent_id',
        'kode_kegiatan', 'nama_kegiatan',
        'volume_1', 'satuan_1', 'volume_2', 'satuan_2',
        'harga_satuan', 'pagu_anggaran', 'nominal_realisasi', 'is_parent',
    ];

    protected $casts = [
        'volume_1'          => 'integer',
        'volume_2'          => 'integer',
        'harga_satuan'      => 'decimal:2',
        'pagu_anggaran'     => 'decimal:2',
        'nominal_realisasi' => 'decimal:2',
        'is_parent'         => 'boolean',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(RkasHeader::class, 'rkas_header_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(RkasTemplate::class, 'rkas_template_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(RkasDetail::class, 'parent_id');
    }

    // Versi asli hasOne — satu mata anggaran bisa dicairkan lebih dari sekali,
    // jadi hasMany yang benar (dipakai untuk menghitung realisasi di 4e).
    public function pengeluaran(): HasMany
    {
        return $this->hasMany(Pengeluaran::class, 'rkas_id');
    }
}