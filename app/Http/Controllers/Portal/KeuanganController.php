<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Keuangan\Inbound\PembayaranController;
use App\Models\Keuangan\Inbound\Pembayaran;
use App\Models\Keuangan\Inbound\Tagihan;
use App\Models\Keuangan\MasterData\Rekening;
use App\Models\Santri;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Halaman Keuangan di portal wali/santri. Read-only.
 *
 * Dua aturan yang tidak boleh dilanggar di file ini:
 *
 *  1. Semua query diturunkan dari objek santri hasil resolveSantri(),
 *     tidak pernah dari input request. Tanpa itu, wali bisa membaca
 *     tagihan santri lain hanya dengan menukar id di URL.
 *
 *  2. Tagihan yang header-nya masih 'Draft' tidak boleh tampil.
 *     Bendahara menyusunnya dulu sebelum diumumkan — memperlihatkan
 *     draft berarti wali menagih dirinya sendiri atas angka yang
 *     belum final.
 */
class KeuanganController extends Controller
{
    /** Status pembayaran yang boleh dianggap sah. */
    private const APPROVAL_SAH = 'disetujui';

    /**
     * Santri milik user yang login. Sama persis dengan SantriController —
     * sengaja diduplikasi agar portal keuangan tetap hidup meski modul
     * raport dimatikan dan controller itu tidak ikut termuat.
     */
    private function resolveSantri(\App\Models\User $user): ?Santri
    {
        return $user->santri()->with(['kelas'])->first()
            ?? Santri::where('user_id', $user->id)->with(['kelas'])->first();
    }

