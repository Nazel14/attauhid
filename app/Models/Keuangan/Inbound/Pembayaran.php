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
        'receipt_number', 'student_id', 'invoice_id', 'kategori', 'sumber', 'nama_pihak',
        'account_id', 'nominal', 'payment_date', 'payment_method', 'catatan',
        'status_approval', 'diperiksa_by', 'disetujui_by',
        'diperiksa_at', 'disetujui_at', 'created_by',
        'ditolak_by', 'ditolak_at', 'alasan_tolak',
    ];

    protected $casts = [
        'nominal'      => 'decimal:2',
        'payment_date' => 'date',
        'diperiksa_at' => 'datetime',
        'disetujui_at' => 'datetime',
        'ditolak_at'   => 'datetime',
    ];

    /**
     * Asal catatan pembayaran.
     *
     * 'bendahara' = dicatat langsung di panel keuangan (jalur lama).
     * 'wali'      = dikirim orang tua dari panel santri lewat unggah bukti.
     *
     * Keduanya melewati tahap persetujuan yang sama persis; kiriman wali
     * tidak pernah langsung sah. Penanda ini hanya supaya bendahara tahu
     * asalnya dan supaya wali hanya bisa membatalkan kirimannya sendiri.
     */
    public const SUMBER_BENDAHARA = 'bendahara';
    public const SUMBER_WALI      = 'wali';

    /** Status akhir untuk kiriman yang ditolak bendahara. */
    public const STATUS_DITOLAK = 'ditolak';

    /**
     * Urutan di sini = urutan tab di halaman Pembayaran.
     *
     * 'infaq_arahan' adalah infaq sukarela santri Rumah Quran: santrinya
     * dipilih dari daftar, nominalnya bebas, dan tidak pernah terikat
     * pada tagihan — jadi tidak ada sisa dan tidak ada tunggakan.
     */
    public const KATEGORI = [
        'tagihan'      => 'Pembayaran Tagihan',
        'infaq_arahan' => 'Infaq Rumah Quran',
        'uang_masuk'   => 'Uang Masuk',
        'koperasi'     => 'Koperasi',
        'donatur'      => 'Donatur',
        'lainnya'      => 'Lainnya',
    ];

    /** Prefix nomor kwitansi per kategori. */
    public const PREFIX = [
        'tagihan'      => 'RCP',
        'infaq_arahan' => 'INF',
        'uang_masuk'   => 'UM',
        'koperasi'     => 'KOP',
        'donatur'      => 'DON',
        'lainnya'      => 'TRX',
    ];

    /**
     * withTrashed WAJIB di sini.
     *
     * getPihakAttribute() di bawah memakai $this->siswa?->name — kalau
     * relasinya null, kwitansi TIDAK error melainkan mencetak "—" sebagai
     * nama penyetor. Gagalnya tidak bersuara, jadi baru ketahuan saat
     * kwitansi lama dicetak ulang berbulan-bulan kemudian.
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'student_id')->withTrashed();
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

    public function penolak(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditolak_by');
    }

    public function getDariWaliAttribute(): bool
    {
        return $this->sumber === self::SUMBER_WALI;
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