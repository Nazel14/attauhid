<?php

namespace App\Http\Controllers\Keuangan\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\MasterData\Jabatan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JabatanController extends Controller
{
    public function index(Request $request)
    {
        $query = Jabatan::query();

        // 'ilike' diganti 'like' — ilike hanya ada di PostgreSQL.
        // where/orWhere juga dibungkus closure supaya kalau nanti ada
        // filter lain, orWhere tidak melompati kondisi tersebut.
        $query->when($request->search, function ($q, $search) {
            $q->where(function ($w) use ($search) {
                $w->where('nama_jabatan', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        });

        $sortField     = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        $allowedSorts = ['nama_jabatan', 'gaji_pokok_default', 'created_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        $jabatan = $query->paginate(10)->withQueryString();

        return Inertia::render('Keuangan/MasterData/DataJabatan', [
            'jabatan' => $jabatan,
            'filters' => $request->only(['search', 'sort_field', 'sort_direction']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_jabatan'       => 'required|string|max:255|unique:positions,nama_jabatan',
            'description'        => 'nullable|string',
            'gaji_pokok_default' => 'required|numeric|min:0',
        ], [
            'nama_jabatan.required'       => 'Nama jabatan wajib diisi.',
            'nama_jabatan.unique'         => 'Nama jabatan ini sudah terdaftar.',
            'gaji_pokok_default.required' => 'Gaji pokok default wajib diisi.',
            'gaji_pokok_default.numeric'  => 'Gaji pokok harus berupa angka.',
        ]);

        Jabatan::create($validated);

        return redirect()
            ->route('keuangan.master-data.jabatan')
            ->with('success', 'Jabatan berhasil ditambahkan.');
    }

    public function update(Request $request, Jabatan $jabatan)
    {
        $validated = $request->validate([
            'nama_jabatan'       => 'required|string|max:255|unique:positions,nama_jabatan,' . $jabatan->id,
            'description'        => 'nullable|string',
            'gaji_pokok_default' => 'required|numeric|min:0',
        ], [
            'nama_jabatan.required'       => 'Nama jabatan wajib diisi.',
            'nama_jabatan.unique'         => 'Nama jabatan ini sudah terdaftar.',
            'gaji_pokok_default.required' => 'Gaji pokok default wajib diisi.',
        ]);

        $jabatan->update($validated);

        return redirect()->back()->with('success', 'Jabatan berhasil diperbarui.');
    }

    public function destroy(Jabatan $jabatan)
    {
        // Jabatan yang masih dipakai pegawai tidak boleh dihapus.
        if ($jabatan->pegawai()->exists()) {
            return redirect()->back()->withErrors([
                'delete_error' => 'Gagal menghapus! Jabatan ini masih digunakan oleh pegawai.',
            ]);
        }

        $jabatan->delete();

        return redirect()->back()->with('success', 'Jabatan berhasil dihapus.');
    }
}