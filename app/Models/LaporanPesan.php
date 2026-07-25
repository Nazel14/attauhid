<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanPesan extends Model
{
    protected $table = 'laporan_pesan';

    protected $fillable = ['laporan_id', 'user_id', 'isi', 'read_at', 'edited_at', 'dihapus_at'];

    protected $casts = [
        'read_at'    => 'datetime',
        'edited_at'  => 'datetime',
        'dihapus_at' => 'datetime',
    ];

    /** Batas waktu pesan masih boleh disunting (menit). */
    public const BATAS_EDIT_MENIT = 360;

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(Laporan::class, 'laporan_id');
    }

    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /* ================= Helper ================= */

    public function isDihapus(): bool
    {
        return $this->dihapus_at !== null;
    }

    /** Masih dalam jendela waktu edit? */
    public function masihBisaDiedit(): bool
    {
        return ! $this->isDihapus()
            && $this->created_at->diffInMinutes(now()) < self::BATAS_EDIT_MENIT;
    }
}