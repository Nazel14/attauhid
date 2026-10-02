<?php

namespace App\Http\Controllers\Keuangan\Pengaturan;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Identitas penanda tangan kwitansi.
 *
 * Satu tanda tangan untuk seluruh pesantren, bukan satu per petugas:
 * yang mengesahkan kwitansi adalah lembaganya, dan pimpinan tidak perlu
 * ikut campur tiap kali bendahara mencatat pembayaran. Siapa yang
 * menginput tetap tercatat — tercetak kecil di kaki kwitansi.
 *
 * Route-nya berada di dalam grup 'role:super-admin' di routes/keuangan.php,
 * jadi tidak ada pengecekan izin tambahan di sini. Kalau suatu hari grup
 * itu dilonggarkan, controller ini ikut terbuka — periksa grupnya, bukan
 * file ini.
 */
class IdentitasController extends Controller
{
    public function index()
    {
        return Inertia::render('Keuangan/Pengaturan/Identitas', [
            'identitas' => [
                // Kop kwitansi. Default-nya config('app.name') supaya
                // halaman ini tidak tampil kosong di instalasi yang belum
                // pernah mengisinya.
                'kop_nama'   => Setting::get('kop_nama', config('app.name')),
                'kop_alamat' => Setting::get('kop_alamat', config('app.school_address') ?: ''),

                'nama'    => Setting::get('ttd_nama', ''),
                'jabatan' => Setting::get('ttd_jabatan', ''),
                'gambar'  => Setting::get('ttd_gambar'),
                'aktif'   => Setting::get('ttd_aktif') === '1',
                'diubah'  => Setting::get('ttd_diubah'),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'kop_nama'   => 'required|string|max:120',
            'kop_alamat' => 'nullable|string|max:200',
            'nama'    => 'required|string|max:100',
            'jabatan' => 'required|string|max:100',
            'aktif'   => 'required|boolean',
            // PNG saja, dan itu bukan kerewelan: JPG tidak punya lapisan
            // transparan, jadi kotak putihnya akan menutupi garis nama di
            // bawah tanda tangan. 300 KB cukup untuk hasil pindai yang
            // tajam pada ukuran cetak 34px.
            'gambar'  => 'nullable|image|mimes:png|max:300',
            'hapus'   => 'nullable|boolean',
        ], [
            'kop_nama.required' => 'Nama lembaga wajib diisi — inilah judul yang tercetak di kop kwitansi.',
            'nama.required'    => 'Nama penanda tangan wajib diisi.',
            'jabatan.required' => 'Jabatan wajib diisi — inilah yang tercetak di atas tanda tangan.',
            'gambar.mimes'     => 'Berkas harus PNG dengan latar transparan. JPG akan menutupi garis nama.',
            'gambar.max'       => 'Ukuran gambar maksimal 300 KB.',
        ]);

        $uid = Auth::id();

        Setting::set('kop_nama', $data['kop_nama'], $uid);
        Setting::set('kop_alamat', $data['kop_alamat'] ?? null, $uid);

        Setting::set('ttd_nama', $data['nama'], $uid);
        Setting::set('ttd_jabatan', $data['jabatan'], $uid);
        Setting::set('ttd_aktif', $data['aktif'] ? '1' : '0', $uid);

        if (! empty($data['hapus'])) {
            Setting::set('ttd_gambar', null, $uid);
        } elseif ($request->hasFile('gambar')) {
            // Disimpan sebagai data-URI, bukan sebagai berkas di folder.
            // DomPDF memang membutuhkan bentuk ini — logo kop sudah
            // diubah ke base64 saat dirender — jadi menyimpannya begini
            // sekaligus menghapus satu langkah konversi tiap kali cetak,
            // dan menghindari urusan symlink storage yang tidak bisa
            // dibuat di hosting tanpa artisan.
            $berkas = $request->file('gambar');

            Setting::set(
                'ttd_gambar',
                'data:image/png;base64,' . base64_encode(file_get_contents($berkas->getRealPath())),
                $uid
            );
        }

        Setting::set('ttd_diubah', now()->toDateTimeString(), $uid);

        return back()->with('success', 'Identitas tersimpan. Kwitansi berikutnya langsung memakainya.');
    }
}