    public function index()
    {
        $santri = $this->resolveSantri(Auth::user());

        if (! $santri) {
            return Inertia::render('Santri/Keuangan/Index', [
                'santri'    => null,
                'lembaga'   => 'tk',
                'ringkasan' => null,
                'infaq'     => null,
                'tagihan'   => [],
                'pembayaran'=> [],
                'message'   => 'Data santri tidak ditemukan. Silakan hubungi administrator.',
            ]);
        }

        // Santri Rumah Quran membayar infaq sukarela — tidak pernah ditagih.
        // Halamannya berubah jadi riwayat infaq, tanpa bagian tagihan sama
        // sekali, supaya wali tidak mengira ada kewajiban yang belum dibayar.
        $isRq = $santri->isArahan();

        /* ---------- TAGIHAN ---------- */
        // invoice_header_id nullable: tagihan yang dibuat manual tidak punya
        // header, jadi tidak ada tahap Draft untuk disembunyikan.
        $tagihan = $isRq ? collect() : Tagihan::where('student_id', $santri->id)
            ->where(function ($q) {
                $q->whereDoesntHave('header')
                  ->orWhereHas('header', fn ($h) => $h->whereIn('status', ['Published', 'Closed']));
            })
            ->with(['header:id,title,period,due_date,status', 'items:id,invoice_id,deskripsi,nominal'])
            ->orderByDesc('id')
            ->get()
            ->map(function (Tagihan $t) {
                $jatuhTempo = $t->header?->due_date;
                // Tagihan batal sudah bukan kewajiban — tidak boleh dihitung
                // menunggak, dan tidak boleh tampil "lewat tempo".
                $masihDitagih = in_array($t->status, ['Unpaid', 'Installment'], true);

                return [
                    'id'             => $t->id,
                    'nomor'          => $t->invoice_number,
                    'judul'          => $t->header?->title ?? 'Tagihan',
                    'periode'        => $t->header?->period,
                    'jatuh_tempo'    => $jatuhTempo?->format('Y-m-d'),
                    'total'          => (float) $t->total_amount,
                    'sisa'           => (float) $t->remaining_balance,
                    'terbayar'       => $t->status === 'Cancelled'
                        ? 0.0
                        : (float) $t->total_amount - (float) $t->remaining_balance,
                    'status'         => $t->status, // Unpaid | Installment | Paid | Cancelled
                    'alasan_batal'   => $t->status === 'Cancelled' ? $t->alasan_batal : null,
                    'lewat_tempo'    => $masihDitagih && $jatuhTempo && $jatuhTempo->isPast(),
                    'rincian'        => $t->items->map(fn ($i) => [
                        'deskripsi' => $i->deskripsi,
                        'nominal'   => (float) $i->nominal,
                    ])->values(),
                ];
            });

        /* ---------- PEMBAYARAN / INFAQ ---------- */
        // Difilter student_id, bukan kategori: pemasukan donatur dan koperasi
        // yang tidak terkait santri ini memang tidak akan ikut terjaring.
        $pembayaran = Pembayaran::where('student_id', $santri->id)
            ->with(['tagihan:id,invoice_number,invoice_header_id', 'tagihan.header:id,title'])
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Pembayaran $p) => [
                'id'             => $p->id,
                'nomor'          => $p->receipt_number,
                'tanggal'        => $p->payment_date?->format('Y-m-d'),
                'nominal'        => (float) $p->nominal,
                'metode'         => $p->payment_method,
                'kategori'       => $p->kategori,
                'kategori_label' => Pembayaran::KATEGORI[$p->kategori] ?? 'Pembayaran',
                // Untuk pembayaran tagihan: tagihan mana yang dilunasi.
                'untuk'          => $p->tagihan?->header?->title ?? $p->tagihan?->invoice_number,
                'catatan'        => $p->catatan,
                // Selama belum 'disetujui', pembayaran belum sah di mata
                // pembukuan. Wali harus melihat itu apa adanya, supaya tidak
                // merasa lunas atas setoran yang belum disahkan bendahara.
                'sah'            => $p->status_approval === self::APPROVAL_SAH,
                'status'         => $p->status_approval === self::APPROVAL_SAH
                    ? 'Diterima'
                    : 'Menunggu verifikasi',
            ]);

        $sah = $pembayaran->where('sah', true);

        /* ---------- RINGKASAN TAGIHAN (TK) ---------- */
        $belumLunas = $tagihan->whereIn('status', ['Unpaid', 'Installment']);

        $terdekat = $belumLunas
            ->filter(fn ($t) => $t['jatuh_tempo'])
            ->sortBy('jatuh_tempo')
            ->first();

        $ringkasan = [
            'total_tunggakan'     => (float) $belumLunas->sum('sisa'),
            'jumlah_belum'        => $belumLunas->count(),
            'jumlah_lewat'        => $belumLunas->where('lewat_tempo', true)->count(),
            'jumlah_lunas'        => $tagihan->where('status', 'Paid')->count(),
            'total_dibayar'       => (float) $sah->sum('nominal'),
            'menunggu_verifikasi' => $pembayaran->where('sah', false)->count(),
            'tagihan_terdekat'    => $terdekat ? [
                'judul'       => $terdekat['judul'],
                'sisa'        => $terdekat['sisa'],
                'jatuh_tempo' => $terdekat['jatuh_tempo'],
            ] : null,
        ];

        /* ---------- RINGKASAN INFAQ (Rumah Quran) ---------- */
        // Hanya yang sudah disahkan bendahara yang dihitung — infaq yang
        // masih menunggu verifikasi tampil di riwayat, tapi belum dijumlah.
        $infaqSah = $sah->where('kategori', 'infaq_arahan');
        $tahunIni = (string) now()->year;
        $terakhir = $infaqSah->first(); // sudah urut tanggal terbaru

        $infaq = $isRq ? [
            'total_semua'    => (float) $infaqSah->sum('nominal'),
            'total_tahun'    => (float) $infaqSah->filter(fn ($p) => str_starts_with((string) $p['tanggal'], $tahunIni))->sum('nominal'),
            'jumlah'         => $infaqSah->count(),
            'tahun'          => (int) $tahunIni,
            'terakhir'       => $terakhir ? ['tanggal' => $terakhir['tanggal'], 'nominal' => $terakhir['nominal']] : null,
            'menunggu'       => $pembayaran->where('sah', false)->count(),
        ] : null;

        /* ---------- CARA BAYAR ---------- */
        // Hanya rekening yang ditandai publik. Kolom `balance` sengaja tidak
        // ikut diambil — wali tidak perlu tahu isi kas pesantren.
        $rekening = Rekening::publik()
            ->orderByRaw("FIELD(type, 'E-Wallet', 'Bank', 'Tunai')")
            ->get(['id', 'account_name', 'type', 'account_number', 'account_holder', 'qris_path', 'catatan_bayar'])
            ->map(fn (Rekening $r) => [
                'id'      => $r->id,
                'nama'    => $r->account_name,
                'tipe'    => $r->type,
                'nomor'   => filled(trim((string) $r->account_number, " -–—")) ? $r->account_number : null,
                'pemilik' => $r->account_holder,
                'qris'    => $r->qris_path ? '/storage/' . $r->qris_path : null,
                'catatan' => $r->catatan_bayar,
            ]);

        return Inertia::render('Santri/Keuangan/Index', [
            'santri' => [
                'id'     => $santri->id,
                'name'   => $santri->name,
                'nis'    => $santri->nis,
                'gender' => $santri->gender,
                // Kolomnya `name`, bukan `nama_kelas` — tabel `classes`
                // yang memakai nama itu sudah tidak dipakai.
                'kelas'  => $santri->kelas?->name,
            ],
            'lembaga'    => $isRq ? 'arahan' : 'tk',
            'ringkasan'  => $ringkasan,
            'infaq'      => $infaq,
            'tagihan'    => $tagihan->values(),
            'pembayaran' => $pembayaran->values(),
            'rekening'   => $rekening->values(),
            'waAdmin'    => config('modules.wa_admin'),
        ]);
    }

    /**
     * Kuitansi. Kepemilikan diperiksa di sini karena route portal tidak
     * lewat gerbang permission keuangan — tanpa pemeriksaan ini, wali bisa
     * mengunduh kuitansi santri lain hanya dengan menebak id.
     */
    public function printKwitansi(Pembayaran $pembayaran)
    {
        $santri = $this->resolveSantri(Auth::user());

        abort_if(! $santri || $pembayaran->student_id !== $santri->id, 403);
        abort_if($pembayaran->status_approval !== self::APPROVAL_SAH, 403,
            'Kuitansi terbit setelah pembayaran disahkan bendahara.');

        return app(PembayaranController::class)->printKwitansi($pembayaran);
    }
}