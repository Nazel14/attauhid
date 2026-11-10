<?php

namespace App\Http\Controllers\Keuangan\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\MasterData\Jabatan;
use App\Models\Keuangan\MasterData\Pegawai;
use App\Models\Keuangan\MasterData\Potongan;
use App\Models\Keuangan\MasterData\Tunjangan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class PegawaiController extends Controller
{
    public function index(Request $request)
    {
        // `kelas` di sini adalah relasi wali-kelas raport (kelas.guru_id),
        // bukan lagi employees.class_id yang sudah dibuang.
        $query = Pegawai::with(['jabatan', 'user', 'defaultAllowances', 'defaultDeductions', 'kelasDiampu']);

        // 'ilike' diganti 'like', orWhere dibungkus closure supaya tidak
        // melompati filter status di bawahnya.
        $query->when($request->search, function ($q, $search) {
            $q->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%");
            });
        });

        $query->when($request->statusFilter, function ($q, $status) {
            $q->where('is_active', $status === 'Aktif' ? 1 : 0);
        });

        $sortField     = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        $allowedSorts = ['nip', 'name', 'is_active', 'created_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        $pegawai = $query->paginate(10)->withQueryString();

        return Inertia::render('Keuangan/MasterData/DataPegawai', [
            'pegawai'       => $pegawai,
            'listJabatan'   => Jabatan::orderBy('nama_jabatan')->get(['id', 'nama_jabatan']),
            // listKelas dibuang: wali kelas ditentukan dari panel raport
            // lewat kelas.guru_id, bukan dari form pegawai.
            'listTunjangan' => Tunjangan::orderBy('nama_tunjangan')->get(),
            'listPotongan'  => Potongan::orderBy('nama_potongan')->get(),
            // Bukan cuma role keuangan: pegawai berjabatan guru butuh role
            // 'guru' agar bisa login ke panel raport, meski tidak diberi
            // akses apa pun di keuangan.
            'listRole'      => Role::whereIn('name', array_merge(
                                        config('modules.keuangan_roles', []),
                                        ['guru']
                                    ))
                                    ->orderBy('name')->get(['id', 'name']),
            // Dikirim ke frontend agar hak akses terisi otomatis begitu
            // jabatan dipilih — lihat config('modules.jabatan_ke_role').
            'petaJabatanRole' => $this->petaJabatanRole(),
            'filters'       => $request->only(['search', 'statusFilter', 'sort_field', 'sort_direction']),
        ]);
    }

    /**
     * Peta position_id => nama role, untuk pengisian otomatis di form.
     * Dihitung di backend supaya frontend tidak perlu mencocokkan nama
     * jabatan sendiri (rawan beda ejaan/kapitalisasi).
     */
    private function petaJabatanRole(): array
    {
        $peta = config('modules.jabatan_ke_role', []);

        return Jabatan::get(['id', 'nama_jabatan'])
            ->mapWithKeys(fn ($j) => [
                $j->id => $peta[mb_strtolower($j->nama_jabatan)] ?? null,
            ])
            ->filter()
            ->toArray();
    }

    /**
     * users.role hanya menampung satu nilai dan dibaca seluruh role-gating
     * raport (isGuru, RoleMiddleware, displayName). Kalau pegawai berjabatan
     * Guru diberi role keuangan di kolom itu, akses raport-nya putus.
     *
     * Solusinya: kolom tetap 'guru', role keuangan ditambahkan sebagai role
     * Spatie kedua. availableModules() memeriksa hasAnyRole(), jadi kedua
     * modul tetap terbuka dan permission keuangan tetap berlaku.
     *
     * @return array{0: string, 1: array<string>} [roleKolom, rolesSpatie]
     */
    /**
     * Spatie mendukung banyak role per user, tapi users.role hanya satu kolom
     * dan seluruh role-gating raport membacanya. Jadi dari daftar role yang
     * dipilih, satu harus ditunjuk sebagai role utama untuk kolom itu.
     *
     * Prioritasnya 'guru': akses raport paling mudah putus kalau kolomnya
     * terisi role lain, sementara akses keuangan tetap aman lewat Spatie.
     *
     * @param  array<string>  $rolesPilihan
     * @return array{0: string, 1: array<string>} [roleKolom, rolesSpatie]
     */
    private function petakanRole(int $positionId, array $rolesPilihan): array
    {
        $jabatan = Jabatan::find($positionId);
        $berjabatanGuru = $jabatan && mb_strtolower($jabatan->nama_jabatan) === 'guru';

        $roles = array_values(array_unique(array_filter($rolesPilihan)));

        // Jabatan guru selalu mendapat role 'guru', bahkan bila tidak dicentang —
        // tanpa itu dia tidak bisa membuka panel raport sama sekali.
        if ($berjabatanGuru && ! in_array('guru', $roles, true)) {
            array_unshift($roles, 'guru');
        }

        $roleKolom = in_array('guru', $roles, true) ? 'guru' : ($roles[0] ?? 'guru');

        return [$roleKolom, $roles];
    }

    public function store(Request $request)
    {
        $buatAkun = $request->boolean('buat_akun');

        $validated = $request->validate([
            'nip'         => 'required|string|max:50|unique:gurus,nip',
            'name'        => 'required|string|max:255',
            'position_id' => 'required|exists:positions,id',
            'is_active'   => 'boolean',
            // Akun login opsional: pegawai seperti satpam atau petugas
            // kebersihan tidak perlu masuk ke sistem.
            'username'    => $buatAkun ? 'required|string|max:255|unique:users,name' : 'nullable',
            'password'    => $buatAkun ? 'required|string|min:8' : 'nullable',
            // Array: satu pegawai boleh memegang lebih dari satu peran.
            'roles'       => $buatAkun ? 'required|array|min:1' : 'nullable|array',
            'roles.*'     => 'exists:roles,name',
        ], [
            'nip.required'         => 'NIY wajib diisi.',
            'nip.unique'           => 'NIY ini sudah terdaftar.',
            'position_id.required' => 'Jabatan wajib dipilih.',
            'username.required'    => 'Username wajib diisi bila membuat akun login.',
            'username.unique'      => 'Username ini sudah dipakai akun lain.',
            'password.required'    => 'Password wajib diisi bila membuat akun login.',
            'password.min'         => 'Password minimal 8 karakter.',
            'roles.required'       => 'Minimal satu hak akses wajib dipilih.',
            'roles.min'            => 'Minimal satu hak akses wajib dipilih.',
        ]);

        DB::transaction(function () use ($validated, $request, $buatAkun) {
            $userId = null;

            if ($buatAkun) {
                [$roleKolom, $rolesSpatie] = $this->petakanRole(
                    (int) $validated['position_id'],
                    $validated['roles'] ?? []
                );

                $user = new User();
                $user->name = $validated['username'];
                $user->role = $roleKolom;
                $user->password = Hash::make($validated['password']);
                $user->plain_password = $validated['password'];
                $user->save();

                $user->syncRoles($rolesSpatie);

                $userId = $user->id;
            }

            Pegawai::create([
                'nip'         => $validated['nip'],
                'name'        => $validated['name'],
                'position_id' => $validated['position_id'],
                'is_active'   => $request->boolean('is_active', true),
                'user_id'     => $userId,
            ]);
        });

        return redirect()
            ->route('keuangan.master-data.pegawai')
            ->with('success', $buatAkun
                ? 'Data pegawai dan akun login berhasil ditambahkan.'
                : 'Data pegawai berhasil ditambahkan (tanpa akun login).');
    }

    public function update(Request $request, Pegawai $pegawai)
    {
        // Akun bisa ditambahkan belakangan untuk pegawai yang belum punya.
        $buatAkun = $request->boolean('buat_akun') && ! $pegawai->user_id;

        $validated = $request->validate([
            'nip'         => 'required|string|max:50|unique:gurus,nip,' . $pegawai->id,
            'name'        => 'required|string|max:255',
            'position_id' => 'required|exists:positions,id',
            'is_active'   => 'boolean',
            'username'    => $buatAkun ? 'required|string|max:255|unique:users,name' : 'nullable',
            'password'    => $buatAkun ? 'required|string|min:8' : 'nullable',
            'roles'       => $buatAkun ? 'required|array|min:1' : 'nullable|array',
            'roles.*'     => 'exists:roles,name',
        ], [
            'nip.required'         => 'NIY wajib diisi.',
            'nip.unique'           => 'NIY ini sudah terdaftar.',
            'position_id.required' => 'Jabatan wajib dipilih.',
            'username.unique'      => 'Username ini sudah dipakai akun lain.',
            'password.min'         => 'Password minimal 8 karakter.',
            'roles.required'       => 'Minimal satu hak akses wajib dipilih.',
        ]);

        DB::transaction(function () use ($validated, $request, $pegawai, $buatAkun) {
            $pegawai->update([
                'nip'         => $validated['nip'],
                'name'        => $validated['name'],
                'position_id' => $validated['position_id'],
                'is_active'   => $request->boolean('is_active', true),
            ]);

            if ($buatAkun) {
                [$roleKolom, $rolesSpatie] = $this->petakanRole(
                    (int) $validated['position_id'],
                    $validated['roles'] ?? []
                );

                $user = new User();
                $user->name = $validated['username'];
                $user->role = $roleKolom;
                $user->password = Hash::make($validated['password']);
                $user->plain_password = $validated['password'];
                $user->save();

                $user->syncRoles($rolesSpatie);

                $pegawai->update(['user_id' => $user->id]);
            }
        });

        return redirect()->back()->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function updateSalaryComponents(Request $request, Pegawai $pegawai)
    {
        $request->validate([
            'allowances'                    => 'array',
            'allowances.*.id'               => 'required|exists:allowances,id',
            'allowances.*.nominal_override' => 'nullable|numeric|min:0',
            'deductions'                    => 'array',
            'deductions.*.id'               => 'required|exists:deductions,id',
            'deductions.*.nominal_override' => 'nullable|numeric|min:0',
        ], [
            'allowances.*.id.required'               => 'Pilihan tunjangan tidak boleh kosong.',
            'allowances.*.id.exists'                 => 'Tunjangan yang dipilih tidak valid atau sudah dihapus.',
            'allowances.*.nominal_override.numeric'  => 'Nominal override harus berupa angka.',
            'allowances.*.nominal_override.min'      => 'Nominal override tidak boleh kurang dari 0.',
            'deductions.*.id.required'               => 'Pilihan potongan tidak boleh kosong.',
            'deductions.*.id.exists'                 => 'Potongan yang dipilih tidak valid atau sudah dihapus.',
            'deductions.*.nominal_override.numeric'  => 'Nominal override harus berupa angka.',
            'deductions.*.nominal_override.min'      => 'Nominal override tidak boleh kurang dari 0.',
        ]);

        DB::transaction(function () use ($request, $pegawai) {
            $tunjangan = [];
            foreach ($request->input('allowances', []) as $item) {
                // String kosong dari form dinormalkan jadi null, supaya kolom
                // decimal tidak menerima '' dan nominal default tetap dipakai.
                $tunjangan[$item['id']] = [
                    'nominal_override' => $item['nominal_override'] === '' ? null : $item['nominal_override'],
                ];
            }
            $pegawai->defaultAllowances()->sync($tunjangan);

            $potongan = [];
            foreach ($request->input('deductions', []) as $item) {
                $potongan[$item['id']] = [
                    'nominal_override' => $item['nominal_override'] === '' ? null : $item['nominal_override'],
                ];
            }
            $pegawai->defaultDeductions()->sync($potongan);
        });

        return redirect()->back()->with('success', 'Komponen gaji pegawai berhasil diperbarui.');
    }

    public function destroy(Pegawai $pegawai)
    {
        // Satu-satunya yang tetap DITOLAK. Ini masalah operasional, bukan
        // masalah data: kelasnya akan kehilangan pengampu, dan yang harus
        // menyelesaikannya manusia (tunjuk wali kelas pengganti dulu),
        // bukan sistem.
        if ($pegawai->kelasDiampu()->exists()) {
            return redirect()->back()->withErrors([
                'delete_error' => 'Gagal! Pegawai ini masih menjadi wali kelas di modul raport. '
                                . 'Pindahkan kelasnya ke guru lain dulu, baru hapus.',
            ]);
        }

        // Riwayat tidak lagi memblokir penghapusan — ia hanya menentukan
        // CARA menghapusnya. Riwayat absensi, gaji, dan setoran menunjuk
        // gurus.id; kalau barisnya dilenyapkan, rujukan itu jadi yatim dan
        // laporan gaji bulan-bulan lalu kehilangan namanya.
        $punyaRiwayat = $pegawai->absensi()->exists()
            || $pegawai->setorans()->exists()
            || $pegawai->santriBimbingan()->exists();

        DB::transaction(function () use ($pegawai, $punyaRiwayat) {
            if ($punyaRiwayat) {
                // ARSIP: baris gurus tetap ada (soft delete), sehingga seluruh
                // riwayat tetap utuh dan namanya tetap terbaca di laporan lama.
                // Akun loginnya dilucuti seluruh role — orangnya sudah tidak
                // bekerja di sini, tapi datanya masih dibutuhkan.
                if ($pegawai->user) {
                    $pegawai->user->syncRoles([]);
                }

                $pegawai->update(['is_active' => false]);
                $pegawai->delete();

                return;
            }

            // Betul-betul bersih tanpa jejak → hapus permanen, dan NIY-nya
            // bebas dipakai pegawai baru.
            if ($pegawai->user) {
                if ($pegawai->user->profile_photo && Storage::disk('public')->exists($pegawai->user->profile_photo)) {
                    Storage::disk('public')->delete($pegawai->user->profile_photo);
                }
                $pegawai->user->delete();
            }

            $pegawai->forceDelete();
        });

        return redirect()->back()->with('success', $punyaRiwayat
            ? 'Pegawai diarsipkan. Riwayat absensi, gaji, dan setorannya tetap tersimpan.'
            : 'Data pegawai berhasil dihapus.');
    }
}
