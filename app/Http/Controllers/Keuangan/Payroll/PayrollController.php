<?php

namespace App\Http\Controllers\Keuangan\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\Finance\MutasiKas;
use App\Models\Keuangan\MasterData\Pegawai;
use App\Models\Keuangan\MasterData\Rekening;
use App\Models\Keuangan\Payroll\Absensi;
use App\Models\Keuangan\Payroll\PayrollDetail;
use App\Models\Keuangan\Payroll\PayrollHeader;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PayrollController extends Controller
{
    private const BULAN = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
    ];

    private const IZIN = [
        'diperiksa' => 'penggajian.approve_diperiksa',
        'disetujui' => 'penggajian.approve_disetujui',
        'dibayar'   => 'penggajian.approve_dibayar',
    ];

    private const SYARAT = [
        'diperiksa' => 'draft',
        'disetujui' => 'diperiksa',
        'dibayar'   => 'disetujui',
    ];

    public function index(Request $request)
    {
        $query = PayrollHeader::with([
            'pembuat:id,name', 'diperiksaOleh:id,name', 'penyetuju:id,name', 'pembayar:id,name',
        ]);

        $query->when($request->tahun, fn ($q, $t) => $q->where('periode_tahun', $t));
        $query->when($request->bulan, fn ($q, $b) => $q->where('periode_bulan', str_pad($b, 2, '0', STR_PAD_LEFT)));
        $query->when($request->status, fn ($q, $s) => $q->where('status_approval', $s));

        $sortField = $request->input('sort_field', 'created_at');
        $allowed   = ['periode_tahun', 'periode_bulan', 'grand_total', 'status_approval', 'created_at'];

        $query->orderBy(
            in_array($sortField, $allowed, true) ? $sortField : 'created_at',
            $request->input('sort_direction') === 'asc' ? 'asc' : 'desc'
        );

        $headers            = $query->paginate(10)->withQueryString();
        $details            = null;
        $availableEmployees = [];

        if ($request->header_id) {
            $header = PayrollHeader::find($request->header_id);

            $detailQuery = PayrollDetail::with(['employee.jabatan:id,nama_jabatan', 'allowances', 'deductions'])
                ->where('payroll_header_id', $request->header_id);

            // 'ilike' diganti 'like' — ilike hanya ada di PostgreSQL.
            $detailQuery->when($request->search_detail, fn ($q, $cari) =>
                $q->whereHas('employee', fn ($e) => $e->where('name', 'like', "%{$cari}%")));

            $details = $detailQuery->paginate(10, ['*'], 'detail_page')->withQueryString();

            if ($header && $header->status_approval === 'draft') {
                $sudahAda = PayrollDetail::where('payroll_header_id', $header->id)->pluck('employee_id');

                $availableEmployees = Pegawai::with('jabatan:id,nama_jabatan,gaji_pokok_default')
                    ->where('is_active', true)
                    ->whereNotIn('id', $sudahAda)
                    ->orderBy('name')
                    ->get(['id', 'name', 'nip', 'position_id']);
            }
        }

        return Inertia::render('Keuangan/Penggajian/DataPenggajian', [
            'headers'            => $headers,
            'details'            => $details,
            'availableEmployees' => $availableEmployees,
            'rekening_options'   => Rekening::orderBy('account_name')->get(['id', 'account_name', 'balance']),
            'selectedHeaderId'   => $request->header_id,
            'filters'            => $request->only(['tahun', 'bulan', 'status', 'sort_field', 'sort_direction', 'header_id', 'search_detail']),
        ]);
    }

    public function autoGenerate(Request $request)
    {
        $data = $request->validate([
            'periode_bulan' => 'required|string|max:2',
            'periode_tahun' => 'required|string|size:4',
        ]);

        $bulan = str_pad($data['periode_bulan'], 2, '0', STR_PAD_LEFT);
        $tahun = $data['periode_tahun'];

        if (PayrollHeader::where('periode_bulan', $bulan)->where('periode_tahun', $tahun)->exists()) {
            return redirect()->back()->withErrors(['generate' => 'Periode penggajian ini sudah dibuat.']);
        }

        $pegawais = Pegawai::with(['jabatan', 'defaultAllowances', 'defaultDeductions'])
            ->where('is_active', true)
            ->get();

        if ($pegawais->isEmpty()) {
            return redirect()->back()->withErrors(['generate' => 'Belum ada pegawai aktif.']);
        }

        DB::transaction(function () use ($bulan, $tahun, $pegawais) {
            $header = PayrollHeader::create([
                'periode_bulan'   => $bulan,
                'periode_tahun'   => $tahun,
                'status_approval' => 'draft',
                'created_by'      => Auth::id(),
            ]);

            foreach ($pegawais as $pegawai) {
                $this->buatDetail($header, $pegawai, $pegawai->jabatan->gaji_pokok_default ?? 0);
            }

            // Kelas yang diampu kini dibaca dari kelas.guru_id, bukan
            // employees.class_id yang sudah dibuang.
            foreach ($this->kelasDiampu($pegawais->pluck('id')) as $kelasId) {
                $this->hitungSubsidiKelas($header, $kelasId);
            }

            $this->hitungTotalHeader($header);
        });

        return redirect()->back()->with('success',
            'Penggajian ' . self::BULAN[$bulan] . " {$tahun} berhasil dibuat untuk {$pegawais->count()} pegawai.");
    }

    public function storeDetail(Request $request)
    {
        $data = $request->validate([
            'payroll_header_id' => 'required|exists:payroll_headers,id',
            'employee_id'       => 'required|exists:gurus,id',
            'gaji_pokok'        => 'required|numeric|min:0',
        ]);

        $header = PayrollHeader::findOrFail($data['payroll_header_id']);

        if ($header->status_approval !== 'draft') {
            return redirect()->back()->withErrors(['store_detail' => 'Rincian hanya bisa ditambah saat berstatus draft.']);
        }

        if (PayrollDetail::where('payroll_header_id', $header->id)->where('employee_id', $data['employee_id'])->exists()) {
            return redirect()->back()->withErrors(['employee_id' => 'Pegawai ini sudah ada di daftar gaji.']);
        }

        DB::transaction(function () use ($data, $header) {
            $pegawai = Pegawai::with(['defaultAllowances', 'defaultDeductions'])->findOrFail($data['employee_id']);

            $this->buatDetail($header, $pegawai, $data['gaji_pokok']);

            foreach ($pegawai->kelasDiampu()->pluck('id') as $kelasId) {
                $this->hitungSubsidiKelas($header, $kelasId);
            }

            $this->hitungTotalHeader($header);
        });

        return redirect()->back()->with('success', 'Pegawai berhasil ditambahkan.');
    }

    public function updateDetail(Request $request, PayrollDetail $detail)
    {
        if ($detail->header->status_approval !== 'draft') {
            return redirect()->back()->withErrors(['status' => 'Rincian hanya bisa diubah saat berstatus draft.']);
        }

        $request->validate(['gaji_pokok' => 'required|numeric|min:0']);

        DB::transaction(function () use ($request, $detail) {
            $tunjangan = $detail->allowances()->sum('nominal_snapshot');
            $potongan  = $detail->deductions()->sum('nominal_snapshot');

            $detail->update([
                'gaji_pokok'      => $request->gaji_pokok,
                'total_tunjangan' => $tunjangan,
                'total_potongan'  => $potongan,
                'take_home_pay'   => $request->gaji_pokok + $tunjangan - $potongan + $detail->nominal_tambahan,
            ]);

            foreach ($detail->employee?->kelasDiampu()->pluck('id') ?? [] as $kelasId) {
                $this->hitungSubsidiKelas($detail->header, $kelasId);
            }

            $this->hitungTotalHeader($detail->header);
        });

        return redirect()->back()->with('success', 'Rincian gaji berhasil diperbarui.');
    }

    public function destroyHeader(PayrollHeader $header)
    {
        if ($header->status_approval !== 'draft') {
            return redirect()->back()->withErrors(['status' => 'Periode yang sudah diproses tidak dapat dihapus.']);
        }

        // SoftDeletes tidak memicu ON DELETE CASCADE — detail dan
        // snapshot-nya dihapus manual agar tidak jadi yatim.
        DB::transaction(function () use ($header) {
            foreach ($header->details as $d) {
                $d->allowances()->delete();
                $d->deductions()->delete();
                $d->delete();
            }

            $header->delete();
        });

        return redirect()->back()->with('success', 'Periode penggajian berhasil dihapus.');
    }

    public function destroyDetail(PayrollDetail $detail)
    {
        if ($detail->header->status_approval !== 'draft') {
            return redirect()->back()->withErrors(['status' => 'Rincian yang sudah diproses tidak dapat dihapus.']);
        }

        DB::transaction(function () use ($detail) {
            $header = $detail->header;
            // Diambil sebelum baris dihapus.
            $kelasIds = $detail->employee?->kelasDiampu()->pluck('id')->all() ?? [];

            $detail->allowances()->delete();
            $detail->deductions()->delete();
            $detail->delete();

            foreach ($kelasIds as $kelasId) {
                $this->hitungSubsidiKelas($header, $kelasId);
            }

            $this->hitungTotalHeader($header);
        });

        return redirect()->back()->with('success', 'Pegawai dikeluarkan dari daftar gaji.');
    }

    public function approve(Request $request, PayrollHeader $header)
    {
        $request->validate([
            'stage'      => 'required|in:diperiksa,disetujui,dibayar',
            'account_id' => 'required_if:stage,dibayar|exists:accounts,id',
        ], [
            'account_id.required_if' => 'Rekening sumber dana wajib dipilih.',
        ]);

        $stage = $request->stage;

        abort_unless(Auth::user()->can(self::IZIN[$stage]), 403, 'Anda tidak memiliki akses untuk tahap ini.');

        // Versi asli hanya mengecek keberadaan diperiksa_by / disetujui_by,
        // bukan status saat ini. Header yang sudah 'dibayar' bisa didorong
        // ulang ke 'dibayar' — saldo terpotong dua kali.
        if ($header->status_approval !== self::SYARAT[$stage]) {
            return redirect()->back()->withErrors([
                'approve' => 'Transisi tidak valid. Status saat ini: ' . $header->status_approval . '.',
            ]);
        }

        if ($stage !== 'dibayar') {
            $kolom = $stage === 'diperiksa'
                ? ['diperiksa_by' => Auth::id(), 'diperiksa_at' => now()]
                : ['disetujui_by' => Auth::id(), 'disetujui_at' => now()];

            $header->update(['status_approval' => $stage] + $kolom);

            return redirect()->back()->with('success', 'Status penggajian menjadi ' . ucfirst($stage) . '.');
        }

        if ($header->grand_total <= 0) {
            return redirect()->back()->withErrors(['approve' => 'Total penggajian nol, tidak ada yang dicairkan.']);
        }

        try {
            DB::transaction(function () use ($header, $request) {
                $now      = now();
                $rekening = Rekening::lockForUpdate()->findOrFail($request->account_id);

                if (bccomp((string) $header->grand_total, (string) $rekening->balance, 2) === 1) {
                    throw new \RuntimeException(
                        'Saldo ' . $rekening->account_name . ' tidak mencukupi (tersedia Rp '
                        . number_format((float) $rekening->balance, 0, ',', '.') . ').'
                    );
                }

                $rekening->balance -= $header->grand_total;
                $rekening->save();

                $header->update([
                    'status_approval' => 'dibayar',
                    'account_id'      => $rekening->id,
                    'dibayar_by'      => Auth::id(),
                    'dibayar_at'      => $now,
                ]);

                $header->details()->get()->each->update(['is_paid' => true]);

                MutasiKas::create([
                    'account_id'              => $rekening->id,
                    'tanggal_transaksi'       => $now,
                    'keterangan'              => 'Pembayaran gaji ' . self::BULAN[$header->periode_bulan] . ' ' . $header->periode_tahun,
                    'jenis'                   => 'Kredit',
                    'nominal'                 => $header->grand_total,
                    'saldo_setelah_transaksi' => $rekening->balance,
                    'reference_type'          => PayrollHeader::class,
                    'reference_id'            => $header->id,
                    'created_by'              => Auth::id(),
                ]);
            });
        } catch (\RuntimeException $e) {
            return redirect()->back()->withErrors(['approve' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Gaji dicairkan dan tercatat di buku kas.');
    }

    public function printBulk(PayrollHeader $header)
    {
        abort_unless($header->status_approval === 'dibayar', 403,
            'Slip gaji hanya dapat dicetak setelah gaji dibayarkan.');

        $header->load(['details.employee.jabatan', 'details.allowances', 'details.deductions']);

        return Pdf::loadView('pdf.slip_gaji_bulk', [
            'header'  => $header,
            'periode' => self::BULAN[$header->periode_bulan] . ' ' . $header->periode_tahun,
        ])->setPaper('A4', 'portrait')
          ->stream("Slip_Gaji_{$header->periode_bulan}_{$header->periode_tahun}.pdf");
    }

    /* ================= Helper ================= */

    /** Buat satu baris detail lengkap dengan snapshot tunjangan & potongan. */
    private function buatDetail(PayrollHeader $header, Pegawai $pegawai, $gajiPokok): PayrollDetail
    {
        $hadir      = $this->hitungAbsensi($pegawai, $header, ['Hadir']);
        $tidakMasuk = $this->hitungAbsensi($pegawai, $header, Absensi::TIDAK_MASUK);

        $detail = PayrollDetail::create([
            'payroll_header_id' => $header->id,
            'employee_id'       => $pegawai->id,
            'gaji_pokok'        => $gajiPokok,
            'take_home_pay'     => 0,
        ]);

        $totalTunjangan = 0;

        foreach ($pegawai->defaultAllowances as $t) {
            $dasar   = $t->pivot->nominal_override ?? $t->nominal_default;
            // Versi asli juga menebak dari nama komponen (str_contains 'hadir'),
            // sehingga "Tunjangan Kehadiran Rapat" berjenis Tetap ikut dikalikan
            // jumlah hari. Kolom `jenis` sudah wajib diisi, jadi itu saja acuannya.
            $nominal = $t->jenis === 'Kehadiran' ? $dasar * $hadir : $dasar;

            $detail->allowances()->create([
                'nama_tunjangan_snapshot' => $t->nama_tunjangan,
                'nominal_snapshot'        => $nominal,
            ]);

            $totalTunjangan += $nominal;
        }

        $totalPotongan = 0;

        foreach ($pegawai->defaultDeductions as $p) {
            $dasar   = $p->pivot->nominal_override ?? $p->nominal_default;
            $nominal = $p->jenis === 'Kehadiran' ? $dasar * $tidakMasuk : $dasar;

            $detail->deductions()->create([
                'nama_potongan_snapshot' => $p->nama_potongan,
                'nominal_snapshot'       => $nominal,
            ]);

            $totalPotongan += $nominal;
        }

        $detail->update([
            'total_tunjangan' => $totalTunjangan,
            'total_potongan'  => $totalPotongan,
            'take_home_pay'   => $gajiPokok + $totalTunjangan - $totalPotongan,
        ]);

        return $detail;
    }

    private function hitungAbsensi(Pegawai $pegawai, PayrollHeader $header, array $status): int
    {
        return $pegawai->absensi()
            ->whereYear('date', $header->periode_tahun)
            ->whereMonth('date', $header->periode_bulan)
            ->whereIn('status', $status)
            ->count();
    }

    /**
     * Kumpulan kelas yang diampu sejumlah pegawai. Menggantikan
     * employees.class_id — satu guru bisa mengampu lebih dari satu kelas
     * (lintas tahun ajaran), jadi hasilnya array, bukan satu nilai.
     */
    private function kelasDiampu($pegawaiIds): array
    {
        return \App\Models\Kelas::whereIn('guru_id', $pegawaiIds)
            ->pluck('id')
            ->unique()
            ->all();
    }

    /**
     * Subsidi silang kehadiran antar guru satu kelas.
     *
     * Bila seorang guru absen pada satu hari, nominal potongannya
     * dibagikan kepada rekan sekelas yang hadir hari itu.
     */
    private function hitungSubsidiKelas(PayrollHeader $header, $classId): void
    {
        if (!$classId) {
            return;
        }

        $details = PayrollDetail::with(['employee.defaultDeductions'])
            ->where('payroll_header_id', $header->id)
            ->whereHas('employee.kelasDiampu', fn ($q) => $q->where('kelas.id', $classId))
            ->get();

        // Satu guru sendirian tidak punya rekan untuk saling menyubsidi.
        if ($details->count() <= 1) {
            foreach ($details as $d) {
                if ($d->nominal_tambahan > 0) {
                    $d->update([
                        'nominal_tambahan'    => 0,
                        'keterangan_tambahan' => null,
                        'take_home_pay'       => $d->gaji_pokok + $d->total_tunjangan - $d->total_potongan,
                    ]);
                }
            }

            return;
        }

        // Tarif potongan harian per pegawai.
        $tarif = [];

        foreach ($details as $d) {
            $tarif[$d->employee_id] = $d->employee->defaultDeductions
                ->where('jenis', 'Kehadiran')
                ->sum(fn ($p) => $p->pivot->nominal_override ?? $p->nominal_default);
        }

        $absensi = Absensi::whereIn('employee_id', $details->pluck('employee_id'))
            ->whereYear('date', $header->periode_tahun)
            ->whereMonth('date', $header->periode_bulan)
            ->get()
            ->groupBy(fn ($a) => $a->date->format('Y-m-d'));

        $subsidi = $details->pluck('id')->flip()->map(fn () => 0)->all();

        foreach ($absensi as $harian) {
            $hadir     = [];
            $terkumpul = 0;

            foreach ($details as $d) {
                $status = $harian->firstWhere('employee_id', $d->employee_id)?->status;

                if (in_array($status, Absensi::TIDAK_MASUK, true)) {
                    $terkumpul += $tarif[$d->employee_id] ?? 0;
                } elseif ($status === 'Hadir') {
                    $hadir[] = $d->id;
                }
            }

            if ($terkumpul > 0 && count($hadir) > 0) {
                $porsi = intdiv($terkumpul, count($hadir));

                foreach ($hadir as $id) {
                    $subsidi[$id] += $porsi;
                }
            }
        }

        foreach ($details as $d) {
            $tambahan = $subsidi[$d->id] ?? 0;

            $d->update([
                'nominal_tambahan'    => $tambahan,
                'keterangan_tambahan' => $tambahan > 0 ? 'Subsidi kehadiran kelas' : null,
                'take_home_pay'       => $d->gaji_pokok + $d->total_tunjangan - $d->total_potongan + $tambahan,
            ]);
        }
    }

    private function hitungTotalHeader(PayrollHeader $header): void
    {
        $details = $header->details()->get();

        $header->update([
            'total_gaji_pokok' => $details->sum('gaji_pokok'),
            'total_tunjangan'  => $details->sum('total_tunjangan'),
            'total_potongan'   => $details->sum('total_potongan'),
            'total_tambahan'   => $details->sum('nominal_tambahan'),
            'grand_total'      => $details->sum('take_home_pay'),
        ]);
    }
}