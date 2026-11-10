<?php

namespace App\Http\Controllers\Keuangan\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\MasterData\Kelas;
use App\Models\Keuangan\MasterData\Siswa;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SiswaController extends Controller
{
    /** Penanda kelas semu untuk siswa yang kelas_id-nya null. */
    private const TANPA_KELAS = 'tanpa-kelas';

    /**
     * Halaman pemilih kelas. Daftar santri ada di detail(), sama seperti
     * alur panel raport — bendahara memilih kelas dulu baru lihat siswanya.
     */
    public function index(Request $request)
    {
        $kelas = Kelas::query()
            ->withCount([
                'santris',
                'santris as reguler_count' => fn ($q) => $q->where('student_type', 'Reguler'),
                'santris as fullday_count' => fn ($q) => $q->where('student_type', 'Fullday'),
                'santris as ditahan_count' => fn ($q) => $q->where('is_raport_held', true),
            ])
            ->with('guru:id,name')
            ->when($request->search, fn ($q, $cari) => $q->where(function ($w) use ($cari) {
                $w->where('name', 'like', "%{$cari}%")
                  ->orWhere('tahun_ajaran', 'like', "%{$cari}%");
            }))
            ->when($request->tahunFilter, fn ($q, $t) => $q->where('tahun_ajaran', $t))
            ->orderBy('tahun_ajaran', 'desc')
            ->orderBy('name')
            ->get();

        // Siswa tanpa kelas dirangkum jadi satu kartu semu.
        $tanpaKelas = Siswa::whereNull('kelas_id')
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when student_type = 'Reguler' then 1 else 0 end) as reguler")
            ->selectRaw("sum(case when student_type = 'Fullday' then 1 else 0 end) as fullday")
            ->selectRaw('sum(case when is_raport_held = 1 then 1 else 0 end) as ditahan')
            ->first();

        return Inertia::render('Keuangan/MasterData/DataSiswa', [
            'daftarKelas' => $kelas->map(fn ($k) => [
                'id'            => $k->id,
                'name'          => $k->name,
                'tingkat'       => $k->tingkat,
                'tahun_ajaran'  => $k->tahun_ajaran,
                'is_active'     => (bool) $k->is_active,
                'guru_name'     => $k->guru->name ?? null,
                'kapasitas'     => (int) $k->kapasitas,
                'total'         => (int) $k->santris_count,
                'reguler'       => (int) $k->reguler_count,
                'fullday'       => (int) $k->fullday_count,
                'ditahan'       => (int) $k->ditahan_count,
            ]),
            'tanpaKelas' => [
                'total'   => (int) ($tanpaKelas->total ?? 0),
                'reguler' => (int) ($tanpaKelas->reguler ?? 0),
                'fullday' => (int) ($tanpaKelas->fullday ?? 0),
                'ditahan' => (int) ($tanpaKelas->ditahan ?? 0),
            ],
            'listTahun' => Kelas::select('tahun_ajaran')->whereNotNull('tahun_ajaran')
                ->distinct()->orderBy('tahun_ajaran', 'desc')->pluck('tahun_ajaran'),
            'filters'   => $request->only(['search', 'tahunFilter']),
        ]);
    }

    /** Daftar santri di satu kelas (atau yang belum punya kelas). */
    public function detail(Request $request, string $kelas)
    {
        $tanpaKelas = $kelas === self::TANPA_KELAS;
        $kelasAktif = $tanpaKelas ? null : Kelas::with('guru:id,name')->findOrFail($kelas);

        $query = Siswa::with('kelas:id,name,tahun_ajaran')
            ->when($tanpaKelas,
                fn ($q) => $q->whereNull('kelas_id'),
                fn ($q) => $q->where('kelas_id', $kelasAktif->id));

        $query->when($request->search, function ($q, $search) {
            $q->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        });

        $query->when($request->typeFilter, fn ($q, $type) => $q->where('student_type', $type));
        $query->when($request->statusFilter, fn ($q, $s) => $q->where('status', $s));

        $sortField     = $request->input('sort_field', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');

        // `is_active` tidak ada di santris — statusnya enum: aktif/lulus/keluar.
        $allowedSorts = ['nis', 'name', 'angkatan', 'status', 'created_at'];
        if (in_array($sortField, $allowedSorts, true)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        return Inertia::render('Keuangan/MasterData/DetailSiswa', [
            'kelas' => $kelasAktif ? [
                'id'           => $kelasAktif->id,
                'name'         => $kelasAktif->name,
                'tingkat'      => $kelasAktif->tingkat,
                'tahun_ajaran' => $kelasAktif->tahun_ajaran,
                'kapasitas'    => (int) $kelasAktif->kapasitas,
                'guru_name'    => $kelasAktif->guru->name ?? null,
            ] : null,
            'kelasParam' => $tanpaKelas ? self::TANPA_KELAS : (string) $kelasAktif->id,
            'siswa'      => $query->paginate(15)->withQueryString(),
            'listKelas'  => Kelas::where('is_active', true)
                ->orderBy('tahun_ajaran', 'desc')->orderBy('name')
                ->get(['id', 'name', 'tingkat', 'tahun_ajaran']),
            'filters'    => $request->only(['search', 'typeFilter', 'statusFilter', 'sort_field', 'sort_direction']),
        ]);
    }

    public function store(Request $request)
    {
        // gender & status wajib: keduanya NOT NULL di santris dan dipakai
        // modul raport untuk pembatasan kelas ikhwan/akhwat.
        $validated = $request->validate($this->aturan(), $this->pesan());

        Siswa::create($validated);

        return redirect()->back()->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function update(Request $request, Siswa $siswa)
    {
        $validated = $request->validate($this->aturan($siswa->id), $this->pesan());

        // Target hafalan (target_juz, target_metode, target_jilid) sengaja
        // tidak disentuh dari sini — itu wilayah panel raport.
        $siswa->update($validated);

        return redirect()->back()->with('success', 'Data siswa berhasil diperbarui.');
    }

        public function destroy(Siswa $siswa)
    {
        // Tombol ini untuk membuang SALAH INPUT, bukan untuk meluluskan atau
        // mengeluarkan santri. Santri yang benar-benar pernah bersekolah di
        // sini selalu meninggalkan jejak, dan jejak itu tidak boleh kehilangan
        // pemiliknya — tagihan tanpa siswa merusak laporan tunggakan, setoran
        // tanpa santri merusak rapor.
        $jejak = [];

        if ($siswa->tagihan()->exists())    $jejak[] = 'tagihan';
        if ($siswa->pembayaran()->exists()) $jejak[] = 'pembayaran';
        if ($siswa->setorans()->exists())   $jejak[] = 'setoran';
        if ($siswa->presensis()->exists())  $jejak[] = 'presensi';

        if ($jejak) {
            return redirect()->back()->withErrors([
                'delete_error' => 'Gagal! Siswa ini sudah punya riwayat '
                    . implode(', ', $jejak) . '. Untuk siswa yang sudah tidak '
                    . 'bersekolah di sini, ubah statusnya menjadi "lulus" atau '
                    . '"keluar" lewat tombol Edit — datanya tetap tersimpan dan '
                    . 'laporan tahun sebelumnya tidak rusak.',
            ]);
        }

        DB::transaction(function () use ($siswa) {
            // Baris turunan milik santri ini sendiri, tidak dirujuk modul lain.
            $siswa->kelasHistories()->delete();
            $siswa->targetHafalans()->delete();
            $siswa->kenaikanUsulans()->delete();

            if ($siswa->user) {
                $siswa->user->delete();
            }

            $siswa->forceDelete();
        });

        return redirect()->back()->with('success', 'Data siswa berhasil dihapus.');
    }

    /* ================= Helper ================= */

    private function aturan($abaikanId = null): array
    {
        return [
            'nis'            => 'required|string|unique:santris,nis' . ($abaikanId ? ",{$abaikanId}" : ''),
            'name'           => 'required|string|max:255',
            'gender'         => 'required|in:Laki-laki,Perempuan',
            'student_type'   => 'required|in:Reguler,Fullday',
            'angkatan'       => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'parent_name'    => 'required|string|max:255',
            'parent_contact' => 'required|string|max:20',
            'kelas_id'       => 'nullable|exists:kelas,id',
            'status'         => 'required|in:aktif,lulus,keluar',
            'is_raport_held' => 'boolean',
        ];
    }

    private function pesan(): array
    {
        return [
            'nis.required'            => 'NIS wajib diisi.',
            'nis.unique'              => 'NIS ini sudah terdaftar di database.',
            'name.required'           => 'Nama siswa wajib diisi.',
            'gender.required'         => 'Jenis kelamin wajib dipilih.',
            'angkatan.required'       => 'Angkatan wajib diisi.',
            'parent_name.required'    => 'Nama orang tua/wali wajib diisi.',
            'parent_contact.required' => 'Nomor kontak wajib diisi.',
        ];
    }
}