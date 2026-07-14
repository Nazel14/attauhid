<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TahunAjaran;
use App\Models\Santri;
use App\Models\SantriKelasHistory;
use App\Models\SantriTargetHafalan;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TahunAjaranController extends Controller
{
    use LogsActivity;

    /**
     * Daftar semua tahun ajaran (Inertia + React)
     */
    public function index()
    {
        $tahunAjarans = TahunAjaran::terbaru()
            ->withCount(['presensis', 'setorans'])
            ->get()
            ->map(fn ($ta) => [
                'id'                  => $ta->id,
                'nama'                => $ta->nama,
                'semester'            => (int) $ta->semester,
                'label_lengkap'       => $ta->label_lengkap,
                'is_aktif'            => (bool) $ta->is_aktif,
                'keterangan'          => $ta->keterangan,
                // Untuk tampilan
                'mulai_fmt'           => $ta->tanggal_mulai->format('d M Y'),
                'selesai_fmt'         => $ta->tanggal_selesai->format('d M Y'),
                // Untuk isi form edit (input type=date)
                'tanggal_mulai'       => $ta->tanggal_mulai->format('Y-m-d'),
                'tanggal_selesai'     => $ta->tanggal_selesai->format('Y-m-d'),
                'presensis_count'     => $ta->presensis_count,
                'setorans_count'      => $ta->setorans_count,
                // Sudah pernah dipakai & ditutup → terkunci permanen
                'sudah_dipakai'       => ! is_null($ta->ditutup_pada),
                'ditutup_fmt'         => $ta->ditutup_pada?->format('d M Y'),
            ])
            ->values();

        $aktif = TahunAjaran::getAktif();

        return Inertia::render('Admin/TahunAjaran/Index', [
            'tahunAjarans' => $tahunAjarans,
            'aktif' => $aktif ? [
                'id'            => $aktif->id,
                'label_lengkap' => $aktif->label_lengkap,
                'mulai_fmt'     => $aktif->tanggal_mulai->format('d M Y'),
                'selesai_fmt'   => $aktif->tanggal_selesai->format('d M Y'),
            ] : null,
        ]);
    }

    /**
     * Simpan tahun ajaran baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama'            => 'required|string|max:20',
            'semester'        => 'required|in:1,2',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
            'keterangan'      => 'nullable|string|max:255',
        ], [
            'nama.required'         => 'Nama tahun ajaran wajib diisi. Contoh: 2025/2026',
            'tanggal_selesai.after' => 'Tanggal selesai harus setelah tanggal mulai.',
        ]);

        // Cek duplikat nama + semester
        $exists = TahunAjaran::where('nama', $validated['nama'])
                              ->where('semester', $validated['semester'])
                              ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Tahun ajaran {$validated['nama']} Semester {$validated['semester']} sudah ada.");
        }

        $tahunAjaran = TahunAjaran::create([
            'nama'            => $validated['nama'],
            'semester'        => $validated['semester'],
            'tanggal_mulai'   => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'is_aktif'        => false,
            'ditutup_pada'    => null,
            'keterangan'      => $validated['keterangan'],
        ]);

        $this->logCreate('tahun-ajaran', $tahunAjaran->label_lengkap, $tahunAjaran);

        return redirect()->route('admin.tahun-ajaran.index')
            ->with('success', "Tahun ajaran {$tahunAjaran->label_lengkap} berhasil ditambahkan.");
    }

    /**
     * Update tahun ajaran.
     * Tahun ajaran yang sudah ditutup hanya boleh diubah keterangannya —
     * mengubah periode/nama akan merusak data historis yang sudah terikat.
     */
    public function update(Request $request, TahunAjaran $tahunAjaran)
    {
        $oldValues = $tahunAjaran->only(['nama', 'semester', 'tanggal_mulai', 'tanggal_selesai', 'keterangan']);

        if ($tahunAjaran->ditutup_pada) {
            $validated = $request->validate([
                'keterangan' => 'nullable|string|max:255',
            ]);

            $tahunAjaran->update(['keterangan' => $validated['keterangan'] ?? null]);
            $this->logUpdate('tahun-ajaran', $tahunAjaran->label_lengkap, $tahunAjaran, $oldValues);

            return redirect()->route('admin.tahun-ajaran.index')
                ->with('success', "Keterangan {$tahunAjaran->label_lengkap} diperbarui. Periode tidak dapat diubah karena semester sudah ditutup.");
        }

        $validated = $request->validate([
            'nama'            => 'required|string|max:20',
            'semester'        => 'required|in:1,2',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
            'keterangan'      => 'nullable|string|max:255',
        ]);

        // Cek duplikat (kecuali diri sendiri)
        $exists = TahunAjaran::where('nama', $validated['nama'])
                              ->where('semester', $validated['semester'])
                              ->where('id', '!=', $tahunAjaran->id)
                              ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Tahun ajaran {$validated['nama']} Semester {$validated['semester']} sudah ada.");
        }

        $tahunAjaran->update($validated);
        $this->logUpdate('tahun-ajaran', $tahunAjaran->label_lengkap, $tahunAjaran, $oldValues);

        return redirect()->route('admin.tahun-ajaran.index')
            ->with('success', "Tahun ajaran {$tahunAjaran->label_lengkap} berhasil diperbarui.");
    }

    /**
     * Jadikan tahun ajaran ini AKTIF (hanya 1 yang boleh aktif).
     * Tahun ajaran yang sudah pernah ditutup TIDAK BISA dipakai lagi.
     */
    public function setAktif(TahunAjaran $tahunAjaran)
    {
        if ($tahunAjaran->is_aktif) {
            return redirect()->route('admin.tahun-ajaran.index')
                ->with('success', 'Tahun ajaran ini sudah aktif.');
        }

        if ($tahunAjaran->ditutup_pada) {
            return redirect()->route('admin.tahun-ajaran.index')
                ->with('error', "Tahun ajaran {$tahunAjaran->label_lengkap} sudah pernah dipakai dan ditutup pada {$tahunAjaran->ditutup_pada->format('d M Y')}. Buat periode baru untuk melanjutkan.");
        }

        TahunAjaran::setAktif($tahunAjaran->id);

        $this->logActivity(
            'update',
            'tahun-ajaran',
            "Mengaktifkan tahun ajaran: {$tahunAjaran->label_lengkap}",
            $tahunAjaran,
            ['is_aktif' => false],
            ['is_aktif' => true]
        );

        return redirect()->route('admin.tahun-ajaran.index')
            ->with('success', "Tahun ajaran {$tahunAjaran->label_lengkap} sekarang aktif.");
    }

    /**
     * TUTUP SEMESTER / NAIK KELAS.
     * Setelah ditutup, tahun ajaran lama dikunci permanen (ditutup_pada terisi).
     */
    public function tutupSemester(Request $request, TahunAjaran $tahunAjaran)
    {
        if (!$tahunAjaran->is_aktif) {
            return redirect()->route('admin.tahun-ajaran.index')
                ->with('error', 'Hanya tahun ajaran aktif yang bisa ditutup.');
        }

        $request->validate([
            'tahun_ajaran_baru_id' => 'required|exists:tahun_ajaran,id|different:' . $tahunAjaran->id,
        ], [
            'tahun_ajaran_baru_id.required'  => 'Pilih tahun ajaran yang akan diaktifkan berikutnya.',
            'tahun_ajaran_baru_id.different' => 'Tahun ajaran baru harus berbeda dengan yang sedang aktif.',
        ]);

        $tahunAjaranBaru = TahunAjaran::findOrFail($request->tahun_ajaran_baru_id);

        // Penjagaan utama: tahun ajaran bekas pakai tidak boleh dipilih lagi.
        if ($tahunAjaranBaru->ditutup_pada) {
            return redirect()->back()
                ->with('error', "Tahun ajaran {$tahunAjaranBaru->label_lengkap} sudah pernah dipakai dan ditutup. Pilih atau buat periode yang belum pernah dipakai.");
        }

        DB::transaction(function () use ($tahunAjaran, $tahunAjaranBaru) {

            // 1. Tutup semua history kelas yang masih aktif
            SantriKelasHistory::where('tahun_ajaran_id', $tahunAjaran->id)
                ->whereNull('tanggal_keluar')
                ->update(['tanggal_keluar' => $tahunAjaran->tanggal_selesai]);

            // 2. Buat history kelas baru untuk tahun ajaran berikutnya
            $santris = Santri::all();
            foreach ($santris as $santri) {
                SantriKelasHistory::create([
                    'santri_id'       => $santri->id,
                    'kelas_id'        => $santri->kelas_id,
                    'tahun_ajaran_id' => $tahunAjaranBaru->id,
                    'tanggal_masuk'   => $tahunAjaranBaru->tanggal_mulai,
                    'keterangan'      => 'Otomatis dari tutup semester ' . $tahunAjaran->label_lengkap,
                ]);

                // 3. Salin target hafalan ke tahun ajaran baru
                $targetLama = SantriTargetHafalan::where('santri_id', $santri->id)
                    ->where('tahun_ajaran_id', $tahunAjaran->id)
                    ->first();

                SantriTargetHafalan::firstOrCreate(
                    [
                        'santri_id'       => $santri->id,
                        'tahun_ajaran_id' => $tahunAjaranBaru->id,
                    ],
                    [
                        'target_juz'  => $targetLama?->target_juz ?? $santri->target_juz ?? 1,
                        'keterangan'  => 'Disalin dari ' . $tahunAjaran->label_lengkap,
                    ]
                );
            }

            // 4. Kunci tahun ajaran lama — tidak bisa dipakai lagi selamanya
            $tahunAjaran->update([
                'is_aktif'     => false,
                'ditutup_pada' => now(),
            ]);

            // 5. Aktifkan tahun ajaran baru
            TahunAjaran::setAktif($tahunAjaranBaru->id);
        });

        $this->logActivity(
            'update',
            'tahun-ajaran',
            "Menutup semester {$tahunAjaran->label_lengkap} → aktifkan {$tahunAjaranBaru->label_lengkap}",
            $tahunAjaran,
            ['ditutup_pada' => null],
            ['ditutup_pada' => now()->toDateTimeString()]
        );

        return redirect()->route('admin.tahun-ajaran.index')
            ->with('success', "Semester {$tahunAjaran->label_lengkap} ditutup permanen. Sekarang aktif: {$tahunAjaranBaru->label_lengkap}");
    }

    /**
     * Hapus tahun ajaran (hanya yang tidak aktif, belum ditutup, dan tidak punya data)
     */
    public function destroy(TahunAjaran $tahunAjaran)
    {
        if ($tahunAjaran->is_aktif) {
            return redirect()->route('admin.tahun-ajaran.index')
                ->with('error', 'Tahun ajaran yang sedang aktif tidak dapat dihapus.');
        }

        if ($tahunAjaran->ditutup_pada) {
            return redirect()->route('admin.tahun-ajaran.index')
                ->with('error', 'Tahun ajaran yang sudah ditutup adalah arsip dan tidak dapat dihapus.');
        }

        $adaPresensi = $tahunAjaran->presensis()->exists();
        $adaSetoran  = $tahunAjaran->setorans()->exists();

        if ($adaPresensi || $adaSetoran) {
            return redirect()->route('admin.tahun-ajaran.index')
                ->with('error', 'Tahun ajaran ini memiliki data presensi/setoran dan tidak dapat dihapus. Arsipkan saja jika tidak ingin ditampilkan.');
        }

        $label = $tahunAjaran->label_lengkap;
        $oldValues = $tahunAjaran->toArray();
        $tahunAjaran->delete();

        $this->logDelete('tahun-ajaran', $label, $oldValues);

        return redirect()->route('admin.tahun-ajaran.index')
            ->with('success', "Tahun ajaran {$label} berhasil dihapus.");
    }
}