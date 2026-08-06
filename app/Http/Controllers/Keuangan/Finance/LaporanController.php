<?php

namespace App\Http\Controllers\Keuangan\Finance;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\Finance\MutasiKas;
use App\Models\Keuangan\Inbound\RiwayatPenagihan;
use App\Models\Keuangan\Inbound\Tagihan;
use App\Models\Keuangan\MasterData\Rekening;
use App\Models\Keuangan\MasterData\Siswa;
use App\Models\Keuangan\Outbound\RkasDetail;
use App\Models\Keuangan\Outbound\RkasHeader;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LaporanController extends Controller
{
    /* ==================== BUKU KAS UMUM ==================== */

    public function bukuKasUmum(Request $request)
    {
        [$accountId, $dari, $sampai] = $this->paramBukuKas($request);

        $mutasi    = null;
        $saldoAwal = 0;
        $ringkas   = ['debit' => 0, 'kredit' => 0];

        if ($accountId) {
            $saldoAwal = $this->saldoAwal($accountId, $dari);

            $rentang = [$dari . ' 00:00:00', $sampai . ' 23:59:59'];

            $mutasi = MutasiKas::with('pembuat:id,name')
                ->where('account_id', $accountId)
                ->whereBetween('tanggal_transaksi', $rentang)
                ->orderBy('tanggal_transaksi')
                ->orderBy('id')
                ->paginate(50)
                ->withQueryString();

            // Total dihitung dari seluruh rentang, bukan hanya halaman aktif.
            $ringkas = [
                'debit' => (float) MutasiKas::where('account_id', $accountId)
                    ->whereBetween('tanggal_transaksi', $rentang)
                    ->where('jenis', 'Debit')->sum('nominal'),
                'kredit' => (float) MutasiKas::where('account_id', $accountId)
                    ->whereBetween('tanggal_transaksi', $rentang)
                    ->where('jenis', 'Kredit')->sum('nominal'),
            ];
        }

        return Inertia::render('Keuangan/Laporan/BukuKasUmum', [
            'listRekening' => Rekening::orderBy('account_name')->get(['id', 'account_name', 'type', 'balance']),
            'mutasi'       => $mutasi,
            'saldoAwal'    => (float) $saldoAwal,
            'ringkas'      => $ringkas,
            'filters'      => ['account_id' => $accountId, 'from_date' => $dari, 'to_date' => $sampai],
        ]);
    }

    public function cetakBukuKas(Request $request)
    {
        [$accountId, $dari, $sampai] = $this->paramBukuKas($request);

        abort_unless($accountId, 400, 'Rekening belum dipilih.');

        $rekening = Rekening::findOrFail($accountId);

        $mutasi = MutasiKas::with('pembuat:id,name')
            ->where('account_id', $accountId)
            ->whereBetween('tanggal_transaksi', [$dari . ' 00:00:00', $sampai . ' 23:59:59'])
            ->orderBy('tanggal_transaksi')
            ->orderBy('id')
            ->get();

        return Pdf::loadView('pdf.buku_kas', [
            'rekening'  => $rekening,
            'mutasi'    => $mutasi,
            'saldoAwal' => (float) $this->saldoAwal($accountId, $dari),
            'dari'      => Carbon::parse($dari),
            'sampai'    => Carbon::parse($sampai),
        ])->setPaper('A4', 'landscape')
          ->stream("Buku_Kas_{$rekening->account_name}_{$dari}_{$sampai}.pdf");
    }

    /* ==================== TUNGGAKAN ==================== */

    public function tunggakan(Request $request)
    {
        $query = Tagihan::with([
            'siswa:id,nis,name,parent_name,parent_contact',
            'header:id,title,period,due_date',
            'pembayarans.dibuatOleh:id,name',
            'riwayatPenagihans.petugas:id,name',
        ])
            ->whereHas('header', fn ($q) => $q->where('status', 'Published'))
            ->whereIn('status', ['Unpaid', 'Installment']);

        // BUG ASLI: rangkaian where/orWhereHas tidak dibungkus closure, sehingga
        // orWhere melompati filter status DAN filter header Published di atas —
        // tagihan yang sudah lunas ikut muncul saat kolom pencarian diisi.
        $query->when($request->search, function ($q, $cari) {
            $q->where(function ($w) use ($cari) {
                $w->where('invoice_number', 'like', "%{$cari}%")
                  ->orWhereHas('siswa', fn ($s) => $s->where('name', 'like', "%{$cari}%")
                                                    ->orWhere('nis', 'like', "%{$cari}%"));
            });
        });

        $query->when($request->student_id, fn ($q, $id) => $q->where('student_id', $id));

        $sortField = $request->input('sort_field', 'created_at');
        $allowed   = ['invoice_number', 'remaining_balance', 'total_amount', 'created_at'];

        $query->orderBy(
            in_array($sortField, $allowed, true) ? $sortField : 'created_at',
            $request->input('sort_direction') === 'asc' ? 'asc' : 'desc'
        );

        $dasar = Tagihan::whereHas('header', fn ($q) => $q->where('status', 'Published'))
            ->whereIn('status', ['Unpaid', 'Installment']);

        return Inertia::render('Keuangan/Laporan/LaporanTunggakan', [
            'tunggakan' => $query->paginate(15)->withQueryString(),
            'listSiswa' => Siswa::aktif()->orderBy('name')->get(['id', 'nis', 'name']),
            'ringkas'   => [
                'totalPiutang' => (float) (clone $dasar)->sum('remaining_balance'),
                'jumlahSiswa'  => (clone $dasar)->distinct('student_id')->count('student_id'),
                'jumlahInvoice'=> (clone $dasar)->count(),
            ],
            'filters' => $request->only(['search', 'student_id', 'sort_field', 'sort_direction']),
        ]);
    }

    public function catatPenagihan(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:santris,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'metode'     => 'required|in:WhatsApp,Surat Peringatan,Telepon,Lainnya',
            'keterangan' => 'required|string|max:255',
        ], [
            'metode.required'     => 'Metode penagihan wajib dipilih.',
            'keterangan.required' => 'Keterangan tindakan wajib diisi.',
        ]);

        RiwayatPenagihan::create($data + [
            'tanggal_penagihan' => now(),
            'ditagih_oleh'      => Auth::id(),
        ]);

        // Flash key 'message' versi asli tidak dikenali HandleInertiaRequests.
        return redirect()->back()->with('success', 'Tindakan penagihan berhasil dicatat.');
    }

    /* ==================== REALISASI RKAS ==================== */

    public function realisasiRkas(Request $request)
    {
        $listPeriode = RkasHeader::whereIn('status', ['Final', 'Closed'])
            ->orderByDesc('created_at')
            ->get(['id', 'tahun_ajaran', 'status', 'budget_limit']);

        $periodeId = $request->input('periode_id') ?: $listPeriode->first()?->id;

        $realisasi = null;
        $summary   = ['total_anggaran' => 0, 'total_realisasi' => 0, 'sisa_anggaran' => 0, 'persentase_serapan' => 0];

        if ($periodeId) {
            $query = RkasDetail::where('rkas_header_id', $periodeId)->where('is_parent', false);

            $query->when($request->search, function ($q, $cari) {
                $q->where(function ($w) use ($cari) {
                    $w->where('nama_kegiatan', 'like', "%{$cari}%")
                      ->orWhere('kode_kegiatan', 'like', "%{$cari}%");
                });
            });

            $sortField = $request->input('sort_field', 'kode_kegiatan');
            $allowed   = ['kode_kegiatan', 'nama_kegiatan', 'pagu_anggaran', 'nominal_realisasi'];

            $query->orderBy(
                in_array($sortField, $allowed, true) ? $sortField : 'kode_kegiatan',
                $request->input('sort_direction') === 'desc' ? 'desc' : 'asc'
            );

            $realisasi = $query->paginate(20)->withQueryString();

            $dasar = RkasDetail::where('rkas_header_id', $periodeId)->where('is_parent', false);

            $anggaran  = (float) (clone $dasar)->sum('pagu_anggaran');
            $terserap  = (float) (clone $dasar)->sum('nominal_realisasi');

            $summary = [
                'total_anggaran'     => $anggaran,
                'total_realisasi'    => $terserap,
                'sisa_anggaran'      => $anggaran - $terserap,
                'persentase_serapan' => $anggaran > 0 ? round($terserap / $anggaran * 100, 2) : 0,
            ];
        }

        // Nama komponen: 'RealisasiRkas'. Versi asli me-render string ini
        // sementara nama filenya RealisasiRKAS.jsx — di Linux tidak ketemu.
        return Inertia::render('Keuangan/Laporan/RealisasiRkas', [
            'listPeriode' => $listPeriode,
            'realisasi'   => $realisasi,
            'summary'     => $summary,
            'filters'     => [
                'periode_id'     => $periodeId,
                'search'         => $request->search,
                'sort_field'     => $request->sort_field,
                'sort_direction' => $request->sort_direction,
            ],
        ]);
    }

    /* ==================== Helper ==================== */

    private function paramBukuKas(Request $request): array
    {
        return [
            $request->input('account_id'),
            $request->input('from_date', Carbon::now()->startOfMonth()->format('Y-m-d')),
            $request->input('to_date', Carbon::now()->endOfMonth()->format('Y-m-d')),
        ];
    }

    /**
     * Saldo penutup terakhir sebelum tanggal mulai.
     * orderBy('id') dipakai sebagai tie-break — tanpa itu, dua mutasi pada
     * detik yang sama bisa memberi saldo awal dari baris yang keliru.
     */
    private function saldoAwal($accountId, string $dari): float
    {
        $terakhir = MutasiKas::where('account_id', $accountId)
            ->where('tanggal_transaksi', '<', $dari . ' 00:00:00')
            ->orderByDesc('tanggal_transaksi')
            ->orderByDesc('id')
            ->first();

        return (float) ($terakhir->saldo_setelah_transaksi ?? 0);
    }
}