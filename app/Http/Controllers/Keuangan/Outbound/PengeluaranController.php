<?php

namespace App\Http\Controllers\Keuangan\Outbound;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\Finance\MutasiKas;
use App\Models\Keuangan\MasterData\Rekening;
use App\Models\Keuangan\Outbound\Pengeluaran;
use App\Models\Keuangan\Outbound\RkasDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PengeluaranController extends Controller
{
    private const IZIN = [
        'diperiksa' => 'pengeluaran.approve_diperiksa',
        'disetujui' => 'pengeluaran.approve_disetujui',
    ];

    /** Status yang harus dimiliki dokumen sebelum transisi. */
    private const SYARAT = [
        'diperiksa' => 'draft',
        'disetujui' => 'diperiksa',
    ];

    public function index(Request $request)
    {
        $query = Pengeluaran::with([
            'rkas:id,kode_kegiatan,nama_kegiatan,pagu_anggaran',
            'rekening:id,account_name',
            'diajukanOleh:id,name',
            'diperiksaOleh:id,name',
            'disetujuiOleh:id,name',
        ]);

        // orWhere dibungkus closure — versi asli membiarkannya melompati
        // filter status dan bulan di bawahnya.
        $query->when($request->search, function ($q, $cari) {
            $q->where(function ($w) use ($cari) {
                $w->where('deskripsi', 'like', "%{$cari}%")
                  ->orWhereHas('rkas', fn ($r) => $r->where('nama_kegiatan', 'like', "%{$cari}%"));
            });
        });

        $query->when($request->status, fn ($q, $s) => $q->where('status_approval', $s));

        $query->when($request->monthFilter, function ($q, $bulan) {
            $q->whereYear('tanggal_pengajuan', substr($bulan, 0, 4))
              ->whereMonth('tanggal_pengajuan', substr($bulan, 5, 2));
        });

        $sortField     = $request->input('sort_field', 'tanggal_pengajuan');
        $sortDirection = $request->input('sort_direction', 'desc');

        $allowedSorts = ['tanggal_pengajuan', 'nominal_pengajuan', 'status_approval'];
        $query->orderBy(
            in_array($sortField, $allowedSorts) ? $sortField : 'tanggal_pengajuan',
            $sortDirection === 'asc' ? 'asc' : 'desc'
        );

        $pengeluaran = $query->paginate(10)->withQueryString();

        $pengeluaran->getCollection()->transform(function ($item) {
            $item->attachments = $item->getMedia('bukti_pengeluaran')->map(fn ($m) => [
                'id'        => $m->id,
                'url'       => route('keuangan.media.show', $m->id),
                'file_name' => $m->file_name,
            ]);

            return $item;
        });

        return Inertia::render('Keuangan/Pengeluaran/PengajuanPengeluaran', [
            'pengeluaran'      => $pengeluaran,
            'rkas_options'     => $this->mataAnggaranTersedia(),
            'rekening_options' => Rekening::orderBy('account_name')->get(['id', 'account_name', 'balance']),
            'filters'          => $request->only(['search', 'status', 'monthFilter', 'sort_field', 'sort_direction']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->aturan(), $this->pesan());

        $rkas = RkasDetail::findOrFail($data['rkas_id']);

        Pengeluaran::create([
            'rkas_id'                => $rkas->id,
            'tanggal_pengajuan'      => $data['tanggal_pengajuan'],
            'nominal_pengajuan'      => $data['nominal_pengajuan'],
            'deskripsi'              => $data['deskripsi'],
            'is_over_budget_warning' => $this->melebihiPagu($rkas, $data['nominal_pengajuan']),
            'status_approval'        => 'draft',
            'requested_by'           => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Pengajuan pengeluaran berhasil dibuat.');
    }

    public function update(Request $request, Pengeluaran $pengeluaran)
    {
        if ($pengeluaran->status_approval !== 'draft') {
            return redirect()->back()->withErrors(['status' => 'Pengajuan yang sudah diproses tidak dapat diubah.']);
        }

        $data = $request->validate($this->aturan(), $this->pesan());

        $rkas = RkasDetail::findOrFail($data['rkas_id']);

        $pengeluaran->update([
            'rkas_id'                => $rkas->id,
            'tanggal_pengajuan'      => $data['tanggal_pengajuan'],
            'nominal_pengajuan'      => $data['nominal_pengajuan'],
            'deskripsi'              => $data['deskripsi'],
            'is_over_budget_warning' => $this->melebihiPagu($rkas, $data['nominal_pengajuan'], $pengeluaran->id),
        ]);

        return redirect()->back()->with('success', 'Pengajuan pengeluaran berhasil diperbarui.');
    }

    public function destroy(Pengeluaran $pengeluaran)
    {
        if ($pengeluaran->status_approval !== 'draft') {
            return redirect()->back()->withErrors(['status' => 'Pengajuan yang sudah diproses tidak dapat dihapus.']);
        }

        $pengeluaran->clearMediaCollection('bukti_pengeluaran');
        $pengeluaran->delete();

        return redirect()->back()->with('success', 'Pengajuan pengeluaran berhasil dihapus.');
    }

    public function approve(Request $request, Pengeluaran $pengeluaran)
    {
        $request->validate([
            'stage'      => 'required|in:diperiksa,disetujui',
            'account_id' => 'required_if:stage,disetujui|exists:accounts,id',
        ], [
            'account_id.required_if' => 'Rekening sumber dana wajib dipilih untuk mencairkan.',
        ]);

        $stage = $request->stage;

        abort_unless(Auth::user()->can(self::IZIN[$stage]), 403, 'Anda tidak memiliki akses untuk tahap ini.');

        // Versi asli tidak memeriksa status saat ini sama sekali. Dokumen yang
        // sudah 'disetujui' bisa dikembalikan ke 'diperiksa' lalu disetujui
        // lagi — saldo terpotong dua kali dan muncul dua baris di buku kas.
        if ($pengeluaran->status_approval !== self::SYARAT[$stage]) {
            return redirect()->back()->withErrors([
                'approval' => 'Transisi tidak valid. Status saat ini: ' . $pengeluaran->status_approval . '.',
            ]);
        }

        if ($stage === 'diperiksa') {
            $pengeluaran->update([
                'status_approval' => 'diperiksa',
                'diperiksa_by'    => Auth::id(),
                'diperiksa_at'    => now(),
            ]);

            return redirect()->back()->with('success', 'Pengajuan ditandai sudah diperiksa.');
        }

        try {
            DB::transaction(function () use ($pengeluaran, $request) {
                $now = now();

                // Baris rekening dikunci agar pembacaan saldo dan penulisan
                // mutasi tidak balapan dengan transaksi lain.
                $rekening = Rekening::lockForUpdate()->findOrFail($request->account_id);

                if (bccomp((string) $pengeluaran->nominal_pengajuan, (string) $rekening->balance, 2) === 1) {
                    throw new \RuntimeException(
                        'Saldo ' . $rekening->account_name . ' tidak mencukupi (tersedia Rp '
                        . number_format((float) $rekening->balance, 0, ',', '.') . ').'
                    );
                }

                $rekening->balance -= $pengeluaran->nominal_pengajuan;
                $rekening->save();

                $pengeluaran->update([
                    'status_approval' => 'disetujui',
                    'account_id'      => $rekening->id,
                    'disetujui_by'    => Auth::id(),
                    'disetujui_at'    => $now,
                ]);

                MutasiKas::create([
                    'account_id'              => $rekening->id,
                    'tanggal_transaksi'       => $now,
                    'keterangan'              => 'Pencairan: ' . Str::limit($pengeluaran->deskripsi, 80),
                    'jenis'                   => 'Kredit',
                    'nominal'                 => $pengeluaran->nominal_pengajuan,
                    'saldo_setelah_transaksi' => $rekening->balance,
                    'reference_type'          => Pengeluaran::class,
                    'reference_id'            => $pengeluaran->id,
                    'created_by'              => Auth::id(),
                ]);
            });
        } catch (\RuntimeException $e) {
            return redirect()->back()->withErrors(['approval' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Dana dicairkan dan tercatat di buku kas.');
    }

    public function storeLpj(Request $request, Pengeluaran $pengeluaran)
    {
        if ($pengeluaran->status_approval !== 'disetujui') {
            return redirect()->back()->withErrors(['status' => 'LPJ hanya untuk pengajuan yang sudah dicairkan.']);
        }

        $request->validate([
            'nominal_realisasi' => 'required|numeric|min:0|max:' . $pengeluaran->nominal_pengajuan,
            'bukti_nota'        => 'required|array|min:1|max:5',
            'bukti_nota.*'      => 'file|mimes:jpg,jpeg,png,pdf|max:2048',
        ], [
            'nominal_realisasi.required' => 'Nominal realisasi wajib diisi.',
            'nominal_realisasi.max'      => 'Realisasi tidak boleh melebihi dana yang dicairkan.',
            'bukti_nota.required'        => 'Bukti nota wajib diunggah.',
            'bukti_nota.max'             => 'Maksimal 5 berkas.',
        ]);

        $refund = (float) $pengeluaran->nominal_pengajuan - (float) $request->nominal_realisasi;

        DB::transaction(function () use ($request, $pengeluaran, $refund) {
            $pengeluaran->update([
                'nominal_realisasi' => $request->nominal_realisasi,
                'nominal_refund'    => $refund,
                // Checkbox versi asli menandai refund selesai TANPA memindahkan
                // uangnya — selisihnya hilang tanpa jejak. Sekarang otomatis:
                // selesai hanya bila memang tidak ada sisa.
                'is_refund_completed' => $refund <= 0,
                'status_approval'   => 'selesai',
            ]);

            $rkas = $pengeluaran->rkas;
            $rkas->increment('nominal_realisasi', $request->nominal_realisasi);
            $this->bubbleUpRealisasi($rkas->parent_id);

            foreach ($request->file('bukti_nota') as $file) {
                $pengeluaran->addMedia($file)
                    ->usingFileName(time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension())
                    ->toMediaCollection('bukti_pengeluaran');
            }
        });

        return redirect()->back()->with('success', $refund > 0
            ? 'LPJ tersimpan. Sisa dana Rp ' . number_format($refund, 0, ',', '.') . ' menunggu dikembalikan ke kas.'
            : 'LPJ tersimpan. Dana terpakai seluruhnya.');
    }

    public function completeRefund(Pengeluaran $pengeluaran)
    {
        if ($pengeluaran->status_approval !== 'selesai') {
            return redirect()->back()->withErrors(['status' => 'Pengajuan belum sampai tahap LPJ.']);
        }

        // Versi asli tidak memeriksa flag ini, sehingga tombolnya bisa diklik
        // berulang dan saldo bertambah setiap kali.
        if ($pengeluaran->is_refund_completed) {
            return redirect()->back()->withErrors(['status' => 'Sisa dana sudah dikembalikan sebelumnya.']);
        }

        if ($pengeluaran->nominal_refund <= 0 || !$pengeluaran->account_id) {
            return redirect()->back()->withErrors(['status' => 'Tidak ada sisa dana yang perlu dikembalikan.']);
        }

        DB::transaction(function () use ($pengeluaran) {
            $now      = now();
            $rekening = Rekening::lockForUpdate()->findOrFail($pengeluaran->account_id);

            $rekening->balance += $pengeluaran->nominal_refund;
            $rekening->save();

            $pengeluaran->update(['is_refund_completed' => true]);

            MutasiKas::create([
                'account_id'              => $rekening->id,
                'tanggal_transaksi'       => $now,
                'keterangan'              => 'Pengembalian sisa dana: ' . Str::limit($pengeluaran->deskripsi, 70),
                'jenis'                   => 'Debit',
                'nominal'                 => $pengeluaran->nominal_refund,
                'saldo_setelah_transaksi' => $rekening->balance,
                'reference_type'          => Pengeluaran::class,
                'reference_id'            => $pengeluaran->id,
                'created_by'              => Auth::id(),
            ]);
        });

        return redirect()->back()->with('success', 'Sisa dana dikembalikan ke kas dan tercatat di buku mutasi.');
    }

    /* ================= Helper ================= */

    private function aturan(): array
    {
        return [
            'rkas_id'           => 'required|exists:rkas_details,id',
            'tanggal_pengajuan' => 'required|date',
            'nominal_pengajuan' => 'required|numeric|min:1',
            'deskripsi'         => 'required|string|max:1000',
        ];
    }

    private function pesan(): array
    {
        return [
            'rkas_id.required'           => 'Mata anggaran wajib dipilih.',
            'tanggal_pengajuan.required' => 'Tanggal pengajuan wajib diisi.',
            'nominal_pengajuan.required' => 'Nominal pengajuan wajib diisi.',
            'nominal_pengajuan.min'      => 'Nominal minimal Rp 1.',
            'deskripsi.required'         => 'Deskripsi keperluan wajib diisi.',
        ];
    }

    private function melebihiPagu(RkasDetail $rkas, $nominal, $kecualiId = null): bool
    {
        $terpakai = Pengeluaran::where('rkas_id', $rkas->id)
            ->when($kecualiId, fn ($q) => $q->where('id', '!=', $kecualiId))
            ->sum('nominal_pengajuan');

        return ($terpakai + (float) $nominal) > (float) $rkas->pagu_anggaran;
    }

    /** Mata anggaran RKAS Final yang pagunya masih tersisa. */
    private function mataAnggaranTersedia()
    {
        return RkasDetail::withSum('pengeluaran as total_pengajuan', 'nominal_pengajuan')
            ->where('is_parent', false)
            ->whereHas('header', fn ($q) => $q->where('status', 'Final'))
            ->get()
            ->map(function ($r) {
                $r->sisa_pagu = (float) $r->pagu_anggaran - (float) ($r->total_pengajuan ?? 0);
                return $r->sisa_pagu > 0 ? $r : null;
            })
            ->filter()
            ->values();
    }

    /**
     * Realisasi baris induk = jumlah realisasi anak-anaknya, naik sampai
     * level teratas — mengikuti pola pagu_anggaran di RkasController,
     * supaya laporan realisasi RKAS terisi benar di tiap tingkat.
     */
    private function bubbleUpRealisasi($detailId): void
    {
        if (!$detailId) {
            return;
        }

        $detail = RkasDetail::find($detailId);

        if (!$detail || !$detail->is_parent) {
            return;
        }

        $detail->update(['nominal_realisasi' => $detail->children()->sum('nominal_realisasi')]);

        $this->bubbleUpRealisasi($detail->parent_id);
    }
}