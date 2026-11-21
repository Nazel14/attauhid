<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Keuangan\Inbound\PembayaranController;
use App\Models\Keuangan\Inbound\Pembayaran;
use App\Models\Keuangan\Inbound\Tagihan;
use App\Models\Keuangan\MasterData\Rekening;
use App\Models\Santri;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Halaman Keuangan di portal wali/santri.
 *
 * Satu-satunya yang bisa DITULIS wali di sini adalah konfirmasi pembayaran
 * (unggah bukti transfer). Kirimannya selalu masuk sebagai draft dan baru
 * sah setelah melewati tahap persetujuan bendahara yang sama dengan
 * pembayaran yang dicatat bendahara sendiri.
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
     * Batas kiriman wali yang masih menunggu per santri. Cukup longgar untuk
     * membayar beberapa tagihan sekaligus, cukup ketat supaya bendahara
     * tidak dibanjiri kiriman ganda dari tombol yang ditekan berkali-kali.
     */
    private const MAKS_MENUNGGU = 3;

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
                'invoice_id'     => $p->invoice_id,
                // Untuk pembayaran tagihan: tagihan mana yang dilunasi.
                'untuk'          => $p->tagihan?->header?->title ?? $p->tagihan?->invoice_number,
                'catatan'        => $p->catatan,
                // Selama belum 'disetujui', pembayaran belum sah di mata
                // pembukuan. Wali harus melihat itu apa adanya, supaya tidak
                // merasa lunas atas setoran yang belum disahkan bendahara.
                'sah'            => $p->status_approval === self::APPROVAL_SAH,
                'ditolak'        => $p->status_approval === Pembayaran::STATUS_DITOLAK,
                'alasan_tolak'   => $p->status_approval === Pembayaran::STATUS_DITOLAK ? $p->alasan_tolak : null,
                'dari_wali'      => $p->sumber === Pembayaran::SUMBER_WALI,
                // Wali hanya boleh membatalkan kirimannya sendiri, dan hanya
                // selama bendahara belum mulai memeriksanya.
                'bisa_batal'     => $p->sumber === Pembayaran::SUMBER_WALI && $p->status_approval === 'draft',
                'status'         => match ($p->status_approval) {
                    self::APPROVAL_SAH         => 'Diterima',
                    Pembayaran::STATUS_DITOLAK => 'Ditolak',
                    default                    => 'Menunggu verifikasi',
                },
            ]);

        // "Menunggu" = belum sah DAN belum ditolak. Kiriman yang ditolak
        // sudah selesai diproses — tidak boleh ikut dihitung menunggu.
        $menunggu = $pembayaran->where('sah', false)->where('ditolak', false)->count();

        $sah = $pembayaran->where('sah', true);

        // Nominal yang sudah dikirim untuk tiap tagihan tapi belum disahkan.
        // Ditampilkan di form konfirmasi agar wali tidak mentransfer dua kali
        // untuk tagihan yang sama.
        $tertunda = $pembayaran->where('sah', false)->where('ditolak', false)
            ->whereNotNull('invoice_id')
            ->groupBy('invoice_id')
            ->map(fn ($g) => (float) $g->sum('nominal'));

        $tagihan = $tagihan->map(fn ($t) => $t + ['menunggu' => $tertunda[$t['id']] ?? 0.0]);

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
            'menunggu_verifikasi' => $menunggu,
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
            'menunggu'       => $menunggu,
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
     * Wali mengirim bukti transfer.
     *
     * Hasilnya SELALU pembayaran berstatus draft dengan sumber 'wali'.
     * Sisa tagihan dan saldo rekening baru berubah ketika bendahara
     * menyetujuinya lewat PembayaranController::approve() — gerbang yang
     * sama dengan pembayaran yang dicatat bendahara sendiri.
     */
    public function kirimKonfirmasi(Request $request)
    {
        $santri = $this->resolveSantri(Auth::user());
        abort_unless($santri, 403, 'Data santri tidak ditemukan.');

        $isRq = $santri->isArahan();

        $menunggu = Pembayaran::where('student_id', $santri->id)
            ->where('sumber', Pembayaran::SUMBER_WALI)
            ->whereIn('status_approval', ['draft', 'diperiksa'])
            ->count();

        if ($menunggu >= self::MAKS_MENUNGGU) {
            return redirect()->back()->withErrors([
                'konfirmasi' => 'Masih ada ' . $menunggu . ' kiriman yang menunggu diperiksa bendahara. '
                    . 'Tunggu sampai diproses dulu sebelum mengirim lagi.',
            ]);
        }

        // Rekening tujuan hanya yang memang diumumkan ke wali.
        // Kas tunai tidak bisa ditransfer, jadi tidak boleh dipilih wali.
        $rekeningPublik = Rekening::publik()->where('type', '!=', 'Tunai')->pluck('id')->all();

        $aturan = [
            'account_id'   => ['required', Rule::in($rekeningPublik)],
            'nominal'      => 'required|numeric|min:1000|max:100000000',
            'payment_date' => 'required|date|before_or_equal:today|after:' . now()->subYear()->toDateString(),
            'bukti'        => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'catatan'      => 'nullable|string|max:255',
        ];

        // Santri TK membayar tagihan tertentu; santri Rumah Quran berinfaq
        // bebas tanpa tagihan.
        if (! $isRq) {
            $aturan['invoice_id'] = ['required', 'integer'];
        }

        $data = $request->validate($aturan, [
            'account_id.required'      => 'Pilih rekening tujuan transfer.',
            'account_id.in'            => 'Rekening tujuan tidak dikenali.',
            'nominal.required'         => 'Nominal wajib diisi.',
            'nominal.min'              => 'Nominal minimal Rp 1.000.',
            'payment_date.required'    => 'Tanggal transfer wajib diisi.',
            'payment_date.before_or_equal' => 'Tanggal transfer tidak boleh di masa depan.',
            'payment_date.after'       => 'Tanggal transfer terlalu lama. Hubungi bendahara langsung.',
            'bukti.required'           => 'Foto atau file bukti transfer wajib diunggah.',
            'bukti.mimes'              => 'Bukti harus berupa JPG, PNG, atau PDF.',
            'bukti.max'                => 'Ukuran bukti maksimal 2 MB.',
            'invoice_id.required'      => 'Pilih tagihan yang dibayar.',
        ]);

        $tagihan = null;

        if (! $isRq) {
            // Tagihan WAJIB milik santri ini dan masih ditagih. id-nya datang
            // dari browser, jadi tidak boleh dipercaya begitu saja.
            $tagihan = Tagihan::where('id', $data['invoice_id'])
                ->where('student_id', $santri->id)
                ->whereIn('status', ['Unpaid', 'Installment'])
                ->where(function ($q) {
                    $q->whereDoesntHave('header')
                      ->orWhereHas('header', fn ($h) => $h->whereIn('status', ['Published', 'Closed']));
                })
                ->first();

            if (! $tagihan) {
                return redirect()->back()->withErrors(['invoice_id' => 'Tagihan tidak ditemukan atau sudah lunas.']);
            }

            // Pembayaran lain untuk tagihan ini yang belum disahkan ikut
            // diperhitungkan — kalau tidak, dua kiriman bisa sama-sama lolos
            // dan baru tertolak saat bendahara menyetujui yang kedua.
            $sedangDiproses = (float) Pembayaran::where('invoice_id', $tagihan->id)
                ->whereIn('status_approval', ['draft', 'diperiksa'])
                ->sum('nominal');

            $bolehDibayar = max(0, (float) $tagihan->remaining_balance - $sedangDiproses);

            if ((float) $data['nominal'] > $bolehDibayar + 0.001) {
                $pesan = $sedangDiproses > 0
                    ? 'Nominal melebihi sisa tagihan. Sisa Rp ' . $this->rupiah($tagihan->remaining_balance)
                        . ', dan Rp ' . $this->rupiah($sedangDiproses) . ' di antaranya sedang diperiksa bendahara.'
                    : 'Nominal melebihi sisa tagihan (Rp ' . $this->rupiah($tagihan->remaining_balance) . ').';

                return redirect()->back()->withErrors(['nominal' => $pesan]);
            }
        }

        $kategori = $isRq ? 'infaq_arahan' : 'tagihan';

        DB::transaction(function () use ($request, $data, $santri, $tagihan, $kategori) {
            $pembayaran = Pembayaran::create([
                'receipt_number'  => Pembayaran::PREFIX[$kategori] . '-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
                'kategori'        => $kategori,
                'sumber'          => Pembayaran::SUMBER_WALI,
                'student_id'      => $santri->id,
                'invoice_id'      => $tagihan?->id,
                'account_id'      => $data['account_id'],
                'nominal'         => $data['nominal'],
                'payment_date'    => $data['payment_date'],
                // Wali hanya bisa transfer; bayar tunai tetap dicatat bendahara.
                'payment_method'  => 'Transfer Bank',
                'catatan'         => $data['catatan'] ?? null,
                'status_approval' => 'draft',
                'created_by'      => Auth::id(),
            ]);

            $file = $request->file('bukti');

            $pembayaran->addMedia($file)
                ->usingFileName(time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension())
                ->toMediaCollection('bukti_pembayaran');
        });

        return redirect()->back()->with('success',
            'Bukti transfer terkirim. Bendahara akan memeriksanya — status berubah menjadi "Diterima" setelah disahkan.');
    }

    private function rupiah($angka): string
    {
        return number_format((float) $angka, 0, ',', '.');
    }

    /** Wali menarik kembali kirimannya sendiri, selama belum diperiksa. */
    public function batalkanKonfirmasi(Pembayaran $pembayaran)
    {
        $santri = $this->resolveSantri(Auth::user());

        abort_if(! $santri || $pembayaran->student_id !== $santri->id, 403);
        abort_unless($pembayaran->sumber === Pembayaran::SUMBER_WALI, 403,
            'Pembayaran ini dicatat bendahara, jadi tidak bisa dibatalkan dari sini.');

        if ($pembayaran->status_approval !== 'draft') {
            return redirect()->back()->withErrors([
                'konfirmasi' => 'Kiriman ini sudah mulai diperiksa bendahara, jadi tidak bisa dibatalkan lagi.',
            ]);
        }

        $pembayaran->clearMediaCollection('bukti_pembayaran');
        $pembayaran->delete();

        return redirect()->back()->with('success', 'Kiriman bukti transfer dibatalkan.');
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