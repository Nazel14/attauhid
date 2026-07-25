<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use App\Models\LaporanPesan;
use App\Models\Presensi;
use App\Models\Setoran;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LaporanController extends Controller
{
    /* ================= INDEX ================= */

    public function index(Request $request)
    {
        $user   = $request->user();
        $status = $request->query('status', 'semua');
        $cari   = trim((string) $request->query('q', ''));

        $query = Laporan::with(['pelapor:id,name,role', 'penerima:id,name,role', 'reportable'])
            ->withCount(['pesan as unread_count' => function ($q) use ($user) {
                $q->whereNull('read_at')
                  ->whereNull('dihapus_at')
                  ->where('user_id', '!=', $user->id);
            }])
            ->latest('updated_at');

        // Scope per role
        if ($user->role === 'santri') {
            $query->where(function ($q) use ($user) {
                $q->where('pelapor_id', $user->id)     // laporan dia ke guru
                  ->orWhere('penerima_id', $user->id); // pesan dari guru
            });
        } elseif ($user->role === 'guru') {
            $query->where(function ($q) use ($user) {
                $q->where('penerima_id', $user->id)   // laporan dari santri
                  ->orWhere('pelapor_id', $user->id); // laporan dia ke admin
            });
        }
        // admin: lihat semua (monitoring) — tanpa filter tambahan

        // Filter status laporan. Thread yang dibisukan disembunyikan dari
        // tampilan biasa supaya tidak lagi mengganggu — hanya muncul kalau
        // filter "Dibisukan" dipilih secara sadar.
        if (in_array($status, ['terbuka', 'dibalas', 'selesai'])) {
            $query->where('status', $status)->whereNull('dibisukan_at');
        } elseif ($status === 'dibisukan') {
            $query->whereNotNull('dibisukan_at');
        } else {
            $query->whereNull('dibisukan_at');
        }

        // Pencarian: nama LAWAN BICARA atau isi pesan.
        //
        // Nama sendiri sengaja dikecualikan (`id != $user->id`). Tanpa itu,
        // seorang guru bernama "yudi ahmad" yang mencari "ahmad" akan cocok
        // di SEMUA thread — karena dia memang pelapor atau penerima di
        // semuanya. Hasilnya pencarian terlihat tidak bekerja sama sekali.
        //
        // Untuk admin yang memantau thread orang lain, dia bukan pihak yang
        // terlibat, jadi kedua nama tetap ikut tercari.
        if ($cari !== '') {
            $like = '%' . $cari . '%';

            $query->where(function ($q) use ($like, $user) {
                $q->whereHas('pelapor', fn ($u) => $u
                        ->where('id', '!=', $user->id)
                        ->where('name', 'like', $like))
                  ->orWhereHas('penerima', fn ($u) => $u
                        ->where('id', '!=', $user->id)
                        ->where('name', 'like', $like))
                  ->orWhereHas('pesan', fn ($p) => $p
                        ->whereNull('dihapus_at')
                        ->where('isi', 'like', $like));
            });
        }

        $laporan = $query->paginate(10)->withQueryString();

        // Tandai per-item siapa yang boleh menghapus: pemilik thread (pelapor)
        // atau admin. Penerima tidak boleh — kalau boleh, pihak yang sedang
        // dikomplain atau ditegur bisa melenyapkan threadnya sepihak.
        $isAdmin = in_array($user->role, ['admin', 'super-admin']);

        $laporan->getCollection()->transform(function ($item) use ($user, $isAdmin) {
            $item->bisa_hapus = $isAdmin || $item->pelapor_id === $user->id;
            return $item;
        });

        return Inertia::render('Laporan/Index', [
            'laporan'  => $laporan,
            'filter'   => ['status' => $status, 'q' => $cari],
            'kategori' => Laporan::KATEGORI,
            'guruList' => in_array($user->role, ['admin', 'super-admin'])
                ? User::where('role', 'guru')->orderBy('name')->get(['id', 'name'])
                : [],
            'santriList' => $user->role === 'guru' && $user->guru
                ? User::where('role', 'santri')
                    ->whereHas('santri', fn ($q) => $q->where('guru_id', $user->guru->id))
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : [],
        ]);
    }

    /* ================= SHOW (thread) ================= */

    public function show(Request $request, Laporan $laporan)
    {
        $user = $request->user();

        abort_unless(
            $laporan->melibatkan($user) || in_array($user->role, ['admin', 'super-admin']),
            403
        );

        // Tandai pesan lawan bicara sebagai dibaca (admin monitor tidak menandai)
        if ($laporan->melibatkan($user)) {
            $laporan->pesan()
                ->whereNull('read_at')
                ->whereNull('dihapus_at')
                ->where('user_id', '!=', $user->id)
                ->update(['read_at' => now()]);
        }

        $laporan->load([
            'pelapor:id,name,role',
            'penerima:id,name,role',
            'reportable',
            'pesan' => fn ($q) => $q->with('pengirim:id,name')->orderBy('created_at'),
        ]);

        // Yang boleh membisukan: pihak yang terlibat, selain santri.
        // Santri sengaja tidak diberi tombol ini agar tidak bisa membungkam
        // teguran atau pengingat dari gurunya sendiri.
        $bisaBisukan = $laporan->melibatkan($user) && $user->role !== 'santri';

        return Inertia::render('Laporan/Show', [
            'laporan'        => $laporan,
            'bisaBalas'      => $laporan->melibatkan($user) && ! $laporan->terkunci(),
            'bisaTutup'      => $user->id === $laporan->penerima_id && ! $laporan->terkunci(),
            'bisaBisukan'    => $bisaBisukan,
            'batasEditMenit' => LaporanPesan::BATAS_EDIT_MENIT,
        ]);
    }

    /* ================= STORE (buat laporan baru) ================= */

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'reportable_type' => 'nullable|in:setoran,presensi',
            'reportable_id'   => 'nullable|integer|required_with:reportable_type',
            'penerima_id'     => 'nullable|integer|exists:users,id',
            'kategori'        => 'required|in:' . implode(',', array_keys(Laporan::KATEGORI)),
            'isi'             => 'required|string|min:5|max:2000',
        ]);

        $reportable = null;
        $penerima   = null;
        $snapshot   = null;

        if ($user->role === 'santri') {
            // Penerima selalu guru pembimbing. Objek opsional:
            // - ada objek  → komplain setoran/presensi
            // - tanpa objek → laporan data diri (nama, gender, dll)
            $penerima = User::findOrFail($user->santri->guru->user_id);

            if (!empty($data['reportable_type'])) {
            $map = ['setoran' => Setoran::class, 'presensi' => Presensi::class];

            abort_unless(isset($map[$data['reportable_type']]), 422);

            $reportable = $map[$data['reportable_type']]::findOrFail($data['reportable_id']);

            // Pastikan objek milik santri ini — sesuaikan nama kolom bila beda
            abort_unless($reportable->santri_id === $user->santri->id, 403);

            $penerima = User::findOrFail($user->santri->guru->user_id);

            // Cegah laporan dobel pada objek yang sama
            $sudahAda = Laporan::where('reportable_type', get_class($reportable))
                ->where('reportable_id', $reportable->id)
                ->where('pelapor_id', $user->id)
                ->where('status', '!=', 'selesai')
                ->exists();

            if ($sudahAda) {
                return back()->with('error', 'Masih ada laporan aktif untuk data ini.');
            }

            $snapshot = $reportable->only(
                array_intersect(
                    ['tanggal', 'surat_start', 'surat_end', 'ayat_start', 'ayat_end', 'nilai', 'kategori_setoran', 'sesi', 'status', 'keterangan'],
                    array_keys($reportable->getAttributes())
                )
            );
            }
        } elseif ($user->role === 'guru') {
            if ($data['penerima_id'] ?? null) {
                // Guru → santri (melalui "Hubungi Santri")
                $penerima = User::where('role', 'santri')
                    ->whereHas('santri', fn ($q) => $q->where('guru_id', $user->guru->id))
                    ->findOrFail($data['penerima_id']);
            } else {
                // Guru → admin (melalui "Hubungi Admin")
                $penerima = User::whereIn('role', ['admin', 'super-admin'])->orderBy('id')->firstOrFail();
            }
        } elseif (in_array($user->role, ['admin', 'super-admin'])) {
            // Admin → guru terpilih (laporan umum, tanpa objek)
            abort_unless($data['penerima_id'] ?? null, 422);
            $penerima = User::where('role', 'guru')->findOrFail($data['penerima_id']);
        } else {
            abort(403);
        }

        DB::transaction(function () use ($user, $penerima, $reportable, $data, $snapshot) {
            $laporan = Laporan::create([
                'reportable_type' => $reportable ? get_class($reportable) : null,
                'reportable_id'   => $reportable?->id,
                'pelapor_id'      => $user->id,
                'penerima_id'     => $penerima->id,
                'kategori'        => $data['kategori'],
                'status'          => 'terbuka',
                'snapshot'        => $snapshot,
            ]);

            $laporan->pesan()->create([
                'user_id' => $user->id,
                'isi'     => $data['isi'],
            ]);
        });

        return back()->with('success', 'Laporan berhasil dikirim.');
    }

    /* ================= REPLY ================= */

    public function reply(Request $request, Laporan $laporan)
    {
        $user = $request->user();

        abort_unless($laporan->melibatkan($user), 403);

        if ($laporan->isDibisukan()) {
            return back()->with('error', 'Percakapan ini sedang dibisukan.');
        }

        if ($laporan->isSelesai()) {
            return back()->with('error', 'Laporan sudah ditutup.');
        }

        $data = $request->validate([
            'isi' => 'required|string|min:1|max:2000',
        ]);

        $laporan->pesan()->create([
            'user_id' => $user->id,
            'isi'     => $data['isi'],
        ]);

        // Penerima membalas pertama kali → status jadi 'dibalas'
        if ($user->id === $laporan->penerima_id && $laporan->status === 'terbuka') {
            $laporan->update(['status' => 'dibalas']);
        } else {
            $laporan->touch();
        }

        return back();
    }

    /* ================= SELESAI (tutup laporan) ================= */

    public function selesai(Request $request, Laporan $laporan)
    {
        $user = $request->user();

        abort_unless($user->id === $laporan->penerima_id, 403);

        $laporan->update([
            'status'     => 'selesai',
            'selesai_at' => now(),
        ]);

        return back()->with('success', 'Laporan ditandai selesai.');
    }

   /* ================= BISUKAN / BUKA BISUKAN PERCAKAPAN ================= */

    public function toggleBisukan(Request $request, Laporan $laporan)
    {
        $user = $request->user();

        abort_unless($laporan->melibatkan($user), 403);
        abort_if($user->role === 'santri', 403);

        if ($laporan->isDibisukan()) {
            // Hanya yang membisukan atau admin yang boleh membuka kembali,
            // supaya tidak saling buka-tutup di tengah percakapan.
            abort_unless(
                $user->id === $laporan->dibisukan_oleh
                    || in_array($user->role, ['admin', 'super-admin']),
                403
            );

            $laporan->update(['dibisukan_at' => null, 'dibisukan_oleh' => null]);

            return back()->with('success', 'Percakapan dibuka kembali.');
        }

        $laporan->update([
            'dibisukan_at'   => now(),
            'dibisukan_oleh' => $user->id,
        ]);

        return back()->with('success', 'Percakapan dibisukan. Tidak ada pesan baru & tidak muncul di notifikasi.');
    }

    /* ================= HAPUS THREAD ================= */

    public function destroy(Request $request, Laporan $laporan)
    {
        $user = $request->user();

        // Pemilik thread atau admin saja. Prinsipnya: kamu bisa menarik
        // kembali yang kamu kirim, tapi tidak bisa menghapus yang orang
        // lain kirim kepadamu.
        abort_unless(
            $laporan->pelapor_id === $user->id
                || in_array($user->role, ['admin', 'super-admin']),
            403
        );

        DB::transaction(function () use ($laporan) {
            $laporan->pesan()->delete();
            $laporan->delete();
        });

        return redirect()
            ->route($this->rolePrefix($user) . '.laporan.index')
            ->with('success', 'Laporan dihapus.');
    }

    /* ================= EDIT PESAN ================= */

    public function updatePesan(Request $request, Laporan $laporan, LaporanPesan $pesan)
    {
        $user = $request->user();

        abort_unless($pesan->laporan_id === $laporan->id, 404);
        abort_unless($pesan->user_id === $user->id, 403);
        abort_if($pesan->isDihapus(), 422, 'Pesan sudah dihapus.');

        if ($laporan->terkunci()) {
            return back()->with('error', 'Percakapan sudah dikunci.');
        }

        if (! $pesan->masihBisaDiedit()) {
            return back()->with('error', 'Pesan hanya bisa diedit dalam ' . LaporanPesan::BATAS_EDIT_MENIT . ' menit pertama.');
        }

        $data = $request->validate([
            'isi' => 'required|string|min:1|max:2000',
        ]);

        $pesan->update([
            'isi'       => $data['isi'],
            'edited_at' => now(),
        ]);

        return back()->with('success', 'Pesan diperbarui.');
    }

    /* ================= HAPUS PESAN ================= */

    public function destroyPesan(Request $request, Laporan $laporan, LaporanPesan $pesan)
    {
        $user = $request->user();

        abort_unless($pesan->laporan_id === $laporan->id, 404);
        abort_unless($pesan->user_id === $user->id, 403);

        if ($pesan->isDihapus()) {
            return back();
        }

        // Baris tetap disimpan sebagai nisan, tapi isinya dikosongkan supaya
        // teks yang salah kirim benar-benar hilang, bukan sekadar disembunyikan.
        $pesan->update([
            'isi'        => '',
            'dihapus_at' => now(),
            'read_at'    => $pesan->read_at ?? now(),
        ]);

        return back()->with('success', 'Pesan dihapus.');
    }

    /* ================= HELPER ================= */

    private function rolePrefix(User $user): string
    {
        return match ($user->role) {
            'guru'   => 'guru',
            'santri' => 'santri',
            default  => 'admin',
        };
    }
}