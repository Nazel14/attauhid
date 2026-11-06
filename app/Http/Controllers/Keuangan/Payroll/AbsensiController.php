<?php

namespace App\Http\Controllers\Keuangan\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\MasterData\Pegawai;
use App\Models\Keuangan\Payroll\Absensi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class AbsensiController extends Controller
{
    public function index(Request $request)
    {
        $bulan = str_pad($request->input('bulan', date('m')), 2, '0', STR_PAD_LEFT);
        $tahun = $request->input('tahun', date('Y'));

        $daysInMonth = Carbon::createFromDate($tahun, $bulan, 1)->daysInMonth;

        $query = Pegawai::where('is_active', true)
            ->with(['absensi' => fn ($q) => $q->whereYear('date', $tahun)->whereMonth('date', $bulan)])
            ->orderBy('name');

        // 'ilike' diganti 'like' — ilike hanya ada di PostgreSQL.
        $query->when($request->search, fn ($q, $cari) => $q->where('name', 'like', "%{$cari}%"));

        return Inertia::render('Keuangan/Penggajian/DataAbsensi', [
            'pegawai'     => $query->paginate(15)->withQueryString(),
            'daysInMonth' => $daysInMonth,
            'filters'     => [
                'bulan'  => $bulan,
                'tahun'  => (string) $tahun,
                'search' => $request->search ?? '',
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            // Tabel employees sudah dilebur ke gurus.
            'employee_id' => 'required|exists:gurus,id',
            'date'        => 'required|date',
            'status'      => 'nullable|in:' . implode(',', Absensi::STATUS),
        ]);

        if (empty($data['status'])) {
            // Status dikosongkan → barisnya dihapus, bukan disimpan sebagai null.
            Absensi::where('employee_id', $data['employee_id'])
                ->whereDate('date', $data['date'])
                ->delete();
        } else {
            Absensi::updateOrCreate(
                ['employee_id' => $data['employee_id'], 'date' => $data['date']],
                ['status' => $data['status']]
            );
        }

        // Tanpa flash message — matriks ini diklik berkali-kali,
        // toast di tiap klik justru mengganggu.
        return redirect()->back();
    }

    /**
     * Simpan banyak perubahan sekaligus (tombol "Simpan" di timesheet).
     *
     * Payload: perubahan = [{ employee_id, date, status|null }, ...]
     * status kosong/null → baris absensi tanggal itu dihapus.
     * Semua perubahan masuk dalam satu transaksi: gagal satu, batal semua.
     */
    public function simpan(Request $request)
    {
        $data = $request->validate([
            'perubahan'               => 'required|array|min:1|max:3000',
            'perubahan.*.employee_id' => 'required|integer',
            'perubahan.*.date'        => 'required|date_format:Y-m-d',
            'perubahan.*.status'      => 'nullable|in:' . implode(',', Absensi::STATUS),
        ], [
            'perubahan.required' => 'Tidak ada perubahan untuk disimpan.',
        ]);

        $perubahan = collect($data['perubahan']);

        // Cek pegawai sekali query, bukan rule `exists` per baris
        // (3.000 baris = 3.000 query).
        //
        // Draf tersimpan di browser dan bisa berumur berhari-hari, jadi
        // pegawai di dalamnya mungkin sudah dihapus. Baris seperti itu
        // DILEWATI, bukan menggagalkan semuanya — kalau ditolak, draf itu
        // akan terus gagal disimpan dan admin terjebak.
        $valid     = Pegawai::whereIn('id', $perubahan->pluck('employee_id')->unique())->pluck('id')->flip();
        $dilewati  = $perubahan->reject(fn ($p) => $valid->has($p['employee_id']))->count();
        $perubahan = $perubahan->filter(fn ($p) => $valid->has($p['employee_id']));

        DB::transaction(function () use ($perubahan) {
            foreach ($perubahan as $p) {
                if (empty($p['status'])) {
                    Absensi::where('employee_id', $p['employee_id'])
                        ->whereDate('date', $p['date'])
                        ->delete();
                    continue;
                }

                Absensi::updateOrCreate(
                    ['employee_id' => $p['employee_id'], 'date' => $p['date']],
                    ['status' => $p['status']]
                );
            }
        });

        $pesan = $perubahan->count() . ' perubahan absensi berhasil disimpan.';
        if ($dilewati) {
            $pesan .= " {$dilewati} dilewati karena pegawainya sudah tidak ada.";
        }

        return redirect()->back()->with('success', $pesan);
    }

    public function bulkHadir(Request $request)
    {
        $data = $request->validate([
            'bulan' => 'required|string|max:2',
            'tahun' => 'required|string|size:4',
        ]);

        $bulan = str_pad($data['bulan'], 2, '0', STR_PAD_LEFT);
        $tahun = $data['tahun'];
        $hari  = Carbon::createFromDate($tahun, $bulan, 1)->daysInMonth;

        $libur    = $this->hariLibur($tahun, $bulan);
        $pegawais = Pegawai::where('is_active', true)->pluck('id');

        if ($pegawais->isEmpty()) {
            return redirect()->back()->withErrors(['bulk' => 'Belum ada pegawai aktif.']);
        }

        // Versi asli memanggil firstOrCreate di dalam dua loop bersarang —
        // 50 pegawai × 31 hari = 1.550 query. Di sini: satu query untuk
        // membaca yang sudah ada, satu insert untuk sisanya.
        $sudahAda = Absensi::whereIn('employee_id', $pegawais)
            ->whereYear('date', $tahun)
            ->whereMonth('date', $bulan)
            ->get(['employee_id', 'date'])
            ->map(fn ($a) => $a->employee_id . '|' . $a->date->format('Y-m-d'))
            ->flip();

        $baris = [];
        $now   = now();

        for ($h = 1; $h <= $hari; $h++) {
            $tanggal = sprintf('%s-%s-%02d', $tahun, $bulan, $h);
            $dow     = Carbon::parse($tanggal)->dayOfWeek;

            $akhirPekan = in_array($dow, [Carbon::SATURDAY, Carbon::SUNDAY], true);

            if ($akhirPekan || in_array($tanggal, $libur, true)) {
                continue;
            }

            foreach ($pegawais as $id) {
                if ($sudahAda->has($id . '|' . $tanggal)) {
                    continue;
                }

                $baris[] = [
                    'employee_id' => $id,
                    'date'        => $tanggal,
                    'status'      => 'Hadir',
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }
        }

        if (empty($baris)) {
            return redirect()->back()->with('success', 'Semua hari kerja bulan ini sudah terisi.');
        }

        DB::transaction(function () use ($baris) {
            foreach (array_chunk($baris, 500) as $chunk) {
                Absensi::insert($chunk);
            }
        });

        return redirect()->back()->with('success',
            count($baris) . ' entri kehadiran berhasil diisi. Status yang sudah ada tidak ditimpa.');
    }

    public function getHolidays(Request $request)
    {
        $tahun = $request->query('year', date('Y'));
        $bulan = str_pad($request->query('month', date('m')), 2, '0', STR_PAD_LEFT);

        $libur = $this->hariLiburLengkap($tahun);

        return response()->json(
            collect($libur)
                ->filter(fn ($h) => substr($h['date'], 5, 2) === $bulan)
                ->map(fn ($h) => ['tanggal' => $h['date'], 'keterangan' => $h['localName']])
                ->values()
        );
    }

    /* ================= Helper ================= */

    /** Tanggal libur nasional pada satu bulan, format Y-m-d. */
    private function hariLibur(string $tahun, string $bulan): array
    {
        return collect($this->hariLiburLengkap($tahun))
            ->filter(fn ($h) => substr($h['date'], 5, 2) === $bulan)
            ->pluck('date')
            ->all();
    }

    /**
     * Data libur nasional satu tahun penuh, di-cache 30 hari.
     *
     * Tanpa cache, tiap membuka halaman absensi memanggil API eksternal —
     * lambat, dan gagal total bila hosting memblokir outbound HTTP.
     * Bila API tidak terjangkau, hasilnya array kosong: sistem hanya
     * memperlakukan Sabtu–Minggu sebagai libur.
     */
    private function hariLiburLengkap(string $tahun): array
    {
        return Cache::remember("libur-nasional-{$tahun}", now()->addDays(30), function () use ($tahun) {
            try {
                $res = Http::timeout(5)->get("https://date.nager.at/api/v3/PublicHolidays/{$tahun}/ID");

                return $res->successful() ? $res->json() : [];
            } catch (\Throwable $e) {
                return [];
            }
        });
    }
}