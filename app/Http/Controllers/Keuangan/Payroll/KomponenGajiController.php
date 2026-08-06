<?php

namespace App\Http\Controllers\Keuangan\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\MasterData\Potongan;
use App\Models\Keuangan\MasterData\Tunjangan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KomponenGajiController extends Controller
{
    public function index(Request $request)
    {
        $tunjangan = Tunjangan::query()
            ->when($request->search_tunjangan, fn ($q, $c) => $q->where('nama_tunjangan', 'like', "%{$c}%"))
            ->orderBy(
                $this->kolomAman($request->input('sort_tunjangan'), ['nama_tunjangan', 'jenis', 'nominal_default', 'created_at']),
                $request->input('dir_tunjangan') === 'asc' ? 'asc' : 'desc'
            )
            ->paginate(10, ['*'], 't_page')
            ->withQueryString();

        $potongan = Potongan::query()
            ->when($request->search_potongan, fn ($q, $c) => $q->where('nama_potongan', 'like', "%{$c}%"))
            ->orderBy(
                $this->kolomAman($request->input('sort_potongan'), ['nama_potongan', 'jenis', 'nominal_default', 'created_at']),
                $request->input('dir_potongan') === 'asc' ? 'asc' : 'desc'
            )
            ->paginate(10, ['*'], 'p_page')
            ->withQueryString();

        return Inertia::render('Keuangan/Penggajian/KomponenPenggajian', [
            'tunjangan' => $tunjangan,
            'potongan'  => $potongan,
            'filters'   => $request->only([
                'search_tunjangan', 'sort_tunjangan', 'dir_tunjangan',
                'search_potongan', 'sort_potongan', 'dir_potongan',
            ]),
        ]);
    }

    public function storeTunjangan(Request $request)
    {
        // create($request->all()) versi asli menerima seluruh isi request —
        // dipersempit ke hasil validasi saja.
        Tunjangan::create($this->validasi($request, 'nama_tunjangan'));

        return redirect()->back()->with('success', 'Tunjangan berhasil ditambahkan.');
    }

    public function updateTunjangan(Request $request, Tunjangan $tunjangan)
    {
        $tunjangan->update($this->validasi($request, 'nama_tunjangan', $tunjangan->id, 'allowances'));

        return redirect()->back()->with('success', 'Tunjangan berhasil diperbarui.');
    }

    public function destroyTunjangan(Tunjangan $tunjangan)
    {
        $tunjangan->delete();

        return redirect()->back()->with('success', 'Tunjangan berhasil dihapus.');
    }

    public function storePotongan(Request $request)
    {
        Potongan::create($this->validasi($request, 'nama_potongan'));

        return redirect()->back()->with('success', 'Potongan berhasil ditambahkan.');
    }

    public function updatePotongan(Request $request, Potongan $potongan)
    {
        $potongan->update($this->validasi($request, 'nama_potongan', $potongan->id, 'deductions'));

        return redirect()->back()->with('success', 'Potongan berhasil diperbarui.');
    }

    public function destroyPotongan(Potongan $potongan)
    {
        $potongan->delete();

        return redirect()->back()->with('success', 'Potongan berhasil dihapus.');
    }

    /* ================= Helper ================= */

    private function validasi(Request $request, string $kolomNama, $abaikanId = null, ?string $tabel = null): array
    {
        return $request->validate([
            $kolomNama        => 'required|string|max:255',
            'jenis'           => 'required|in:Tetap,Kehadiran',
            'nominal_default' => 'required|numeric|min:0',
        ], [
            "{$kolomNama}.required"    => 'Nama komponen wajib diisi.',
            'jenis.required'           => 'Jenis komponen wajib dipilih.',
            'nominal_default.required' => 'Nominal default wajib diisi.',
            'nominal_default.numeric'  => 'Nominal harus berupa angka.',
        ]);
    }

    private function kolomAman(?string $kolom, array $izin): string
    {
        return in_array($kolom, $izin, true) ? $kolom : 'created_at';
    }
}