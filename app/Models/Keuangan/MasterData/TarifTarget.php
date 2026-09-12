<?php

namespace App\Models\Keuangan\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TarifTarget extends Model
{
    protected $table = 'tariff_targets';

    protected $fillable = ['tariff_id', 'target_type', 'target_value'];

    /** Dimensi yang boleh dipakai → kolom di tabel santris. */
    public const DIMENSIONS = [
        'kelas'        => 'kelas_id',
        'angkatan'     => 'angkatan',
        'student_type' => 'student_type',
        'gender'       => 'gender',
    ];

    public const LABELS = [
        'kelas'        => 'Kelas',
        'angkatan'     => 'Angkatan',
        'student_type' => 'Tipe Siswa',
        'gender'       => 'Gender',
    ];

    public function tarif(): BelongsTo
    {
        return $this->belongsTo(Tarif::class, 'tariff_id');
    }
}