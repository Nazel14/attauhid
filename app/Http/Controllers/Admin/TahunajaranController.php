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

class TahunAjaranController extends Controller
{
    use LogsActivity;

    /**
     * Daftar semua tahun ajaran
     */
    public function index()
    {
        $tahunAjarans = TahunAjaran::terbaru()->get();
        $aktif        = TahunAjaran::getAktif();

        return view('admin.tahun-ajaran.index', compact('tahunAjarans', 'aktif'));
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
            'nama.required'            => 'Nama tahun ajaran wajib diisi. Contoh: 2025/2026',
            'tanggal_selesai.after'    => 'Tanggal selesai harus setelah tanggal mulai.',
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
            'is_aktif'        => false, // Baru dibuat = belum aktif
            'keterangan'      => $validated['keterangan'],
        ]);

        $this->logCreate('tahun-ajaran', $tahunAjaran->label_lengkap, $tahunAjaran);

        return redirect()->route('admin.tahun-ajaran.index')
            ->with('success', "Tahun ajaran {$tahunAjaran->label_lengkap} berhasil ditambahkan.");
    }

    /**
     * Update tahun ajaran
     */
    public function update(Request $request, TahunAjaran $tahunAjaran)
    {
        $oldValues = $tahunAjaran->only(['nama', 'semester', 'tanggal_mulai', 'tanggal_selesai', 'keterangan']);

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
            ->with('updated', "Tahun ajaran {$tahunAjaran->label_lengkap} berhasil diperbarui.");
    }

    /**
     * Jadikan tahun ajaran ini AKTIF
     * Ini adalah aksi paling penting: hanya 1 yang boleh aktif
     */
    public function setAktif(TahunAjaran $tahunAjaran)
    {
        if ($tahunAjaran->is_aktif) {
            return redirect()->route('admin.tahun-ajaran.index')
                ->with('info', "Tahun ajaran ini sudah aktif.");
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
     * TUTUP SEMESTER / NAIK KELAS
     * Proses akhir semester:
     * 1. Catat tanggal keluar semua santri di history kelas
     * 2. Siapkan kolom untuk tahun ajaran berikutnya
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

        DB::transaction(function () use ($tahunAjaran, $tahunAjaranBaru) {

            // 1. Tutup semua history kelas yang masih aktif (tanggal_keluar = NULL)
            SantriKelasHistory::where('tahun_ajaran_id', $tahunAjaran->id)
                ->whereNull('tanggal_keluar')
                ->update(['tanggal_keluar' => $tahunAjaran->tanggal_selesai]);

            // 2. Buat history kelas baru untuk tahun ajaran berikutnya
            //    berdasarkan posisi kelas santri saat ini (santri.kelas_id)
            $santris = Santri::all();
            foreach ($santris as $santri) {
                SantriKelasHistory::create([
                    'santri_id'       => $santri->id,
                    'kelas_id'        => $santri->kelas_id,
                    'tahun_ajaran_id' => $tahunAjaranBaru->id,
                    'tanggal_masuk'   => $tahunAjaranBaru->tanggal_mulai,
                    'keterangan'      => 'Otomatis dari tutup semester ' . $tahunAjaran->label_lengkap,
                ]);

                // 3. Salin target hafalan ke tahun ajaran baru (default sama dengan tahun ini)
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

            // 4. Aktifkan tahun ajaran baru, nonaktifkan yang lama
            TahunAjaran::setAktif($tahunAjaranBaru->id);
        });

        $this->logActivity(
            'update',
            'tahun-ajaran',
            "Menutup semester {$tahunAjaran->label_lengkap} → aktifkan {$tahunAjaranBaru->label_lengkap}",
            $tahunAjaran,
            null,
            null
        );

        return redirect()->route('admin.tahun-ajaran.index')
            ->with('success', "Semester {$tahunAjaran->label_lengkap} berhasil ditutup. Sekarang aktif: {$tahunAjaranBaru->label_lengkap}");
    }

    /**
     * Hapus tahun ajaran (hanya yang tidak aktif dan tidak punya data)
     */
    public function destroy(TahunAjaran $tahunAjaran)
    {
        if ($tahunAjaran->is_aktif) {
            return redirect()->route('admin.tahun-ajaran.index')
                ->with('error', 'Tahun ajaran yang sedang aktif tidak dapat dihapus.');
        }

        // Cek apakah ada data presensi/setoran yang terikat
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
            ->with('deleted', "Tahun ajaran {$label} berhasil dihapus.");
    }
}