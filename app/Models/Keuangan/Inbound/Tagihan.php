<?php

namespace App\Models\Keuangan\Inbound;

use App\Models\Keuangan\MasterData\Siswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tagihan extends Model
{
    use SoftDeletes;

    protected $table = 'invoices';

    /**
     * Alasan pembatalan tagihan.
     *
     * Berkategori, bukan tulisan bebas, karena angkanya perlu dijumlahkan.
     * "Berapa yang kita bebaskan tahun ini?" adalah pertanyaan yang akan
     * ditanyakan yayasan, dan dengan tulisan bebas jawabannya tidak pernah
     * bisa dihitung.
     */
    public const ALASAN_BATAL = [
        'salah_input'   => 'Salah input',
        'santri_keluar' => 'Santri keluar atau pindah',
        'keringanan'    => 'Keringanan / dibebaskan',
        'lainnya'       => 'Lainnya',
    ];

    // 'period' dan 'due_date' dari versi asli dibuang — kolomnya tidak ada
    // di tabel invoices, keduanya hidup di invoice_headers.
    protected $fillable = [
        'invoice_number', 'invoice_header_id', 'student_id',
        'total_amount', 'remaining_balance', 'status',
        'cancel_reason', 'cancel_note', 'cancelled_by', 'cancelled_at',
    ];

    protected $casts = [
        'total_amount'      => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'cancelled_at'      => 'datetime',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(InvoiceHeader::class, 'invoice_header_id');
    }

    /**
     * withTrashed: siswa yang sudah diarsipkan tetap terbaca namanya di
     * tagihan lama. Tanpa ini, relasinya mengembalikan null dan tagihan
     * bulan-bulan sebelumnya kehilangan pemiliknya di laporan — tanpa
     * pesan error apa pun, hanya kolom yang mendadak kosong.
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'student_id')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(ItemTagihan::class, 'invoice_id');
    }

    // Versi asli punya pembayaran() DAN pembayarans() yang isinya identik.
    // Disatukan jadi satu.
    public function pembayarans(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'invoice_id');
    }

    public function riwayatPenagihans(): HasMany
    {
        return $this->hasMany(RiwayatPenagihan::class, 'invoice_id');
    }

    public function pembatal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /** Nominal yang sudah masuk dan disetujui. */
    public function getTerbayarAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->remaining_balance;
    }

    /** Label alasan pembatalan untuk ditampilkan apa adanya di UI. */
    public function getAlasanBatalAttribute(): ?string
    {
        return self::ALASAN_BATAL[$this->cancel_reason] ?? null;
    }

    public function getDibatalkanAttribute(): bool
    {
        return $this->status === 'Cancelled';
    }
}