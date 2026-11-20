<?php

namespace App\Http\Controllers\Keuangan\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\MasterData\Kelas;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class KelasController extends Controller
{
    public function index(Request $request)
    {
        $query = Kelas::query()->with('guru:id,name');

        // Kolomnya `name` dan `tahun_ajaran` — tabel classes lama
        // dengan `nama_kelas` sudah tidak dipakai.
        $query->when($request->search, function ($q, $search) {
            $q->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                  ->orWhere('tingkat', 'like', "%{$search}%")
                  ->orWhere('tahun_ajaran', 'like', "%{$search}%");
            });
        });

        // Satu baris kelas = satu rombel per tahun ajaran, jadi filter
        // tahun ajaran diperlukan agar daftarnya tidak menumpuk tiap tahun.
        $query->when($request->tahunFilter, fn ($q, $tahun) => $q->where('tahun_ajaran', $tahun));

        // TK dan Rumah Quran berbagi tabel kelas yang sama; filter ini yang
        // memisahkan keduanya di layar.
        $query->when($request->lembagaFilter, fn ($q, $lembaga) => $q->where('lembaga', $lembaga));

        $sortField     = $request->input('sort_field', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');

        $allowedSorts = ['name', 'tingkat', 'lembaga', 'kapasitas', 'is_active', 'tahun_ajaran'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('name', 'asc');
        }

        // Cukup santris: sejak students dilebur, siswas() menghitung
        // baris yang sama persis.
        $query->withCount('santris');

        $kelas = $query->paginate(10)->withQueryString();

        $listTahun = Kelas::select('tahun_ajaran')
            ->whereNotNull('tahun_ajaran')
            ->distinct()
            ->orderBy('tahun_ajaran', 'desc')
            ->pluck('tahun_ajaran');

        return Inertia::render('Keuangan/MasterData/DataKelas', [
            'kelas'       => $kelas,
            'listTahun'   => $listTahun,
            'listLembaga' => Kelas::LEMBAGA,
            'filters'     => $request->only(['search', 'tahunFilter', 'lembagaFilter', 'sort_field', 'sort_direction']),
        ]);
    }

    public function store(Request $request)
    {
        // Unik per kombinasi nama + tahun ajaran, bukan nama saja:
        // "Kelas 7A" boleh muncul lagi di tahun ajaran berikutnya.
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'tahun_ajaran' => 'required|string|max:20',
            'tingkat'      => 'nullable|string|max:100',
            'lembaga'      => 'required|in:' . implode(',', array_keys(Kelas::LEMBAGA)),
            'kapasitas'    => 'required|integer|min:1',
            'is_active'    => 'boolean',
        ], [
            'lembaga.required' => 'Lembaga wajib dipilih.',
            'lembaga.in'       => 'Lembaga tidak dikenali.',
        ]);

        $sudahAda = Kelas::where('name', $validated['name'])
            ->where('tahun_ajaran', $validated['tahun_ajaran'])
            ->exists();

        if ($sudahAda) {
            return redirect()->back()->withErrors([
                'name' => 'Kelas dengan nama ini sudah ada di tahun ajaran tersebut.',
            ]);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        Kelas::create($validated);

        return redirect()->back()->with('success', 'Data kelas berhasil ditambahkan.');
    }

    /**
     * Pakai $id, bukan route model binding — pluralisasi Laravel untuk
     * kata "Kelas" tidak dapat diandalkan.
     */
    public function update(Request $request, $id)
    {
        $kelas = Kelas::findOrFail($id);

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'tahun_ajaran' => 'required|string|max:20',
            'tingkat'      => 'nullable|string|max:100',
            'lembaga'      => 'required|in:' . implode(',', array_keys(Kelas::LEMBAGA)),
            'kapasitas'    => 'required|integer|min:1',
            'is_active'    => 'boolean',
        ], [
            'lembaga.required' => 'Lembaga wajib dipilih.',
            'lembaga.in'       => 'Lembaga tidak dikenali.',
        ]);

        $sudahAda = Kelas::where('name', $validated['name'])
            ->where('tahun_ajaran', $validated['tahun_ajaran'])
            ->where('id', '!=', $kelas->id)
            ->exists();

        if ($sudahAda) {
            return redirect()->back()->withErrors([
                'name' => 'Kelas dengan nama ini sudah ada di tahun ajaran tersebut.',
            ]);
        }

        // guru_id sengaja tidak divalidasi/diubah di sini — penugasan wali
        // kelas tetap milik panel raport.
        $kelas->update($validated);

        return redirect()->back()->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $kelas = Kelas::findOrFail($id);

        /*
         * Di database, menghapus kelas TIDAK berhenti di baris kelas saja:
         *
         *   santris.kelas_id               ON DELETE CASCADE  → santri ikut terhapus
         *   santri_kelas_history.kelas_id  ON DELETE CASCADE  → riwayat kelas ikut terhapus
         *   setorans.kelas_id              ON DELETE SET NULL → setoran kehilangan kelasnya
         *
         * Jadi pemeriksaannya wajib lengkap di sini. Termasuk santri yang
         * sudah DIARSIPKAN: santris() biasa tidak menghitung mereka (global
         * scope SoftDeletes), padahal CASCADE tetap menghapusnya permanen —
         * beserta rujukan tagihan dan kwitansinya.
         */
        $jumlahSantri = $kelas->santris()->withTrashed()->count();

        if ($jumlahSantri > 0) {
            return redirect()->back()->withErrors([
                'delete_error' => "Gagal menghapus! Kelas ini masih tercatat untuk {$jumlahSantri} santri/siswa (termasuk yang sudah diarsipkan). Pindahkan mereka ke kelas lain, atau nonaktifkan kelas ini saja.",
            ]);
        }

        $punyaRiwayat = DB::table('santri_kelas_history')->where('kelas_id', $kelas->id)->exists()
            || DB::table('setorans')->where('kelas_id', $kelas->id)->exists();

        if ($punyaRiwayat) {
            return redirect()->back()->withErrors([
                'delete_error' => 'Kelas ini sudah punya riwayat kelas atau setoran, jadi tidak bisa dihapus. Nonaktifkan saja: klik Edit, lalu hilangkan centang "Kelas Sedang Aktif".',
            ]);
        }

        // Sampai di sini kelasnya benar-benar kosong dan belum pernah
        // dipakai — biasanya kelas salah input. Wali kelas tidak jadi
        // alasan menolak: penugasannya tersimpan di baris ini sendiri.
        try {
            $kelas->delete();
        } catch (QueryException $e) {
            // Jaring pengaman bila kelak ada tabel lain yang menunjuk ke
            // kelas dengan aturan RESTRICT.
            if ($e->getCode() === '23000') {
                return redirect()->back()->withErrors([
                    'delete_error' => 'Kelas ini masih dipakai data lain, jadi tidak bisa dihapus. Nonaktifkan saja: klik Edit, lalu hilangkan centang "Kelas Sedang Aktif".',
                ]);
            }

            throw $e;
        }

        return redirect()->back()->with('success', 'Data kelas berhasil dihapus.');
    }
}