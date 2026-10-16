<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\Inbound\Tagihan;
use App\Models\Keuangan\MasterData\Kelas;
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
            ]
        );

        return Inertia::render('Keuangan/Pimpinan', [
            'tahun'          => $tahun,
            'tunggakanKelas' => $data['tunggakanKelas'],
            'keringanan'     => $data['keringanan'],
        ]);
    }

    /**
     * Tunggakan per rombel — satu query bergrup, bukan satu query per kelas.
     * Jumlah kelas bertambah tiap tahun ajaran karena satu baris `kelas`
     * berlaku untuk satu tahun saja, dan halaman ini tidak boleh ikut
     * melambat seiring waktu.
     */
    private function tunggakanPerKelas()
    {
        $statistik = Tagihan::query()
            ->join('santris', 'santris.id', '=', 'invoices.student_id')
            ->whereIn('invoices.status', ['Unpaid', 'Installment'])
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
     * Dua angka dilaporkan karena pertanyaannya memang dua. Nilai tagihan
     * adalah nominal penuh yang dibatalkan, dan itu yang relevan untuk
     * 'salah input' — seluruh tagihannya memang tidak pernah sah. Nilai
     * dibebaskan adalah sisa yang belum terbayar saat dibatalkan, dan itulah
     * uang yang benar-benar tidak jadi masuk.
     */
    private function keringananPerAlasan(int $tahun)
    {
        return Tagihan::query()
            ->whereNotNull('cancelled_at')
            ->whereYear('cancelled_at', $tahun)
            ->groupBy('cancel_reason')
            ->selectRaw('cancel_reason,
                         COUNT(*) AS jumlah,
                         COALESCE(SUM(total_amount), 0)      AS nominal_tagihan,
                         COALESCE(SUM(remaining_balance), 0) AS nominal_dibebaskan')
            ->get()
            ->map(fn ($r) => [
                'label'      => Tagihan::ALASAN_BATAL[$r->cancel_reason] ?? 'Tanpa alasan',
                'jumlah'     => (int) $r->jumlah,
                'tagihan'    => (float) $r->nominal_tagihan,
                'dibebaskan' => (float) $r->nominal_dibebaskan,
            ])
            ->sortByDesc('dibebaskan')
            ->values();
    }
}