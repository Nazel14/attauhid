<?php

namespace App\Http\Controllers\Keuangan\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\MasterData\Jabatan;
use App\Models\Keuangan\MasterData\Kelas;
use App\Models\Keuangan\MasterData\Pegawai;
use App\Models\Keuangan\MasterData\Potongan;
use App\Models\Keuangan\MasterData\Tunjangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PegawaiController extends Controller
{
    public function index(Request $request)
    {
        $query = Pegawai::with(['jabatan', 'user', 'defaultAllowances', 'defaultDeductions', 'kelas']);

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
            'listKelas'     => Kelas::where('is_active', true)->orderBy('nama_kelas')->get(['id', 'nama_kelas', 'tingkat']),
            'listTunjangan' => Tunjangan::orderBy('nama_tunjangan')->get(),
            'listPotongan'  => Potongan::orderBy('nama_potongan')->get(),
            'filters'       => $request->only(['search', 'statusFilter', 'sort_field', 'sort_direction']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nip'         => 'required|string|max:50|unique:employees,nip',
            'name'        => 'required|string|max:255',
            'position_id' => 'required|exists:positions,id',
            'class_id'    => 'nullable|exists:classes,id',
            'is_active'   => 'boolean',
        ], [
            'nip.required'         => 'NIY wajib diisi.',
            'nip.unique'           => 'NIY ini sudah terdaftar.',
            'position_id.required' => 'Jabatan wajib dipilih.',
            'class_id.exists'      => 'Kelas yang dipilih tidak valid.',
        ]);

        Pegawai::create($validated);

        return redirect()
            ->route('keuangan.master-data.pegawai')
            ->with('success', 'Data pegawai berhasil ditambahkan.');
    }

    public function update(Request $request, Pegawai $pegawai)
    {
        $validated = $request->validate([
            'nip'         => 'required|string|max:50|unique:employees,nip,' . $pegawai->id,
            'name'        => 'required|string|max:255',
            'position_id' => 'required|exists:positions,id',
            'class_id'    => 'nullable|exists:classes,id',
            'is_active'   => 'boolean',
        ], [
            'nip.required'         => 'NIY wajib diisi.',
            'nip.unique'           => 'NIY ini sudah terdaftar.',
            'position_id.required' => 'Jabatan wajib dipilih.',
            'class_id.exists'      => 'Kelas yang dipilih tidak valid.',
        ]);

        $pegawai->update($validated);

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
        if ($pegawai->absensi()->exists()) {
            return redirect()->back()->withErrors([
                'delete_error' => 'Gagal! Pegawai ini sudah memiliki riwayat absensi/gaji.',
            ]);
        }

        // Versi asli memakai $pegawai->user->email — tabel users di sini
        // tidak punya kolom email, jadi dipakai name.
        if ($pegawai->user) {
            return redirect()->back()->withErrors([
                'delete_error' => 'Gagal! Pegawai ini terhubung dengan akun user "' . $pegawai->user->name . '".',
            ]);
        }

        $pegawai->delete();

        return redirect()->back()->with('success', 'Data pegawai berhasil dihapus.');
    }
}