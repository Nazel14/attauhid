<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\Inbound\Pembayaran;
use App\Models\Keuangan\Inbound\Tagihan;
use App\Models\Keuangan\MasterData\Siswa;
use App\Models\Keuangan\Outbound\Pengeluaran;
use App\Models\Keuangan\Outbound\RkasHeader;
use App\Models\Keuangan\Payroll\PayrollHeader;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /** Masa simpan angka ringkasan & grafik. */
    private const CACHE_MENIT = 10;

    public function index()
    {
        $user  = Auth::user();
        $tahun = now()->year;

        // Yang di-cache HANYA angka informatif: ringkasan tahun berjalan,
        // grafik 6 bulan, dan tren tunggakan. Ketiganya sama untuk semua
        // pemegang akses keuangan dan telat 10 menit tidak mengubah keputusan
        // siapa pun.
        //
        // Notifikasi dan daftar transaksi terbaru sengaja TIDAK ikut: itu
        // justru angka yang dipakai bendahara untuk bertindak. Kalau
        // di-cache, badge "8 pembayaran perlu disetujui" tidak berubah
        // sesudah disetujui — terasa seperti sistem rusak, bukan cepat.
        $ringkasan = Cache::remember(
            "keuangan.dashboard.ringkasan.$tahun",
            now()->addMinutes(self::CACHE_MENIT),
            function () use ($tahun) {
                $totalIncome = Pembayaran::where('status_approval', 'disetujui')
                    ->whereYear('payment_date', $tahun)
                    ->sum('nominal');

                $totalExpenses = Pengeluaran::where('status_approval', 'selesai')
                    ->whereYear('tanggal_pengajuan', $tahun)
                    ->sum('nominal_realisasi');

                // santris tidak punya is_active; statusnya enum aktif/lulus/keluar.
                // scopeAktif() sudah didefinisikan di model Santri.
                $totalSiswa = Siswa::aktif()->count();

                $siswaNunggak = Tagihan::where('status', '!=', 'Paid')
                    ->distinct('student_id')
                    ->count('student_id');

                return [
                    'stats' => [
                        'totalIncome'       => (float) $totalIncome,
                        'totalExpenses'     => (float) $totalExpenses,
                        'savings'           => (float) $totalIncome - (float) $totalExpenses,
                        'arrearsPercentage' => $totalSiswa > 0 ? round($siswaNunggak / $totalSiswa * 100, 1) : 0,
                        'incomeGrowth'      => $this->pertumbuhan(
                            $totalIncome,
                            Pembayaran::where('status_approval', 'disetujui')->whereYear('payment_date', $tahun - 1)->sum('nominal')
                        ),
                        'expensesGrowth'    => $this->pertumbuhan(
                            $totalExpenses,
                            Pengeluaran::where('status_approval', 'selesai')->whereYear('tanggal_pengajuan', $tahun - 1)->sum('nominal_realisasi')
                        ),
                    ],
                    'monthlyData'  => $this->dataBulanan(),
                    'arrearsTrend' => $this->trenTunggakan($totalSiswa),
                ];
            }
        );

        return Inertia::render('Keuangan/Dashboard', [
            'stats'          => $ringkasan['stats'],
            'monthlyData'    => $ringkasan['monthlyData'],
            'arrearsTrend'   => $ringkasan['arrearsTrend'],
            'recentIncome'   => $this->pemasukanTerbaru(),
            'recentExpenses' => $this->pengeluaranTerbaru(),
            'notifications'  => $notifikasi = $this->buildNotifications($user),
            'actionSummary'  => $this->buildActionSummary($notifikasi),
        ]);
    }

    private function pertumbuhan($sekarang, $sebelumnya): float
    {
        $sebelumnya = (float) $sebelumnya;

        return $sebelumnya > 0
            ? round(((float) $sekarang - $sebelumnya) / $sebelumnya * 100, 1)
            : 0.0;
    }

    /** Pemasukan vs pengeluaran 6 bulan terakhir, dalam satuan juta rupiah. */
    private function dataBulanan(): array
    {
        $hasil = [];

        for ($i = 5; $i >= 0; $i--) {
            $tgl = now()->subMonths($i);

            $masuk = Pembayaran::where('status_approval', 'disetujui')
                ->whereYear('payment_date', $tgl->year)
                ->whereMonth('payment_date', $tgl->month)
                ->sum('nominal');

            $keluar = Pengeluaran::where('status_approval', 'selesai')
                ->whereYear('tanggal_pengajuan', $tgl->year)
                ->whereMonth('tanggal_pengajuan', $tgl->month)
                ->sum('nominal_realisasi');

            $hasil[] = [
                'month'    => $tgl->translatedFormat('M'),
                'income'   => round((float) $masuk / 1_000_000, 2),
                'expenses' => round((float) $keluar / 1_000_000, 2),
            ];
        }

        return $hasil;
    }

    private function trenTunggakan(int $totalSiswa): array
    {
        $hasil = [];

        for ($i = 5; $i >= 0; $i--) {
            $tgl = now()->subMonths($i);

            // Versi edufinance memakai whereYear('<=') digabung whereMonth('<=')
            // yang keliru untuk lintas tahun. Di sini cukup satu batas tanggal.
            $nunggak = Tagihan::where('status', '!=', 'Paid')
                ->where('created_at', '<=', $tgl->copy()->endOfMonth())
                ->distinct('student_id')
                ->count('student_id');

            $hasil[] = [
                'month'      => $tgl->translatedFormat('M'),
                'percentage' => $totalSiswa > 0 ? round($nunggak / $totalSiswa * 100, 1) : 0,
            ];
        }

        return $hasil;
    }

    private function pemasukanTerbaru()
    {
        return Pembayaran::with('siswa:id,name')
            ->where('status_approval', 'disetujui')
            ->orderByDesc('payment_date')
            ->limit(4)
            ->get()
            ->map(fn ($p) => [
                'id'          => $p->id,
                'description' => $p->siswa?->name
                    ? 'Pembayaran · ' . $p->siswa->name
                    : 'Dana luar · ' . mb_strimwidth((string) $p->catatan, 0, 28, '…'),
                'date'        => $p->payment_date?->translatedFormat('d M Y') ?? '-',
                'method'      => $p->payment_method ?: 'Tunai',
                'amount'      => (float) $p->nominal,
            ]);
    }

    private function pengeluaranTerbaru()
    {
        return Pengeluaran::where('status_approval', 'selesai')
            ->orderByDesc('tanggal_pengajuan')
            ->limit(4)
            ->get()
            ->map(fn ($e) => [
                'id'          => $e->id,
                'description' => mb_strimwidth((string) ($e->deskripsi ?: 'Pengeluaran'), 0, 40, '…'),
                'date'        => $e->tanggal_pengajuan?->translatedFormat('d M Y') ?? '-',
                'category'    => 'Operasional',
                'amount'      => (float) ($e->nominal_realisasi ?: $e->nominal_pengajuan),
            ]);
    }

    private function buildNotifications($user): array
    {
        if (!$user) {
            return [];
        }

        $n = [];

        $this->tambah($n, $user, 'pembayaran.approve_diperiksa', 'pembayaran-diperiksa',
            fn () => Pembayaran::where('status_approval', 'draft')->count(),
            'pembayaran', 'perlu diperiksa', 'diperiksa',
            'keuangan.pemasukan.pembayaran.index', ['statusFilter' => 'draft']);

        $this->tambah($n, $user, 'pengeluaran.approve_diperiksa', 'pengeluaran-diperiksa',
            fn () => Pengeluaran::where('status_approval', 'draft')->count(),
            'pengeluaran', 'perlu diperiksa', 'diperiksa',
            'keuangan.pengeluaran.pengajuan', ['status' => 'draft']);

        $this->tambah($n, $user, 'penggajian.approve_diperiksa', 'penggajian-diperiksa',
            fn () => PayrollHeader::where('status_approval', 'draft')->count(),
            'batch penggajian', 'perlu diperiksa', 'diperiksa',
            'keuangan.penggajian.data', ['status' => 'draft']);

        $this->tambah($n, $user, 'rkas.approve_diperiksa', 'rkas-diperiksa',
            fn () => RkasHeader::where('status', 'Draft')->count(),
            'RKAS', 'perlu diperiksa', 'diperiksa',
            'keuangan.pengeluaran.rkas', ['statusFilter' => 'Draft']);

        $this->tambah($n, $user, 'pembayaran.approve_disetujui', 'pembayaran-disetujui',
            fn () => Pembayaran::where('status_approval', 'diperiksa')->count(),
            'pembayaran', 'perlu disetujui', 'disetujui',
            'keuangan.pemasukan.pembayaran.index', ['statusFilter' => 'diperiksa']);

        $this->tambah($n, $user, 'pengeluaran.approve_disetujui', 'pengeluaran-disetujui',
            fn () => Pengeluaran::where('status_approval', 'diperiksa')->count(),
            'pengeluaran', 'perlu disetujui', 'disetujui',
            'keuangan.pengeluaran.pengajuan', ['status' => 'diperiksa']);

        $this->tambah($n, $user, 'penggajian.approve_disetujui', 'penggajian-disetujui',
            fn () => PayrollHeader::where('status_approval', 'diperiksa')->count(),
            'batch penggajian', 'perlu disetujui', 'disetujui',
            'keuangan.penggajian.data', ['status' => 'diperiksa']);

        $this->tambah($n, $user, 'penggajian.approve_dibayar', 'penggajian-dibayar',
            fn () => PayrollHeader::where('status_approval', 'disetujui')->count(),
            'batch penggajian', 'perlu dibayarkan', 'dibayarkan',
            'keuangan.penggajian.data', ['status' => 'disetujui']);

        $this->tambah($n, $user, 'rkas.approve_final', 'rkas-final',
            fn () => RkasHeader::where('status', 'Diperiksa')->count(),
            'RKAS', 'perlu difinalkan', 'difinalkan',
            'keuangan.pengeluaran.rkas', ['statusFilter' => 'Diperiksa']);

        $this->tambah($n, $user, 'rkas.approve_tutup_batch', 'rkas-closed',
            fn () => RkasHeader::where('status', 'Final')->count(),
            'RKAS', 'perlu ditutup', 'ditutup',
            'keuangan.pengeluaran.rkas', ['statusFilter' => 'Final']);

        $this->tambah($n, $user, 'pengeluaran.buat_lpj', 'pengeluaran-lpj',
            fn () => Pengeluaran::where('status_approval', 'disetujui')->count(),
            'pengeluaran', 'perlu dibuatkan LPJ', 'ditindaklanjuti',
            'keuangan.pengeluaran.pengajuan', ['status' => 'disetujui']);

        $this->tambah($n, $user, 'pembayaran.buat', 'pembayaran-draft-sendiri',
            fn () => Pembayaran::where('status_approval', 'draft')->where('created_by', $user->id)->count(),
            'pembayaran', 'masih draft', 'ditindaklanjuti',
            'keuangan.pemasukan.pembayaran.index', ['statusFilter' => 'draft']);

        return $n;
    }

    /**
     * Modul keuangan diport bertahap. Notifikasi yang route tujuannya belum
     * terdaftar dilewati — tanpa ini route() melempar RouteNotFoundException
     * dan dashboard jadi error 500.
     */
    private function tambah(array &$n, $user, string $permission, string $key, callable $hitung,
                            string $dokumen, string $status, string $aksi, string $routeName, array $params): void
    {
        if (!$user->can($permission) || !Route::has($routeName)) {
            return;
        }

        $jumlah = $hitung();

        if ($jumlah < 1) {
            return;
        }

        $n[] = [
            'key'    => $key,
            'title'  => "Ada {$jumlah} {$dokumen} yang {$status}",
            'url'    => route($routeName, $params),
            'count'  => $jumlah,
            'action' => $aksi,
        ];
    }

    private function buildActionSummary(array $notifications): ?string
    {
        $total = collect($notifications)->sum('count');

        if ($total < 1) {
            return null;
        }

        $aksi = array_values(array_unique(array_column($notifications, 'action')));

        return count($aksi) === 1
            ? "Ada {$total} dokumen yang perlu {$aksi[0]}"
            : "Ada {$total} dokumen yang memerlukan tindakan Anda";
    }
}