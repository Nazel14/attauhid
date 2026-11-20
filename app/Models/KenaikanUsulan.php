<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KenaikanUsulan extends Model
{
    protected $table = 'kenaikan_usulans';

    protected $fillable = [
        'santri_id',
        'tahun_ajaran_id',
        'guru_id',
        'status',
        'catatan',
    ];

    /**
     * withTrashed: usulan kenaikan yang belum diproses tetap menampilkan
     * nama santrinya walau santri itu sudah diarsipkan — tanpa ini barisnya
     * muncul kosong di daftar usulan dan tidak bisa ditutup.
     */
    public function santri()
    {
        return $this->belongsTo(Santri::class)->withTrashed();
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }
}