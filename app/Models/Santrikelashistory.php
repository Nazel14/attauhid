<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SantriKelasHistory extends Model
{
    use HasFactory;

    protected $table = 'santri_kelas_history';

    protected $fillable = [
        'santri_id',
        'kelas_id',
        'tahun_ajaran_id',
        'tanggal_masuk',
        'tanggal_keluar',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_masuk'   => 'date',
        'tanggal_keluar'  => 'date',
    ];

    // =====================================================
    // RELASI
    // =====================================================

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    // =====================================================
    // SCOPES
    // =====================================================

    /**
     * Scope: yang masih aktif (belum keluar)
     */
    public function scopeAktif($query)
    {
        return $query->whereNull('tanggal_keluar');
    }

    /**
     * Scope: berdasarkan tahun ajaran
     */
    public function scopeByTahunAjaran($query, int $tahunAjaranId)
    {
        return $query->where('tahun_ajaran_id', $tahunAjaranId);
    }

    // =====================================================
    // ACCESSORS
    // =====================================================

    /**
     * Apakah santri masih aktif di kelas ini
     */
    public function getIsAktifAttribute(): bool
    {
        return is_null($this->tanggal_keluar);
    }
}