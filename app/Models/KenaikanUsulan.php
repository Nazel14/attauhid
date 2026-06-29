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

    public function santri()
    {
        return $this->belongsTo(Santri::class);
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