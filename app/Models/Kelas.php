<?php

namespace App\Models;

use App\Models\Keuangan\MasterData\Siswa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tabel `kelas` adalah master rombel untuk KEDUA modul.
 *
 * Penting: satu baris = satu rombel PADA SATU TAHUN AJARAN.
 * "Kelas 7A 2026/2027" dan "Kelas 7A 2027/2028" adalah dua baris berbeda,
 * karena wali kelas dan daftar santrinya memang berganti tiap tahun.
 * Konsekuensinya, kelas harus dibuat ulang setiap tahun ajaran baru.
 *
 * Kolom `lembaga` memisahkan TK At-Tauhid dari Rumah Quran (tempat ngaji).
 * Siswa ikut lembaga kelasnya: TK ditagih lewat gelombang tagihan,
 * santri Rumah Quran membayar infaq sukarela tanpa tagihan.
 *
 * Tabel `classes` sudah tidak dipakai.
 */
class Kelas extends Model
{
    use HasFactory;

    protected $table = 'kelas';

    protected $fillable = [
        'name',
        'tahun_ajaran',
        'guru_id',
        'gender_restriction',
        'tingkat',
        'lembaga',
        'kapasitas',
        'is_active',
    ];

    protected $casts = [
        'kapasitas' => 'integer',
        'is_active' => 'boolean',
    ];

    const GENDER_RESTRICTIONS = [
        'ikhwan'   => 'Ikhwan (Laki-laki)',
        'akhwat'   => 'Akhwat (Perempuan)',
        'campuran' => 'Campuran (Ikhwan & Akhwat)',
    ];

    public const LEMBAGA_TK     = 'tk';
    public const LEMBAGA_ARAHAN = 'arahan';

    public const LEMBAGA = [
        self::LEMBAGA_TK     => 'TK At-Tauhid',
        self::LEMBAGA_ARAHAN => 'Rumah Quran',
    ];

    /** Wali kelas */
    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }

    /**
     * Santri di kelas ini (modul raport).
     *
     * FK disebut eksplisit: subclass keuangan mewarisi method ini, dan
     * tebakan otomatis Laravel mengikuti nama class konkretnya. Selama
     * subclass masih bernama `Kelas` tebakannya kebetulan benar, tapi
     * begitu di-rename relasinya diam-diam kosong tanpa error.
     */
    public function santris(): HasMany
    {
        return $this->hasMany(Santri::class, 'kelas_id');
    }

    /**
     * Alias untuk modul keuangan. Sejak `students` dilebur ke `santris`,
     * relasi ini identik dengan santris() — FK-nya kelas_id, bukan class_id.
     * Dipertahankan supaya kode keuangan yang memanggil `siswas` tetap jalan.
     */
    public function siswas(): HasMany
    {
        return $this->hasMany(Siswa::class, 'kelas_id');
    }

    /** Pegawai yang mengampu kelas ini — kini lewat guru_id, bukan class_id */
    public function pegawais(): HasMany
    {
        return $this->hasMany(Guru::class, 'id', 'guru_id');
    }

    public function scopeTk($query)
    {
        return $query->where('lembaga', self::LEMBAGA_TK);
    }

    public function scopeArahan($query)
    {
        return $query->where('lembaga', self::LEMBAGA_ARAHAN);
    }

    public function isArahan(): bool
    {
        return $this->lembaga === self::LEMBAGA_ARAHAN;
    }

    public function hasGenderMismatch()
    {
        if ($this->gender_restriction === 'campuran') {
            return false;
        }

        $allowedGender = $this->gender_restriction === 'ikhwan' ? 'Laki-laki' : 'Perempuan';

        return $this->santris()->where('gender', '!=', $allowedGender)->exists();
    }

    public function getMismatchedSantris()
    {
        if ($this->gender_restriction === 'campuran') {
            return collect();
        }

        $allowedGender = $this->gender_restriction === 'ikhwan' ? 'Laki-laki' : 'Perempuan';

        return $this->santris()->where('gender', '!=', $allowedGender)->get();
    }
}