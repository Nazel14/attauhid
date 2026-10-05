<?php

namespace App\Http\Controllers\Keuangan\Inbound;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\Inbound\InvoiceHeader;
use App\Models\Keuangan\Inbound\Tagihan;
use App\Models\Keuangan\MasterData\Siswa;
use App\Models\Keuangan\MasterData\Tarif;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TagihanController extends Controller
{
    public function index(Request $request)
    {
        /* ---------- Tabel atas: batch ---------- */
        $headerQuery = InvoiceHeader::with('tarif:id,billing_name,nominal');

        // orWhere dibungkus closure — tanpa ini filter bulan di bawahnya
        // ikut terlompati.
        $headerQuery->when($request->search_header, function ($q, $cari) {
            $q->where(function ($w) use ($cari) {
                $w->where('title', 'like', "%{$cari}%")
                  ->orWhere('batch_number', 'like', "%{$cari}%");
            });
        });

        $headerQuery->when($request->month_header, function ($q, $bulan) {
            $q->whereYear('created_at', substr($bulan, 0, 4))
              ->whereMonth('created_at', substr($bulan, 5, 2));
        });

        $headers = $headerQuery->latest('created_at')
            ->paginate(5, ['*'], 'header_page')
            ->withQueryString();

        /* ---------- Tabel bawah: rincian siswa ---------- */
        $details = null;

        if ($request->header_id) {
            $detailQuery = Tagihan::with([
                'siswa:id,nis,name',
                'items.tarif:id,billing_name',
                'pembayarans.dibuatOleh:id,name',
                'riwayatPenagihans.petugas:id,name',
                'pembatal:id,name',
            ])->where('invoice_header_id', $request->header_id);

            // BUG ASLI: orWhere tidak dibungkus closure, sehingga melompati
            // where('invoice_header_id') di atas — pencarian memunculkan
            // invoice dari batch lain.
            $detailQuery->when($request->search_detail, function ($q, $cari) {
                $q->where(function ($w) use ($cari) {
                    $w->whereHas('siswa', fn ($s) => $s->where('name', 'like', "%{$cari}%"))
                      ->orWhere('invoice_number', 'like', "%{$cari}%");
                });
            });

            $detailQuery->when($request->status_detail, fn ($q, $s) => $q->where('status', $s));

            $sortField     = $request->input('sort_field', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');

            $allowedSorts = ['invoice_number', 'total_amount', 'remaining_balance', 'status', 'created_at'];
            if (in_array($sortField, $allowedSorts)) {
                $detailQuery->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
            }

            $details = $detailQuery->paginate(10, ['*'], 'detail_page')->withQueryString();
        }

        // Tagihan yang dibatalkan tidak lagi dihitung. remaining_balance-nya
        // memang sudah dinolkan saat dibatalkan, tapi total_amount-nya masih
        // utuh — tanpa saringan ini "Total Potensi" ikut menggelembung oleh
        // uang yang justru diputuskan tidak jadi ditagih.
        $dasarRingkas = Tagihan::where('status', '!=', 'Cancelled');

        $totalTagihan = (clone $dasarRingkas)->sum('total_amount');
        $totalBelum   = (clone $dasarRingkas)->sum('remaining_balance');

        return Inertia::render('Keuangan/Pemasukan/KelolaTagihan', [
            'headers'          => $headers,
            'details'          => $details,
            'selectedHeaderId' => $request->header_id,
            'summary'          => [
                'totalTagihan' => (float) $totalTagihan,
                'totalLunas'   => (float) $totalTagihan - (float) $totalBelum,
                'totalBelum'   => (float) $totalBelum,
            ],
            // santris tidak punya is_active — statusnya enum aktif/lulus/keluar.
            'listSiswa' => Siswa::aktif()->orderBy('name')->get(['id', 'nis', 'name', 'student_type']),

            // target_summary dihitung dari relasi targets, jadi eager-load
            // wajib supaya tidak N+1.
            'listTarif' => Tarif::with('targets')
                ->where('is_active', true)
                ->orderBy('billing_name')
                ->get(['id', 'billing_name', 'nominal', 'period', 'is_donation'])
                ->map(fn (Tarif $t) => [
                    'id'             => $t->id,
                    'billing_name'   => $t->billing_name,
                    'nominal'        => $t->nominal,
                    'period'         => $t->period,
                    'is_donation'    => $t->is_donation,
                    'target_summary' => $t->targetSummary(),
                ]),

            'filters'   => $request->only([
                'search_header', 'month_header', 'search_detail', 'status_detail',
                'sort_field', 'sort_direction', 'header_id', 'header_page', 'detail_page',
            ]),
        ]);
    }

    public function storeHeader(Request $request)
    {
        $request->validate([
            'tariff_id' => 'required|exists:tariffs,id',
            'title'     => 'required|string|max:255',
            'period'    => 'required|string|max:100',
            'due_date'  => 'required|date',
        ], [
            'tariff_id.required' => 'Tarif dasar wajib dipilih.',
            'title.required'     => 'Judul penagihan wajib diisi.',
            'period.required'    => 'Periode wajib diisi.',
            'due_date.required'  => 'Tanggal tempo wajib diisi.',
        ]);

        $tarif = Tarif::with('targets')->findOrFail($request->tariff_id);

        InvoiceHeader::create([
            'batch_number'          => 'BCH-' . date('Ym') . '-' . strtoupper(Str::random(4)),
            'tariff_id'             => $tarif->id,
            'title'                 => $request->title,
            'period'                => $request->period,
            // Snapshot: aturan target tarif bisa berubah kapan saja, tapi
            // gelombang ini harus tetap bisa menjelaskan siapa yang dijaring
            // saat ia dibuat.
            'target_summary'        => $tarif->targetSummary(),
            'due_date'              => $request->due_date,
            'total_students'        => 0,
            'total_expected_amount' => 0,
            'status'                => 'Draft',
        ]);

        return redirect()->back()->with('success', 'Gelombang penagihan berhasil dibuat.');
    }

    public function updateHeader(Request $request, InvoiceHeader $header)
    {
        if ($header->status === 'Closed') {
            return redirect()->back()->withErrors(['status_error' => 'Gelombang yang sudah ditutup tidak dapat diubah.']);
        }

        $validated = $request->validate([
            'title'    => 'required|string|max:255',
            'period'   => 'required|string|max:100',
            'due_date' => 'required|date',
        ]);

        $header->update($validated);

        return redirect()->back()->with('success', 'Gelombang penagihan berhasil diperbarui.');
    }

    public function destroyHeader(InvoiceHeader $header)
    {
        /*
         * Penjaganya adalah UANG, bukan status.
         *
         * Versi sebelumnya menolak apa pun yang bukan Draft. Itu salah di
         * dua arah sekaligus: gelombang Closed yang tidak pernah menerima
         * sepeser pun ikut terkunci padahal aman dibuang, sementara Draft
         * yang entah bagaimana sudah menerima pembayaran justru lolos.
         *
         * Yang benar-benar tidak boleh hilang adalah rujukan pembayaran.
         * Uang yang sudah masuk tetap tercatat di buku kas, tapi tanpa
         * tagihan induknya ia tidak bisa lagi dijelaskan berasal dari
         * siswa mana dan untuk tagihan apa.
         */
        $kueri = DB::table('inbound_payments')
            ->join('invoices', 'invoices.id', '=', 'inbound_payments.invoice_id')
            ->where('invoices.invoice_header_id', $header->id);

        // Saringan soft delete dipasang HANYA bila kolomnya memang ada.
        // Versi sebelumnya menyaring 'invoices.deleted_at' tanpa memeriksa
        // dulu — bila kolomnya tidak ada, kueri gagal, Laravel melempar
        // 500, dan Inertia tidak punya pesan apa pun untuk ditampilkan:
        // dialognya diam, tidak ada error, tidak ada apa-apa.
        if (Schema::hasColumn('invoices', 'deleted_at')) {
            $kueri->whereNull('invoices.deleted_at');
        }

        $dipakai = $kueri
            ->selectRaw('COUNT(*) AS jumlah, COALESCE(SUM(inbound_payments.nominal), 0) AS total')
            ->first();

        if ($dipakai && (int) $dipakai->jumlah > 0) {
            // Pesannya menyebut angkanya, bukan sekadar "tidak bisa".
            // "Statusnya bukan Draft" tidak memberi tahu apa pun tentang
            // jalan keluarnya; "sudah menerima 3 pembayaran" memberi tahu.
            return redirect()->back()->withErrors([
                'header_error' => 'Gelombang ini sudah menerima '
                    . $dipakai->jumlah . ' pembayaran senilai Rp '
                    . number_format((float) $dipakai->total, 0, ',', '.')
                    . '. Hapus atau batalkan pembayarannya lebih dulu bila gelombang ini memang harus dibuang.',
            ]);
        }

        // SoftDeletes tidak memicu ON DELETE CASCADE, jadi invoice & item
        // di bawahnya dihapus manual agar tidak jadi yatim.
        DB::transaction(function () use ($header) {
            foreach ($header->invoices as $inv) {
                $inv->items()->delete();
                $inv->delete();
            }

            $header->delete();
        });

        return redirect()->back()->with('success', 'Gelombang penagihan beserta rinciannya berhasil dihapus.');
    }

    public function storeDetail(Request $request)
    {
        $request->validate([
            'invoice_header_id' => 'required|exists:invoice_headers,id',
            // Tabel students sudah dilebur ke santris.
            'student_id'        => 'required|exists:santris,id',
            'nominal'           => 'required|numeric|min:0',
        ], [
            'student_id.required' => 'Siswa wajib dipilih.',
            'nominal.required'    => 'Nominal wajib diisi.',
        ]);

        $header = InvoiceHeader::findOrFail($request->invoice_header_id);

        if ($header->status !== 'Draft') {
            return redirect()->back()->withErrors(['status_error' => 'Rincian hanya bisa ditambah saat gelombang berstatus Draft.']);
        }

        $sudahAda = Tagihan::where('invoice_header_id', $header->id)
            ->where('student_id', $request->student_id)
            ->exists();

        if ($sudahAda) {
            return redirect()->back()->withErrors(['student_id' => 'Siswa ini sudah ada di gelombang tersebut.']);
        }

        DB::transaction(function () use ($request, $header) {
            $tagihan = Tagihan::create([
                'invoice_number'    => 'INV-' . date('Ym') . '-' . strtoupper(Str::random(5)),
                'invoice_header_id' => $header->id,
                'student_id'        => $request->student_id,
                'total_amount'      => $request->nominal,
                'remaining_balance' => $request->nominal,
                'status'            => 'Unpaid',
            ]);

            $tagihan->items()->create([
                'tariff_id' => $header->tariff_id,
                'deskripsi' => 'Tagihan manual dari batch ' . $header->batch_number,
                'nominal'   => $request->nominal,
                'diskon'    => 0,
            ]);

            $header->increment('total_students');
            $header->increment('total_expected_amount', $request->nominal);
        });

        return redirect()->back()->with('success', 'Siswa berhasil ditambahkan ke gelombang.');
    }

    public function updateDetail(Request $request, Tagihan $tagihan)
    {
        if ($tagihan->header && $tagihan->header->status !== 'Draft') {
            return redirect()->back()->withErrors(['status_error' => 'Rincian hanya bisa diubah saat gelombang berstatus Draft.']);
        }

        // Versi asli me-reset remaining_balance ke nominal baru, sehingga
        // pembayaran yang sudah masuk terhapus jejaknya.
        if (bccomp((string) $tagihan->total_amount, (string) $tagihan->remaining_balance, 2) !== 0) {
            return redirect()->back()->withErrors([
                'nominal' => 'Tagihan ini sudah menerima pembayaran, nominalnya tidak dapat diubah.',
            ]);
        }

        $request->validate([
            'student_id' => 'required|exists:santris,id',
            'nominal'    => 'required|numeric|min:0',
        ]);

        $lama = (float) $tagihan->total_amount;
        $baru = (float) $request->nominal;

        DB::transaction(function () use ($tagihan, $request, $lama, $baru) {
            $tagihan->update([
                'student_id'        => $request->student_id,
                'total_amount'      => $baru,
                'remaining_balance' => $baru,
            ]);

            // firstOrCreate: tagihan lama bisa saja tidak punya baris item.
            $item = $tagihan->items()->first();

            if ($item) {
                $item->update(['nominal' => $baru]);
            } else {
                $tagihan->items()->create([
                    'tariff_id' => $tagihan->header?->tariff_id,
                    'deskripsi' => 'Penyesuaian nominal',
                    'nominal'   => $baru,
                    'diskon'    => 0,
                ]);
            }

            if ($tagihan->header) {
                $tagihan->header->increment('total_expected_amount', $baru - $lama);
            }
        });

        return redirect()->back()->with('success', 'Rincian tagihan berhasil diperbarui.');
    }

    public function destroy(Tagihan $tagihan)
    {
        // Gerbang ini sebelumnya hanya ada di tampilan — tombolnya
        // disembunyikan saat gelombang sudah dipublish, tapi controllernya
        // tetap menerima permintaannya.
        if ($tagihan->header && $tagihan->header->status !== 'Draft') {
            return redirect()->back()->withErrors([
                'delete_error' => 'Tagihan pada gelombang yang sudah dipublish tidak bisa dihapus. Gunakan Batalkan agar jejaknya tetap ada.',
            ]);
        }

        if (bccomp((string) $tagihan->total_amount, (string) $tagihan->remaining_balance, 2) !== 0) {
            return redirect()->back()->withErrors([
                'delete_error' => 'Tagihan ini sudah memiliki riwayat pembayaran.',
            ]);
        }

        DB::transaction(function () use ($tagihan) {
            if ($tagihan->header) {
                $header = $tagihan->header;

                if ($header->total_students > 0) {
                    $header->decrement('total_students');
                }

                $header->decrement('total_expected_amount', (float) $tagihan->total_amount);
            }

            $tagihan->items()->delete();
            $tagihan->delete();
        });

        return redirect()->back()->with('success', 'Tagihan berhasil dihapus.');
    }

    /**
     * Membatalkan satu tagihan yang sudah dipublish.
     *
     * Beda dengan destroy(): barisnya tetap ada, lengkap dengan alasan dan
     * siapa yang membatalkan. Enam bulan lagi, saat ada yang bertanya kenapa
     * tagihan seorang santri hilang, jawabannya ada di sistem.
     *
     * Alasannya berkategori, bukan tulisan bebas, supaya nominal yang
     * dibebaskan selama setahun bisa dijumlahkan — itu pengeluaran sosial
     * terbesar pesantren, hanya bentuknya uang yang tidak jadi masuk.
     */
    public function batalkan(Request $request, Tagihan $tagihan)
    {
        if ($tagihan->status === 'Cancelled') {
            return redirect()->back()->withErrors(['batal_error' => 'Tagihan ini sudah dibatalkan.']);
        }

        // Yang sudah menerima uang tidak boleh dibatalkan. Pembayarannya
        // sudah masuk buku kas dan menambah saldo rekening; membatalkan
        // tagihannya membuat saldo itu kehilangan penjelasan.
        if (bccomp((string) $tagihan->total_amount, (string) $tagihan->remaining_balance, 2) !== 0) {
            return redirect()->back()->withErrors([
                'batal_error' => 'Tagihan ini sudah menerima pembayaran, jadi tidak bisa dibatalkan. Yang bisa dibatalkan hanya tagihan yang belum dibayar sama sekali.',
            ]);
        }

        $data = $request->validate([
            'cancel_reason' => ['required', Rule::in(array_keys(Tagihan::ALASAN_BATAL))],
            'cancel_note'   => 'nullable|string|max:255',
        ], [
            'cancel_reason.required' => 'Alasan pembatalan wajib dipilih.',
            'cancel_reason.in'       => 'Alasan pembatalan tidak dikenali.',
        ]);

        DB::transaction(function () use ($tagihan, $data) {
            $tagihan->update([
                'status' => 'Cancelled',
                // Dinolkan supaya penjumlahan piutang di mana pun — termasuk
                // yang belum menyaring status — tidak lagi menghitungnya.
                // Nominal aslinya tetap tersimpan di total_amount.
                'remaining_balance' => 0,
                'cancel_reason'     => $data['cancel_reason'],
                'cancel_note'       => $data['cancel_note'] ?? null,
                'cancelled_by'      => Auth::id(),
                'cancelled_at'      => now(),
            ]);

            // total_students sengaja TIDAK dikurangi: barisnya masih ada di
            // daftar rincian, jadi angka "25 siswa" yang berubah jadi 24
            // sementara tabelnya tetap 25 baris justru membingungkan.
            if ($header = $tagihan->header) {
                $header->decrement('total_expected_amount', (float) $tagihan->total_amount);
            }
        });

        return redirect()->back()->with('success', 'Tagihan dibatalkan.');
    }

    /**
     * Membatalkan beberapa tagihan sekaligus dengan satu alasan.
     *
     * Satu alasan untuk seluruh pilihan memang disengaja: kalau alasannya
     * berbeda-beda, memang seharusnya dibatalkan satu per satu. Yang
     * dilayani di sini adalah kasus sebaliknya — satu keputusan yang
     * kebetulan menyangkut banyak santri, seperti pembebasan serentak.
     */
    public function batalkanMassal(Request $request)
    {
        $data = $request->validate([
            'ids'           => 'required|array|min:1',
            'ids.*'         => 'integer',
            'cancel_reason' => ['required', Rule::in(array_keys(Tagihan::ALASAN_BATAL))],
            'cancel_note'   => 'nullable|string|max:255',
        ], [
            'ids.required'           => 'Tidak ada tagihan yang dipilih.',
            'cancel_reason.required' => 'Alasan pembatalan wajib dipilih.',
            'cancel_reason.in'       => 'Alasan pembatalan tidak dikenali.',
        ]);

        $berhasil = 0;
        $dilewati = [];

        DB::transaction(function () use ($data, &$berhasil, &$dilewati) {
            $tagihans = Tagihan::with(['header', 'siswa:id,name'])
                ->whereIn('id', $data['ids'])
                ->get();

            foreach ($tagihans as $tagihan) {
                // Setiap baris diperiksa ulang dengan gerbang yang sama persis
                // seperti pembatalan satuan. Tidak ada jalan pintas massal:
                // daftar id datang dari browser dan bisa memuat apa saja.
                $boleh = $tagihan->status !== 'Cancelled'
                    && $tagihan->header
                    && $tagihan->header->status === 'Published'
                    && bccomp((string) $tagihan->total_amount, (string) $tagihan->remaining_balance, 2) === 0;

                if (! $boleh) {
                    // Dilewati, bukan menggagalkan seluruh transaksi — satu
                    // tagihan bermasalah tidak boleh membatalkan sembilan
                    // lainnya yang sebenarnya sah.
                    $dilewati[] = $tagihan->siswa?->name ?: $tagihan->invoice_number;
                    continue;
                }

                $tagihan->update([
                    'status'            => 'Cancelled',
                    'remaining_balance' => 0,
                    'cancel_reason'     => $data['cancel_reason'],
                    'cancel_note'       => $data['cancel_note'] ?? null,
                    'cancelled_by'      => Auth::id(),
                    'cancelled_at'      => now(),
                ]);

                $tagihan->header->decrement('total_expected_amount', (float) $tagihan->total_amount);

                $berhasil++;
            }
        });

        if ($berhasil === 0) {
            return redirect()->back()->withErrors([
                'batal_error' => 'Tidak ada tagihan yang dibatalkan. Semua yang dipilih sudah dibatalkan atau sudah menerima pembayaran.',
            ]);
        }

        $pesan = "{$berhasil} tagihan dibatalkan.";

        // Hasil massal hampir tidak pernah seragam. Yang dilewati HARUS
        // disebut — tanpa ini bendahara mengira semuanya berhasil, dan
        // selisihnya baru ketahuan saat laporan piutang tidak cocok.
        if ($dilewati) {
            $jumlah = count($dilewati);
            $daftar = implode(', ', array_slice($dilewati, 0, 5));

            if ($jumlah > 5) {
                $daftar .= ', dan ' . ($jumlah - 5) . ' lainnya';
            }

            $pesan .= "<br><br>{$jumlah} dilewati karena sudah dibatalkan atau sudah menerima pembayaran: <strong>{$daftar}</strong>.";
        }

        return redirect()->back()->with('success', $pesan);
    }

    public function autoGenerate(InvoiceHeader $header)
    {
        if ($header->status !== 'Draft') {
            return redirect()->back()->withErrors(['status_error' => 'Auto-generate hanya bisa saat gelombang berstatus Draft.']);
        }

        $tarif = $header->tarif;

        if (!$tarif) {
            return redirect()->back()->withErrors(['status_error' => 'Tarif dasar gelombang ini tidak ditemukan.']);
        }

        // Tarif donasi tidak menjaring santri manapun — penyumbang
        // ditambahkan manual lewat storeDetail.
        if ($tarif->is_donation) {
            return redirect()->back()->withErrors([
                'status_error' => 'Tarif donasi tidak menargetkan santri. Tambahkan penyumbang secara manual.',
            ]);
        }

        $tarif->loadMissing('targets');

        // Penjaringan sekarang berbasis aturan di tariff_targets:
        // dalam satu dimensi = OR, antar dimensi = AND.
        $siswas = $tarif->eligibleSantris()->get();

        if ($siswas->isEmpty()) {
            return redirect()->back()->withErrors([
                'status_error' => 'Tidak ada santri yang cocok dengan target tarif ini (' . $tarif->targetSummary() . ').',
            ]);
        }

        $jumlah = 0;
        $total  = 0;

        DB::transaction(function () use ($header, $tarif, $siswas, &$jumlah, &$total) {
            foreach ($siswas as $siswa) {
                $cek = Tagihan::where('student_id', $siswa->id)
                    ->whereHas('items', fn ($q) => $q->where('tariff_id', $tarif->id));

                // BUG ASLI: memakai $tarif->jenis_periode, kolom yang tidak
                // ada — nilainya selalu null sehingga kedua cabang meleset dan
                // pengecekan jadi "pernah ditagih tarif ini kapan pun".
                // Akibatnya generate periode berikutnya melewati semua siswa.
                if ($tarif->period !== 'Sekali Bayar') {
                    $cek->whereHas('header', fn ($q) => $q->where('period', $header->period));
                }

                if ($cek->exists()) {
                    continue;
                }

                $tagihan = Tagihan::create([
                    'invoice_number'    => 'INV-' . date('Ym') . '-' . strtoupper(Str::random(5)),
                    'invoice_header_id' => $header->id,
                    'student_id'        => $siswa->id,
                    'total_amount'      => $tarif->nominal,
                    'remaining_balance' => $tarif->nominal,
                    'status'            => 'Unpaid',
                ]);

                $tagihan->items()->create([
                    'tariff_id' => $tarif->id,
                    'deskripsi' => $tarif->billing_name,
                    'nominal'   => $tarif->nominal,
                    'diskon'    => 0,
                ]);

                $jumlah++;
                $total += (float) $tarif->nominal;
            }

            $header->increment('total_students', $jumlah);
            $header->increment('total_expected_amount', $total);
        });

        return $jumlah > 0
            ? redirect()->back()->with('success', "{$jumlah} tagihan berhasil dibuat.")
            : redirect()->back()->withErrors(['status_error' => 'Semua santri yang cocok sudah ditagih untuk periode ini.']);
    }

    public function publishHeader(InvoiceHeader $header)
    {
        if ($header->status !== 'Draft') {
            return redirect()->back()->withErrors(['status_error' => 'Hanya gelombang berstatus Draft yang dapat dipublish.']);
        }

        if (!$header->invoices()->exists()) {
            return redirect()->back()->withErrors(['status_error' => 'Gelombang masih kosong. Tambahkan siswa atau jalankan auto-generate.']);
        }

        $header->update(['status' => 'Published']);

        return redirect()->back()->with('success', 'Gelombang tagihan berhasil dipublish.');
    }

    public function closeHeader(InvoiceHeader $header)
    {
        if ($header->status !== 'Published') {
            return redirect()->back()->withErrors(['status_error' => 'Hanya gelombang berstatus Published yang dapat ditutup.']);
        }

        $header->update(['status' => 'Closed']);

        return redirect()->back()->with('success', 'Gelombang tagihan berhasil ditutup.');
    }

    public function printBulk(InvoiceHeader $header)
    {
        abort_unless($header->status === 'Published', 403,
            'Cetak massal hanya untuk gelombang yang sudah dipublish.');

        $tagihans = $header->invoices()
            ->with(['siswa:id,nis,name,parent_name', 'items'])
            ->whereIn('status', ['Unpaid', 'Installment'])
            ->orderBy('invoice_number')
            ->get();

        if ($tagihans->isEmpty()) {
            return redirect()->back()->withErrors(['status_error' => 'Semua tagihan pada gelombang ini sudah lunas.']);
        }

        return Pdf::loadView('pdf.bulk-tagihan', compact('header', 'tagihans'))
            ->setPaper('A4', 'portrait')
            ->stream('Tagihan_' . str_replace(' ', '_', $header->title) . '.pdf');
    }

    /**
     * Cetak satu invoice. Memakai ulang view bulk-tagihan dengan koleksi
     * berisi satu record, supaya format kertasnya persis sama dengan
     * cetak massal — tidak perlu blade terpisah.
     */
    public function printSingle(Tagihan $tagihan)
    {
        $header = $tagihan->header;

        abort_unless($header && $header->status === 'Published', 403,
            'Tagihan hanya bisa dicetak setelah gelombangnya dipublish.');

        // Diperiksa sebelum pemeriksaan lunas: tagihan batal juga bersisa
        // nol, dan menyebutnya "sudah lunas" adalah keterangan yang salah.
        if ($tagihan->status === 'Cancelled') {
            return redirect()->back()->withErrors([
                'status_error' => 'Tagihan ini sudah dibatalkan dan tidak dapat dicetak.',
            ]);
        }

        // Sejalan dengan printBulk: yang lunas tidak dicetak ulang.
        if (bccomp((string) $tagihan->remaining_balance, '0', 2) <= 0) {
            return redirect()->back()->withErrors([
                'status_error' => 'Tagihan ini sudah lunas.',
            ]);
        }

        $tagihan->load(['siswa:id,nis,name,parent_name', 'items']);

        $tagihans = collect([$tagihan]);

        return Pdf::loadView('pdf.bulk-tagihan', compact('header', 'tagihans'))
            ->setPaper('A4', 'portrait')
            ->stream('Tagihan_' . $tagihan->invoice_number . '.pdf');
    }
}