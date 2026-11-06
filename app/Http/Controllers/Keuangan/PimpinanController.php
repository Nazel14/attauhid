<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\Inbound\Pembayaran;
use App\Models\Keuangan\Inbound\Tagihan;
use App\Models\Keuangan\MasterData\Kelas;
use App\Models\Keuangan\Outbound\RkasDetail;
use App\Models\Keuangan\Outbound\RkasHeader;
use App\Models\Keuangan\Payroll\PayrollHeader;
use App\Models\Santri;
use App\Models\Setoran;
use App\Models\TahunAjaran;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PimpinanController extends Controller
{
    /** Sama dengan DashboardController — halaman ini juga murni informatif. */
    private const CACHE_MENIT = 10;

    /** Ambang "lama tidak setor". Dua pekan, bukan sebulan: sebulan terlalu
     *  terlambat untuk menolong anak yang mulai berhenti. */
    private const HARI_VAKUM = 14;

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
                'tahfizh'        => $this->ringkasanTahfizh(),
            ]
        );

        return Inertia::render('Keuangan/Pimpinan', [
            'tahun'          => $tahun,
            'tunggakanKelas' => $data['tunggakanKelas'],
            'keringanan'     => $data['keringanan'],
            'bebanGaji'      => $data['bebanGaji'],
            'rkas'           => $data['rkas'],
            'tahfizh'        => $data['tahfizh'],
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

    /**
     * Separuh gambaran yang bukan uang: berapa santri aktif, sejauh mana
     * capaian hafalannya, dan siapa yang berhenti setor.
     *
     * Mengembalikan null bila modul raport dimatikan. Halaman ini hidup di
     * panel keuangan, jadi ia tidak boleh ikut rusak di instalasi klien
     * yang hanya memakai satu modul — penjagaannya sama dengan yang dipakai
     * routes/web.php untuk memuat rute raport.
     *
     * Dikerjakan dengan tiga query agregat, bukan dengan memanggil
     * Santri::getPersenHafalanByTahunAjaran() per santri. Accessor itu
     * benar, tapi memanggilnya untuk lima puluh santri berarti seratus
     * query untuk satu kartu.
     */
    private function ringkasanTahfizh(): ?array
    {
        if (! config('modules.raport')) {
            return null;
        }

        $santri = Santri::aktif()->orderBy('name')->get(['id', 'name', 'target_juz']);

        if ($santri->isEmpty()) {
            return [
                'aktif'       => 0,
                'rataCapaian' => 0,
                'hariVakum'   => self::HARI_VAKUM,
                'jumlahVakum' => 0,
                'vakum'       => [],
            ];
        }

        $taId  = TahunAjaran::getAktifId();
        $ids   = $santri->pluck('id');
        $batas = now()->subDays(self::HARI_VAKUM)->startOfDay();

        // Juz yang sudah disetor tahun ajaran berjalan. kategori_setoran
        // NULL berarti baris itu presensi tanpa setoran — bukan hafalan.
        $tersetor = Setoran::query()
            ->whereIn('santri_id', $ids)
            ->whereNotNull('kategori_setoran')
            ->when($taId, fn ($q) => $q->where('tahun_ajaran_id', $taId))
            ->groupBy('santri_id')
            ->selectRaw('santri_id, COALESCE(SUM(total_juz), 0) AS juz')
            ->pluck('juz', 'santri_id');

        // Target khusus tahun ajaran ini; yang tidak punya memakai
        // santris.target_juz. Urutan jatuh yang sama dengan
        // Santri::getPersenHafalanByTahunAjaran().
        $targetTa = $taId
            ? DB::table('santri_target_hafalan')
                ->whereIn('santri_id', $ids)
                ->where('tahun_ajaran_id', $taId)
                ->pluck('target_juz', 'santri_id')
            : collect();

        // Setoran terakhir per santri — lintas tahun ajaran, karena yang
        // ditanyakan adalah "kapan terakhir anak ini setor", bukan "kapan
        // terakhir di tahun ini".
        $terakhir = Setoran::query()
            ->whereIn('santri_id', $ids)
            ->whereNotNull('kategori_setoran')
            ->groupBy('santri_id')
            ->selectRaw('santri_id, MAX(tanggal) AS tanggal')
            ->pluck('tanggal', 'santri_id');

        $persen = [];
        $vakum  = [];

        foreach ($santri as $s) {
            $target = (float) ($targetTa[$s->id] ?? $s->target_juz ?? 0);

            // Santri tanpa target tidak ikut menghitung rata-rata. Memberinya
            // nilai nol akan menarik angka seluruh pesantren ke bawah karena
            // targetnya belum diisi, bukan karena anaknya tertinggal.
            if ($target > 0) {
                $persen[] = min((float) ($tersetor[$s->id] ?? 0) / $target * 100, 100);
            }

            $tgl = $terakhir[$s->id] ?? null;

            if (! $tgl || Carbon::parse($tgl)->lt($batas)) {
                $vakum[] = [
                    'nama'     => $s->name,
                    'terakhir' => $tgl ? Carbon::parse($tgl)->translatedFormat('d M Y') : null,
                    'hari'     => $tgl
                        ? (int) abs(Carbon::parse($tgl)->startOfDay()->diffInDays(now()->startOfDay()))
                        : null,
                ];
            }
        }

        // Yang paling lama vakum lebih dulu. Yang belum pernah setor sama
        // sekali ditaruh paling atas — itu kasus paling jelas dan paling
        // mudah terlewat.
        usort($vakum, fn ($a, $b) => ($b['hari'] ?? PHP_INT_MAX) <=> ($a['hari'] ?? PHP_INT_MAX));

        return [
            'aktif'       => $santri->count(),
            'rataCapaian' => count($persen) > 0 ? round(array_sum($persen) / count($persen), 1) : 0,
            'hariVakum'   => self::HARI_VAKUM,
            'jumlahVakum' => count($vakum),
            'vakum'       => array_slice($vakum, 0, 5),
        ];
    }
}