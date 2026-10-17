<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\Inbound\Pembayaran;
use App\Models\Keuangan\Inbound\Tagihan;
use App\Models\Keuangan\MasterData\Kelas;
use App\Models\Keuangan\Outbound\RkasDetail;
use App\Models\Keuangan\Outbound\RkasHeader;
use App\Models\Keuangan\Payroll\PayrollHeader;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class PimpinanController extends Controller
{
    /** Sama dengan DashboardController — halaman ini juga murni informatif. */
    private const CACHE_MENIT = 10;

    public function index()
    {
        $tahun = now()->year;

        $data = Cache::remember(
            "keuangan.pimpinan.$tahun",
            now()->addMinutes(self::CACHE_MENIT),
            fn () => [
                'tunggakanKelas' => $this->tunggakanPerKelas(),
                'keringanan'     => $this->keringananPerAlasan($tahun),
                'bebanGaji'      => $this->bebanGaji($tahun),
                'rkas'           => $this->realisasiRkas(),
            ]
        );

        return Inertia::render('Keuangan/Pimpinan', [
            'tahun'          => $tahun,
            'tunggakanKelas' => $data['tunggakanKelas'],
            'keringanan'     => $data['keringanan'],
            'bebanGaji'      => $data['bebanGaji'],
            'rkas'           => $data['rkas'],
        ]);
    }

    /**
     * Tunggakan per rombel — satu query bergrup, bukan satu query per kelas.
     * Jumlah kelas bertambah tiap tahun ajaran karena satu baris `kelas`
     * berlaku untuk satu tahun saja, dan halaman ini tidak boleh ikut
     * melambat seiring waktu.
     *
     * Syarat header 'Published' mengikuti LaporanController::tunggakan().
     * Tagihan di gelombang Draft belum pernah sampai ke wali murid, jadi
     * bukan piutang — dan dua halaman yang menghitung hal sama dengan
     * aturan berbeda adalah cara tercepat membuat orang berhenti percaya
     * pada keduanya.
     */
    private function tunggakanPerKelas()
    {
        $statistik = Tagihan::query()
            ->join('santris', 'santris.id', '=', 'invoices.student_id')
            ->join('invoice_headers', 'invoice_headers.id', '=', 'invoices.invoice_header_id')
            ->whereIn('invoices.status', ['Unpaid', 'Installment'])
            ->where('invoice_headers.status', 'Published')
            ->whereNull('invoice_headers.deleted_at')
            ->whereNull('santris.deleted_at')
            ->where('santris.status', 'aktif')
            ->groupBy('santris.kelas_id')
            ->selectRaw('santris.kelas_id AS kelas_id,
                         COUNT(DISTINCT invoices.student_id) AS siswa_nunggak,
                         COALESCE(SUM(invoices.remaining_balance), 0) AS nominal')
            ->get()
            ->keyBy('kelas_id');

        $baris = Kelas::query()
            ->where('is_active', true)
            ->withCount(['siswas as total_siswa' => fn ($q) => $q->where('status', 'aktif')])
            ->orderBy('name')
            ->get()
            ->map(function ($kelas) use ($statistik) {
                $s       = $statistik->get($kelas->id);
                $nunggak = (int) ($s->siswa_nunggak ?? 0);
                $total   = (int) $kelas->total_siswa;

                return [
                    'nama'    => $kelas->name,
                    'total'   => $total,
                    'nunggak' => $nunggak,
                    'nominal' => (float) ($s->nominal ?? 0),
                    'persen'  => $total > 0 ? round($nunggak / $total * 100, 1) : 0,
                ];
            });

        // Siswa tanpa rombel tetap ditagih — route master-data pun menyediakan
        // 'tanpa-kelas'. Tunggakan mereka tidak boleh hilang dari halaman ini
        // hanya karena tidak punya baris kelas untuk ditempeli.
        $lepas = $statistik->get(null) ?? $statistik->get('');

        if ($lepas && (int) $lepas->siswa_nunggak > 0) {
            $baris->push([
                'nama'    => 'Tanpa kelas',
                'total'   => (int) $lepas->siswa_nunggak,
                'nunggak' => (int) $lepas->siswa_nunggak,
                'nominal' => (float) $lepas->nominal,
                'persen'  => 100,
            ]);
        }

        return $baris->sortByDesc('nominal')->values();
    }

    /**
     * Pembatalan tagihan sepanjang tahun, dikelompokkan menurut alasannya —
     * menjawab pertanyaan yang sudah ditulis sendiri di model Tagihan:
     * "Berapa yang kita bebaskan tahun ini?"
     *
     * Nominalnya diambil dari total_amount, bukan remaining_balance.
     * Pembatalan menolkan sisa tagihan, jadi remaining_balance selalu 0 di
     * sini. Lagi pula tagihan hanya boleh dibatalkan selama belum menerima
     * pembayaran sama sekali, sehingga yang dibebaskan memang selalu sama
     * dengan nilai penuh tagihannya.
     */
    private function keringananPerAlasan(int $tahun)
    {
        return Tagihan::query()
            ->whereNotNull('cancelled_at')
            ->whereYear('cancelled_at', $tahun)
            ->groupBy('cancel_reason')
            ->selectRaw('cancel_reason,
                         COUNT(*) AS jumlah,
                         COALESCE(SUM(total_amount), 0) AS nominal')
            ->get()
            ->map(fn ($r) => [
                'label'   => Tagihan::ALASAN_BATAL[$r->cancel_reason] ?? 'Tanpa alasan',
                'jumlah'  => (int) $r->jumlah,
                'nominal' => (float) $r->nominal,
            ])
            ->sortByDesc('nominal')
            ->values();
    }

    /**
     * Beban gaji terhadap pemasukan tahun berjalan.
     *
     * Penggajian dihitung pada tahap 'dibayar' — saat uangnya benar-benar
     * keluar — sejajar dengan pemasukan yang dihitung pada 'disetujui'.
     * Batch yang baru disetujui tapi belum dibayarkan sengaja tidak masuk:
     * ia belum menjadi beban, hanya rencana.
     */
    private function bebanGaji(int $tahun): array
    {
        $gaji = (float) PayrollHeader::where('status_approval', 'dibayar')
            ->where('periode_tahun', $tahun)
            ->sum('grand_total');

        $pemasukan = (float) Pembayaran::where('status_approval', 'disetujui')
            ->whereYear('payment_date', $tahun)
            ->sum('nominal');

        return [
            'gaji'      => $gaji,
            'pemasukan' => $pemasukan,
            'persen'    => $pemasukan > 0 ? round($gaji / $pemasukan * 100, 1) : 0,
            // Nol karena belum ada penggajian sama sekali berbeda artinya
            // dari nol karena gajinya memang belum dibayarkan. Yang pertama
            // bukan angka, hanya ketiadaan data — dan menampilkannya sebagai
            // 0% membuat halaman tampak rusak.
            'ada_data'  => PayrollHeader::where('periode_tahun', $tahun)->exists(),
        ];
    }

    /**
     * Serapan anggaran periode RKAS terbaru yang sudah final.
     *
     * Aturan baris dan kolomnya mengikuti LaporanController::realisasiRkas()
     * — is_parent dikecualikan supaya pagu induk tidak terhitung dua kali
     * bersama rinciannya.
     */
    private function realisasiRkas(): ?array
    {
        $header = RkasHeader::whereIn('status', ['Final', 'Closed'])
            ->orderByDesc('created_at')
            ->first(['id', 'tahun_ajaran', 'status']);

        if (! $header) {
            return null;
        }

        $dasar = RkasDetail::where('rkas_header_id', $header->id)->where('is_parent', false);

        $anggaran = (float) (clone $dasar)->sum('pagu_anggaran');
        $terserap = (float) (clone $dasar)->sum('nominal_realisasi');

        // Diurutkan menurut PERSENTASE serapan, bukan nominal. Pos yang hampir
        // habis itu yang butuh keputusan; pos besar yang baru terpakai
        // seperempatnya tidak menuntut apa pun.
        $pos = (clone $dasar)
            ->orderByRaw('nominal_realisasi / NULLIF(pagu_anggaran, 0) DESC')
            ->limit(5)
            ->get(['kode_kegiatan', 'nama_kegiatan', 'pagu_anggaran', 'nominal_realisasi'])
            ->map(fn ($d) => [
                'kode'      => $d->kode_kegiatan,
                'nama'      => $d->nama_kegiatan,
                'pagu'      => (float) $d->pagu_anggaran,
                'realisasi' => (float) $d->nominal_realisasi,
                'persen'    => (float) $d->pagu_anggaran > 0
                    ? round((float) $d->nominal_realisasi / (float) $d->pagu_anggaran * 100, 1)
                    : 0,
            ]);

        return [
            'periode'  => $header->tahun_ajaran,
            'status'   => $header->status,
            'anggaran' => $anggaran,
            'terserap' => $terserap,
            'sisa'     => $anggaran - $terserap,
            'persen'   => $anggaran > 0 ? round($terserap / $anggaran * 100, 1) : 0,
            'pos'      => $pos,
        ];
    }
}