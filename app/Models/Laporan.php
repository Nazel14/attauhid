<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Laporan extends Model
{
    protected $table = 'laporan';

    protected $fillable = [
        'reportable_type', 'reportable_id',
        'pelapor_id', 'penerima_id',
        'kategori', 'status', 'snapshot', 'selesai_at', 'tutup_otomatis',
        'dibisukan_at', 'dibisukan_oleh',
    ];

    protected $casts = [
        'snapshot'       => 'array',
        'selesai_at'     => 'datetime',
        'tutup_otomatis' => 'boolean',
        'dibisukan_at'   => 'datetime',
    ];

    public const KATEGORI = [
        // setoran
        'halaman_salah' => 'Halaman Salah',
        'nilai_salah'   => 'Nilai Salah',
        'tanggal_salah' => 'Tanggal Salah',
        // presensi
        'status_presensi_salah' => 'Status Presensi Salah',
        'sesi_salah'    => 'Sesi Salah',
        // data diri santri
        'nama_salah'    => 'Nama Salah',
        'gender_salah'  => 'Jenis Kelamin Salah',
        'nis_salah'     => 'NIS Salah',
        'kelas_salah'   => 'Kelas Salah',
        // data diri guru (guru → admin)
        'nip_salah'      => 'NIP Salah',
        'username_salah' => 'Username Salah',
        // foto profil (santri → guru, guru → admin)
        'foto_salah'     => 'Foto Profil Salah',
        // guru→admin & admin→guru
        'data_master'   => 'Data Master',
        'koordinasi'    => 'Koordinasi',
        // guru→santri
        'pengingat_setoran'  => 'Pengingat Setoran',
        'pengingat_murajaah' => 'Pengingat Murajaah',
        'teguran_presensi'   => 'Teguran Presensi',
        'klarifikasi_data'   => 'Klarifikasi Data',
        'lainnya'       => 'Lainnya',
    ];

    /* ================= Relasi ================= */

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pelapor_id');
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penerima_id');
    }

    public function pesan(): HasMany
    {
        return $this->hasMany(LaporanPesan::class, 'laporan_id');
    }

    /* ================= Helper ================= */

    public function isSelesai(): bool
    {
        return $this->status === 'selesai';
    }

    /** Thread dikunci karena dibisukan? */
    public function isDibisukan(): bool
    {
        return $this->dibisukan_at !== null;
    }

    /** Thread masih bisa menerima pesan baru? */
    public function terkunci(): bool
    {
        return $this->isSelesai() || $this->isDibisukan();
    }

    /** User ini terlibat di laporan (pelapor / penerima)? */
    public function melibatkan(User $user): bool
    {
        return $this->pelapor_id === $user->id || $this->penerima_id === $user->id;
    }

    /** Jumlah pesan belum dibaca oleh $user pada laporan ini */
    public function unreadFor(User $user): int
    {
        return $this->pesan()
            ->whereNull('read_at')
            ->whereNull('dihapus_at')
            ->where('user_id', '!=', $user->id)
            ->count();
    }

    /** Total unread di semua laporan yang melibatkan $user (untuk badge sidebar) */
    public static function totalUnreadFor(User $user): int
    {
        return LaporanPesan::whereNull('read_at')
            ->whereNull('dihapus_at')
            ->where('user_id', '!=', $user->id)
            ->whereHas('laporan', function ($q) use ($user) {
                // Thread yang dibisukan sengaja tidak dihitung — itulah inti
                // "bisukan": berhenti menagih perhatian di badge sidebar.
                $q->whereNull('dibisukan_at')
                  ->where(function ($w) use ($user) {
                      $w->where('pelapor_id', $user->id)
                        ->orWhere('penerima_id', $user->id);
                  });
            })
            ->count();
    }
}