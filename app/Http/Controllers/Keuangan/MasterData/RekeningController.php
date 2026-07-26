<?php

namespace App\Http\Controllers\Keuangan\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\MasterData\Rekening;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RekeningController extends Controller
{
    public function index(Request $request)
    {
        $query = Rekening::query();

        // 'ilike' versi asli diganti 'like' — ilike hanya ada di PostgreSQL,
        // dan collation MySQL sudah case-insensitive.
        $query->when($request->search, function ($q, $search) {
            $q->where('account_name', 'like', "%{$search}%");
        });

        $query->when($request->typeFilter, function ($q, $type) {
            $q->where('type', $type);
        });

        $sortField     = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        $allowedSorts = ['account_name', 'type', 'balance', 'created_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        $rekening = $query->paginate(10)->withQueryString();

        return Inertia::render('Keuangan/MasterData/DataRekening', [
            'rekening' => $rekening,
            'filters'  => $request->only(['search', 'typeFilter', 'sort_field', 'sort_direction']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_name' => 'required|string|max:255|unique:accounts,account_name',
            'type'         => 'required|in:Tunai,Bank,E-Wallet',
            'balance'      => 'required|numeric|min:0',
        ], [
            'account_name.required' => 'Nama rekening wajib diisi.',
            'account_name.unique'   => 'Nama rekening ini sudah terdaftar.',
            'type.required'         => 'Tipe rekening wajib dipilih.',
            'balance.required'      => 'Saldo awal wajib diisi.',
        ]);

        Rekening::create($validated);

        return redirect()
            ->route('keuangan.master-data.rekening')
            ->with('success', 'Rekening berhasil ditambahkan.');
    }

    public function update(Request $request, Rekening $rekening)
    {
        $validated = $request->validate([
            'account_name' => 'required|string|max:255|unique:accounts,account_name,' . $rekening->id,
            'type'         => 'required|in:Tunai,Bank,E-Wallet',
            'balance'      => 'required|numeric|min:0',
        ]);

        $rekening->update($validated);

        return redirect()->back()->with('success', 'Rekening berhasil diperbarui.');
    }

    public function destroy(Rekening $rekening)
    {
        // Rekening yang sudah punya riwayat transaksi tidak boleh dihapus,
        // supaya mutasi kas tidak kehilangan induknya.
        if ($rekening->pemasukan()->exists() || $rekening->pengeluaran()->exists()) {
            return redirect()->back()->withErrors([
                'delete_error' => 'Gagal menghapus! Rekening ini sudah memiliki riwayat transaksi pemasukan/pengeluaran.',
            ]);
        }

        $rekening->delete();

        return redirect()->back()->with('success', 'Rekening berhasil dihapus.');
    }
}