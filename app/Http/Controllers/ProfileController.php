<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Santri;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /** Jeda minimal antar-perubahan foto profil guru (hari). */
    private const PHOTO_COOLDOWN_DAYS = 3;

   public function edit(Request $request): Response
    {
        $user = $request->user();

        /* ---------- Tentukan panel aktif ----------
         * Isi & layout halaman profil mengikuti panel yang sedang dibuka,
         * bukan kolom users.role. Super Admin yang sedang berada di panel
         * keuangan harus melihat profil versi keuangan, bukan versi admin. */
        $modules = method_exists($user, 'availableModules')
            ? $user->availableModules()
            : ['raport'];

        $punyaKeuangan = in_array('keuangan', $modules, true);

        $panel = (session('modul_aktif') === 'keuangan' && $punyaKeuangan)
            ? 'keuangan'
            : 'raport';

        // Akun keuangan murni (users.role bukan role raport) tidak punya
        // panel raport sama sekali, jadi selalu diperlakukan sebagai keuangan.
        if ($punyaKeuangan && !in_array($user->role, ['admin', 'guru', 'santri'], true)) {
            $panel = 'keuangan';
        }

        // Aturan foto:
        // - santri : read-only penuh
        // - guru   : cooldown 3 hari (berlaku juga saat ia di panel keuangan)
        // - lainnya: bebas, tanpa cooldown
        $canUpdatePhoto = true;
        $nextUpdateDate = null;

        if ($user->role === 'santri') {
            $canUpdatePhoto = false;
        } elseif ($user->role === 'guru' && $user->profile_photo_updated_at) {
            $next = Carbon::parse($user->profile_photo_updated_at)->addDays(self::PHOTO_COOLDOWN_DAYS);

            if ($next->isFuture()) {
                $canUpdatePhoto = false;
                $nextUpdateDate = $next->locale('id')->isoFormat('D MMMM YYYY, HH:mm');
            }
        }

        // Nama role Spatie, dipakai sebagai label di panel keuangan karena
        // di sana users.role tidak menggambarkan hak akses yang sebenarnya.
        $labelRoleSpatie = $user->getRoleNames()
            ->map(fn ($r) => Str::title(str_replace('-', ' ', $r)))
            ->implode(', ');

        $isSuperAdmin = ($user->role === 'admin' && $user->can('kelola-admin'))
            || $user->hasRole('super-admin');

        if ($panel === 'keuangan') {
            $roleLabel = $isSuperAdmin
                ? 'Super Admin'
                : ($labelRoleSpatie ?: 'Pengguna Keuangan');
        } else {
            $roleLabel = match (true) {
                $isSuperAdmin           => 'Super Admin',
                $user->role === 'admin' => 'Admin',
                $user->role === 'guru'  => 'Guru',
                default                 => 'Santri',
            };
        }

        // Daftar field profil per panel/role. `kategori` = kategori laporan yang
        // otomatis terpilih saat bendera di baris itu diklik; null artinya
        // baris hanya informasi dan tidak punya tombol lapor.
        $fields    = [];
        $bisaLapor = false;
        $kategori  = [];
        $tujuan    = '';
        $fotoLapor = null;

        if ($panel === 'keuangan') {
            // Pegawai adalah subclass Guru dengan tabel yang sama, jadi relasi
            // user->guru tetap mengembalikan baris yang benar bila relasi
            // khusus 'pegawai' belum didefinisikan di model User.
            $pegawai = method_exists($user, 'pegawai') ? $user->pegawai : $user->guru;

            $namaJabatan = null;
            if ($pegawai && method_exists($pegawai, 'jabatan')) {
                $j = $pegawai->jabatan;
                $namaJabatan = $j?->nama_jabatan ?? $j?->nama ?? $j?->name;
            }

            // Modul keuangan belum punya alur laporan/komplain sendiri,
            // jadi bendera dimatikan agar tidak memanggil route yang tidak ada.
            $bisaLapor = false;
            $tujuan    = 'Super Admin';

            $fields = [
                ['label' => 'Nama Lengkap',   'value' => $pegawai?->name ?? $user->name,  'kategori' => null],
                ['label' => 'NIP',            'value' => $pegawai?->nip,                  'kategori' => null],
                ['label' => 'Jabatan',        'value' => $namaJabatan,                    'kategori' => null],
                ['label' => 'Username Login', 'value' => $user->name,                     'kategori' => null],
                ['label' => 'Hak Akses',      'value' => $labelRoleSpatie ?: null,        'kategori' => null],
            ];

            // Akun keuangan tanpa data pegawai tidak perlu deretan tanda strip.
            $fields = array_values(array_filter(
                $fields,
                fn ($f) => $f['value'] !== null && $f['value'] !== ''
            ));
        } elseif ($user->role === 'santri') {
            $santri = $user->santri;
            $santri?->loadMissing(['kelas', 'guru']);

            // Tanpa guru pembimbing, laporan santri tidak punya penerima.
            $bisaLapor = (bool) $santri?->guru?->user_id;
            $tujuan    = 'Guru Pembimbing';

            $kategori = [
                'nama_salah'   => 'Nama Salah',
                'nis_salah'    => 'NIS Salah',
                'gender_salah' => 'Jenis Kelamin Salah',
                'kelas_salah'  => 'Kelas Salah',
                'foto_salah'   => 'Foto Profil Salah',
                'lainnya'      => 'Lainnya',
            ];

            // Username login sengaja tidak ditampilkan: isinya sama dengan
            // nama, jadi hanya menambah baris tanpa menambah informasi.
            $fields = [
                ['label' => 'Nama Lengkap',    'value' => $santri?->name,             'kategori' => 'nama_salah'],
                ['label' => 'NIS',             'value' => $santri?->nis,              'kategori' => 'nis_salah'],
                ['label' => 'Jenis Kelamin',   'value' => $this->labelGender($santri?->gender), 'kategori' => 'gender_salah'],
                ['label' => 'Kelas',           'value' => $santri?->kelas?->name,     'kategori' => 'kelas_salah'],
                ['label' => 'Guru Pembimbing', 'value' => $santri?->guru?->name,      'kategori' => 'lainnya'],
                ['label' => 'Target Jilid',    'value' => $santri?->target_jilid,     'kategori' => 'lainnya'],
            ];

            $fotoLapor = ['label' => 'Foto Profil', 'value' => null, 'kategori' => 'foto_salah'];
        } elseif ($user->role === 'guru') {
            $guru = $user->guru;

            $daftarKelas  = $guru
                ? Kelas::where('guru_id', $guru->id)->orderBy('name')->pluck('name')->implode(', ')
                : null;
            $jumlahSantri = $guru ? Santri::where('guru_id', $guru->id)->count() : 0;

            $bisaLapor = true;
            $tujuan    = 'Admin';

            $kategori = [
                'nama_salah'     => 'Nama Salah',
                'nip_salah'      => 'NIP Salah',
                'username_salah' => 'Username Salah',
                'kelas_salah'    => 'Kelas yang Diampu Salah',
                'foto_salah'     => 'Foto Profil Salah',
                'lainnya'        => 'Lainnya',
            ];

            $fotoLapor = ['label' => 'Foto Profil', 'value' => null, 'kategori' => 'foto_salah'];

            $fields = [
                ['label' => 'Nama Lengkap',   'value' => $guru?->name,   'kategori' => 'nama_salah'],
                ['label' => 'NIP',            'value' => $guru?->nip,    'kategori' => 'nip_salah'],
                ['label' => 'Username Login', 'value' => $user->name,    'kategori' => 'username_salah'],
                ['label' => 'Kelas Diampu',   'value' => $daftarKelas,   'kategori' => 'kelas_salah'],
                ['label' => 'Jumlah Santri',  'value' => $jumlahSantri . ' santri', 'kategori' => null],
            ];
        } else {
            // Admin mengelola datanya sendiri, jadi tidak perlu melapor.
            $fields = [
                ['label' => 'Username Login', 'value' => $user->name, 'kategori' => null],
                ['label' => 'Role',           'value' => $roleLabel,  'kategori' => null],
            ];
        }

        return Inertia::render('Profile/Edit', [
            'user' => [
                'id'            => $user->id,
                'name'          => $user->name,
                'role'          => $user->role,
                'role_label'    => $roleLabel,
                'profile_photo' => $user->profile_photo
                    ? asset('storage/' . $user->profile_photo)
                    : null,
            ],
            'panel'             => $panel,
            'canUpdatePhoto'    => $canUpdatePhoto,
            'nextUpdateDate'    => $nextUpdateDate,
            // 0 = tanpa cooldown
            'photoCooldownDays' => $user->role === 'guru' ? self::PHOTO_COOLDOWN_DAYS : 0,
            'fields'            => $fields,
            'bisaLapor'         => $bisaLapor,
            'kategoriLapor'     => $kategori,
            'tujuanLapor'       => $tujuan,
            'fotoLapor'         => $fotoLapor,
        ]);
    }

    private function labelGender(?string $gender): ?string
    {
        return match ($gender) {
            'L', 'Laki-laki' => 'Laki-laki',
            'P', 'Perempuan' => 'Perempuan',
            default          => $gender,
        };
    }

    /**
     * Nama profil tidak dapat diubah sendiri oleh role mana pun.
     * Route dipertahankan agar tidak memutus link/redirect yang sudah ada.
     */
    public function update(Request $request): RedirectResponse
    {
        return Redirect::route('profile.edit')
            ->with('error', 'Nama profil tidak dapat diubah sendiri. Silakan hubungi admin.');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->role === 'santri') {
            return Redirect::route('profile.edit')
                ->with('error', 'Santri tidak dapat mengubah foto profil.');
        }

        if ($user->role === 'guru' && $user->profile_photo_updated_at) {
            $next = Carbon::parse($user->profile_photo_updated_at)->addDays(self::PHOTO_COOLDOWN_DAYS);

            if ($next->isFuture()) {
                return Redirect::route('profile.edit')->with('error',
                    'Foto profil hanya bisa diubah 1x per ' . self::PHOTO_COOLDOWN_DAYS . ' hari. Coba lagi pada '
                    . $next->locale('id')->isoFormat('D MMMM YYYY, HH:mm') . '.');
            }
        }

        $request->validate([
            'profile_photo' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ], [
            'profile_photo.required' => 'Silakan pilih file foto terlebih dahulu.',
            'profile_photo.image'    => 'File harus berupa gambar.',
            'profile_photo.mimes'    => 'Format foto harus JPG atau PNG.',
            'profile_photo.max'      => 'Ukuran foto maksimal 2MB.',
        ]);

        if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $file = $request->file('profile_photo');

        $user->profile_photo            = $this->processProfilePhoto($file);
        $user->profile_photo_name       = $file->getClientOriginalName();
        $user->profile_photo_updated_at = Carbon::now();
        $user->save();

        return Redirect::route('profile.edit')->with('success', 'Foto profil berhasil diperbarui.');
    }

    public function deletePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->role === 'santri') {
            return Redirect::route('profile.edit')
                ->with('error', 'Santri tidak dapat menghapus foto profil.');
        }

        if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $user->profile_photo      = null;
        $user->profile_photo_name = null;
        // profile_photo_updated_at sengaja TIDAK direset,
        // supaya guru tidak bisa bypass cooldown lewat hapus → upload ulang.
        $user->save();

        return Redirect::route('profile.edit')->with('success', 'Foto profil dihapus.');
    }

    /**
     * Resize & crop foto jadi persegi (bias atas agar kepala tidak terpotong).
     * Disamakan dengan GuruController & AdminController agar avatar konsisten.
     */
    private function processProfilePhoto($file): string
    {
        $maxSize  = 400;
        $path     = $file->store('profile-photos', 'public');
        $fullPath = Storage::disk('public')->path($path);

        $info = getimagesize($fullPath);
        if (!$info) return $path;

        [$width, $height] = $info;
        $mime = $info['mime'];

        $src = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($fullPath),
            'image/png'  => imagecreatefrompng($fullPath),
            default      => null,
        };
        if (!$src) return $path;

        $min  = min($width, $height);
        $srcX = (int) (($width - $min) / 2);
        $srcY = ($height > $width) ? 0 : (int) (($height - $min) / 2);

        $size  = min($min, $maxSize);
        $dst   = imagecreatetruecolor($size, $size);
        $isPng = $mime === 'image/png';

        if ($isPng) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefill($dst, 0, 0, $transparent);
        }

        imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $size, $size, $min, $min);

        if ($isPng) {
            imagepng($dst, $fullPath, 6);
        } else {
            imagejpeg($dst, $fullPath, 95);
        }

        imagedestroy($src);
        imagedestroy($dst);

        return $path;
    }
}