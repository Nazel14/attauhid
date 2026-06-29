<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KelasNotifikasi extends Model
{
    protected $table = 'kelas_notifikasi';

    protected $fillable = [
        'guru_id', 'kelas_id', 'tipe', 'judul', 'pesan', 'data', 'dibaca_pada',
    ];

    protected $casts = [
        'data'        => 'array',
        'dibaca_pada' => 'datetime',
    ];

    public function guru()  { return $this->belongsTo(Guru::class); }
    public function kelas() { return $this->belongsTo(Kelas::class); }

    public function scopeBelumDibaca($q) { return $q->whereNull('dibaca_pada'); }
}