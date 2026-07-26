<?php

namespace App\Http\Controllers\Keuangan\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\MasterData\Kelas;
use App\Models\Keuangan\MasterData\Siswa;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $query = Siswa::with('kelas');

        // 'ilike' diganti 'like', orWhere dibungkus closure supaya tidak
        // melompati filter tipe/angkatan di bawahnya.
        $query->when($request->search, function ($q, $search) {
            $q->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        });

        $query->when($request->typeFilter, fn ($q, $type) => $q->where('student_type', $type));
        $query->when($request->angkatanFilter, fn ($q, $angkatan) => $q->where('class', $angkatan));

        $sortField     = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        $allowedSorts = ['nis', 'name', 'class', 'is_active', 'created_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        $siswa = $query->paginate(10)->withQueryString();

        $listAngkatan = Siswa::select('class')
            ->whereNotNull('class')
            ->distinct()
            ->orderBy('class', 'desc')
            ->pluck('class');

        $listKelas = Kelas::where('is_active', true)
            ->orderBy('nama_kelas')
            ->get(['id', 'nama_kelas', 'tingkat']);

        return Inertia::render('Keuangan/MasterData/DataSiswa', [
            'siswa'        => $siswa,
            'listAngkatan' => $listAngkatan,
            'listKelas'    => $listKelas,
            'filters'      => $request->only(['search', 'typeFilter', 'angkatanFilter', 'sort_field', 'sort_direction']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nis'            => 'required|string|unique:students,nis',
            'name'           => 'required|string|max:255',
            'student_type'   => 'required|in:Reguler,Fullday',
            'class'          => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'parent_name'    => 'required|string|max:255',
            'parent_contact' => 'required|string|max:20',
            'class_id'       => 'nullable|exists:classes,id',
            'is_active'      => 'boolean',
            'is_raport_held' => 'boolean',
        ], [
            'nis.required'            => 'NIS wajib diisi.',
            'nis.unique'              => 'NIS ini sudah terdaftar di database.',
            'name.required'           => 'Nama siswa wajib diisi.',
            'class.required'          => 'Angkatan wajib diisi.',
            'parent_name.required'    => 'Nama orang tua/wali wajib diisi.',
            'parent_contact.required' => 'Nomor kontak wajib diisi.',
        ]);

        Siswa::create($validated);

        return redirect()
            ->route('keuangan.master-data.siswa')
            ->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function update(Request $request, Siswa $siswa)
    {
        $validated = $request->validate([
            'nis'            => 'required|string|unique:students,nis,' . $siswa->id,
            'name'           => 'required|string|max:255',
            'student_type'   => 'required|in:Reguler,Fullday',
            'class'          => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'class_id'       => 'nullable|exists:classes,id',
            'parent_name'    => 'required|string|max:255',
            'parent_contact' => 'required|string|max:20',
            'is_active'      => 'boolean',
            'is_raport_held' => 'boolean',
        ], [
            'nis.required'            => 'NIS wajib diisi.',
            'nis.unique'              => 'NIS ini sudah terdaftar di database.',
            'name.required'           => 'Nama siswa wajib diisi.',
            'class.required'          => 'Angkatan wajib diisi.',
            'parent_name.required'    => 'Nama orang tua/wali wajib diisi.',
            'parent_contact.required' => 'Nomor kontak wajib diisi.',
        ]);

        $siswa->update($validated);

        return redirect()->back()->with('success', 'Data siswa berhasil diperbarui.');
    }
}