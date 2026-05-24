<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use App\Models\Santri;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PresensiController extends Controller
{
    /**
     * Menampilkan daftar Presensi
     */
    public function index()
    {
        $guruId = Auth::user()->guru->id;
        $presensis = Presensi::whereHas('santri', function ($query) use ($guruId) {
            $query->where('guru_id', $guruId);
        })
        ->with('santri')
        ->orderBy('tanggal', 'desc')
        ->get();
        
        return view('guru.presensis.index', compact('presensis'));
    }

    /**
     * Menampilkan form input Presensi massal (lebih efisien)
     */
    public function create()
    {
        $guruId = Auth::user()->guru->id;
        $santris = Santri::where('guru_id', $guruId)->get(['id', 'name']);
        $tanggal = now()->toDateString();
        
        return view('guru.presensis.create', compact('santris', 'tanggal'));
    }

    /**
     * Menyimpan Presensi massal
     */
    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'sesi' => 'required|in:Subuh,Pagi,Sore,Malam',
            'presensi.*.santri_id' => 'required|exists:santris,id',
            'presensi.*.status' => 'required|in:Hadir,Izin,Sakit,Alpha',
        ]);
        
        $guruId = Auth::user()->guru->id;
        $presensiData = [];

        foreach ($request->presensi as $item) {
            // Cek apakah santri ini memang santri bimbingan guru ini
            $santri = Santri::find($item['santri_id']);
            if (!$santri || $santri->guru_id != $guruId) {
                continue; // Skip jika tidak valid
            }
            
            $presensiData[] = [
                'santri_id' => $item['santri_id'],
                'tanggal' => $request->tanggal,
                'sesi' => $request->sesi,
                'status' => $item['status'],
                'poin' => ($item['status'] == 'Hadir' ? 5 : 0), // Contoh logika poin
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Simpan atau Update (On Duplicate Key Update) jika Anda ingin mencegah duplikat
        // Untuk saat ini, kita akan menggunakan metode sederhana dan mengandalkan unique key di migrasi
        
        try {
            Presensi::insert($presensiData);
            return redirect()->route('guru.presensis.index')->with('success', 'Presensi berhasil dicatat.');
        } catch (\Exception $e) {
            // Ini akan menangkap error duplikat jika Anda mencoba memasukkan presensi yang sama dua kali
            return back()->withInput()->with('error', 'Gagal mencatat presensi. Mungkin presensi untuk sesi dan tanggal ini sudah ada.');
        }
    }
}