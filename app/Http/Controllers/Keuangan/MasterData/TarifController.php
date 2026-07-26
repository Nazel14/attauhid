<?php

namespace App\Http\Controllers\Keuangan\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\MasterData\Tarif;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TarifController extends Controller
{
    public function index(Request $request)
    {
        $query = Tarif::query();

        // 'ilike' diganti 'like' — ilike hanya ada di PostgreSQL.
        $query->when($request->search, function ($q, $search) {
            $q->where('billing_name', 'like', "%{$search}%");
        });

        $query->when($request->periodFilter, fn ($q, $period) => $q->where('period', $period));

        $query->when($request->statusFilter, function ($q, $status) {
            $q->where('is_active', $status === 'Aktif' ? 1 : 0);
        });

        $sortField     = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        $allowedSorts = ['billing_name', 'nominal', 'period', 'target_student_type', 'is_active', 'created_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        $tarif = $query->paginate(10)->withQueryString();

        return Inertia::render('Keuangan/MasterData/DataTarif', [
            'tarif'   => $tarif,
            'filters' => $request->only(['search', 'periodFilter', 'statusFilter', 'sort_field', 'sort_direction']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'billing_name'        => 'required|string|max:255',
            'nominal'             => 'required|numeric|min:0',
            'target_student_type' => 'required|in:Reguler,Fullday,All',
            'period'              => 'required|in:Bulanan,Semester,Tahunan,Sekali Bayar',
            'is_active'           => 'boolean',
        ], [
            'billing_name.required' => 'Nama tarif wajib diisi.',
            'nominal.required'      => 'Nominal tarif wajib diisi.',
            'nominal.numeric'       => 'Nominal harus berupa angka.',
        ]);

        Tarif::create($validated);

        return redirect()
            ->route('keuangan.master-data.tarif')
            ->with('success', 'Tarif berhasil ditambahkan.');
    }

    public function update(Request $request, Tarif $tarif)
    {
        $validated = $request->validate([
            'billing_name'        => 'required|string|max:255',
            'nominal'             => 'required|numeric|min:0',
            'target_student_type' => 'required|in:Reguler,Fullday,All',
            'period'              => 'required|in:Bulanan,Semester,Tahunan,Sekali Bayar',
            'is_active'           => 'boolean',
        ]);

        $tarif->update($validated);

        return redirect()->back()->with('success', 'Tarif berhasil diperbarui.');
    }

    public function destroy(Tarif $tarif)
    {
        // Tarif yang sudah dipakai di tagihan tidak boleh dihapus,
        // supaya rincian tagihan lama tidak kehilangan acuannya.
        if ($tarif->itemTagihan()->exists()) {
            return redirect()->back()->withErrors([
                'delete_error' => 'Gagal menghapus! Tarif ini sudah digunakan dalam tagihan siswa yang aktif.',
            ]);
        }

        $tarif->delete();

        return redirect()->back()->with('success', 'Tarif berhasil dihapus.');
    }
}