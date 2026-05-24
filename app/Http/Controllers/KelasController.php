<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Guru;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KelasController extends Controller
{
    /**
     * Menampilkan daftar Kelas/Halaqah
     */
    public function index()
    {
        $kelas = Kelas::with('guru')->get();
        return view('admin.kelas.index', compact('kelas'));
    }

    /**
     * Menampilkan form tambah Kelas
     */
    public function create()
    {
        // Ambil semua Guru untuk dropdown Koordinator
        $gurus = Guru::all();
        return view('admin.kelas.create', compact('gurus'));
    }

    /**
     * Menyimpan Kelas baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:kelas,name',
            'type' => 'required|in:Kelas,Halaqah',
            'guru_id' => 'required|exists:gurus,id',
        ]);

        Kelas::create($request->all());

        return redirect()->route('admin.kelas.index')->with('success', 'Kelas/Halaqah berhasil ditambahkan.');
    }

    /**
     * Menampilkan form edit Kelas
     */
    public function edit(Kelas $kela)
    {
        $gurus = Guru::all();
        return view('admin.kelas.edit', compact('kela', 'gurus'));
    }

    /**
     * Memperbarui Kelas
     */
    public function update(Request $request, Kelas $kela)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('kelas', 'name')->ignore($kela->id)],
            'type' => 'required|in:Kelas,Halaqah',
            'guru_id' => 'required|exists:gurus,id',
        ]);

        $kela->update($request->all());

        return redirect()->route('admin.kelas.index')->with('success', 'Kelas/Halaqah berhasil diperbarui.');
    }

    /**
     * Menghapus Kelas
     */
    public function destroy(Kelas $kela)
    {
        // Note: Karena Santri terhubung ke Kelas dengan onDelete('cascade'), 
        // pastikan Anda menangani Santri yang terkait sebelum menghapus Kelas, 
        // atau relasi harus diubah menjadi onDelete('set null') di migrasi Santri.
        
        $kela->delete();
        return redirect()->route('admin.kelas.index')->with('success', 'Kelas/Halaqah berhasil dihapus.');
    }
}