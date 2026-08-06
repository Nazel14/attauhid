<?php

namespace App\Models\Keuangan\Inbound;

use App\Models\Keuangan\MasterData\Rekening;
use App\Models\Keuangan\MasterData\Siswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Pembayaran extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $table = 'inbound_payments';

    protected $fillable = [
        'receipt_number', 'student_id', 'invoice_id', 'kategori', 'nama_pihak',
        'account_id', 'nominal', 'payment_date', 'payment_method', 'catatan',
        'status_approval', 'diperiksa_by', 'disetujui_by',
        'diperiksa_at', 'disetujui_at', 'created_by',
    ];

    protected $casts = [
        'nominal'      => 'decimal:2',
        'payment_date' => 'date',
        'diperiksa_at' => 'datetime',
        'disetujui_at' => 'datetime',
    ];

    public const KATEGORI = [
        'tagihan'    => 'Pembayaran Tagihan',
        'uang_masuk' => 'Uang Masuk',
        'koperasi'   => 'Koperasi',
        'donatur'    => 'Donatur',
        'lainnya'    => 'Lainnya',
    ];

    /** Prefix nomor kwitansi per kategori. */
    public const PREFIX = [
        'tagihan'    => 'RCP',
        'uang_masuk' => 'UM',
        'koperasi'   => 'KOP',
        'donatur'    => 'DON',
        'lainnya'    => 'TRX',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'student_id');
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class, 'invoice_id');
    }

    public function rekening(): BelongsTo
    {
        return $this->belongsTo(Rekening::class, 'account_id');
    }

    public function pemeriksa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diperiksa_by');
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_by');
    }

    // Bukan createdBy: Laravel men-snake_case nama relasi saat serialisasi,
    // sehingga `createdBy` jadi `created_by` dan menimpa kolom integer.
    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function registerMediaCollections(): void
    {
        // Disk 'local' = storage/app, tidak bisa diakses publik.
        // Penyajiannya lewat MediaController yang mengecek permission.
        $this->addMediaCollection('bukti_pembayaran')->useDisk('local');
    }

    /** Label pihak yang membayar, apa pun kategorinya. */
    public function getPihakAttribute(): string
    {
        return $this->siswa?->name
            ?: ($this->nama_pihak ?: '—');
    }
}