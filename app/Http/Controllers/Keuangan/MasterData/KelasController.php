<?php

namespace App\Http\Controllers\Keuangan\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\MasterData\Kelas;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KelasController extends Controller
{
    public function index(Request $request)
    {
        $query = Kelas::query();

        // 'ilike' diganti 'like'. orWhere dibungkus closure supaya tidak
        // melompati kondisi lain bila nanti ada filter tambahan.
        $query->when($request->search, function ($q, $search) {
            $q->where(function ($w) use ($search) {
                $w->where('nama_kelas', 'like', "%{$search}%")
                  ->orWhere('tingkat', 'like', "%{$search}%");
            });
        });

        $sortField     = $request->input('sort_field', 'nama_kelas');
        $sortDirection = $request->input('sort_direction', 'asc');

        $allowedSorts = ['nama_kelas', 'tingkat', 'kapasitas', 'is_active'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('nama_kelas', 'asc');
        }

        $query->withCount(['siswas', 'pegawais']);

        $kelas = $query->paginate(10)->withQueryString();

        return Inertia::render('Keuangan/MasterData/DataKelas', [
            'kelas'   => $kelas,
            'filters' => $request->only(['search', 'sort_field', 'sort_direction']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:255|unique:classes,nama_kelas',
            'tingkat'    => 'nullable|string|max:100',
            'kapasitas'  => 'required|integer|min:1',
            'is_active'  => 'boolean',
        ], [
            'nama_kelas.unique' => 'Nama kelas ini sudah digunakan, silakan pilih nama lain.',
        ]);

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
            'nama_kelas' => 'required|string|max:255|unique:classes,nama_kelas,' . $kelas->id,
            'tingkat'    => 'nullable|string|max:100',
            'kapasitas'  => 'required|integer|min:1',
            'is_active'  => 'boolean',
        ]);

        $kelas->update($validated);

        return redirect()->back()->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $kelas = Kelas::findOrFail($id);

        if ($kelas->siswas()->exists() || $kelas->pegawais()->exists()) {
            return redirect()->back()->withErrors([
                'delete_error' => 'Gagal menghapus! Kosongkan terlebih dahulu daftar Siswa dan Pegawai dari kelas ini.',
            ]);
        }

        $kelas->delete();

        return redirect()->back()->with('success', 'Data kelas berhasil dihapus.');
    }
}