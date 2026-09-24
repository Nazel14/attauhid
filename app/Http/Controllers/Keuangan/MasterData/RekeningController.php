<?php

namespace App\Http\Controllers\Keuangan\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\MasterData\Rekening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

    /**
     * Info pembayaran yang boleh dilihat wali di portal. Semuanya opsional:
     * kas tunai tidak punya nomor rekening, dan tidak setiap rekening
     * dipublikasikan.
     */
    private function aturanBayar(): array
    {
        return [
            'account_number' => 'nullable|string|max:50',
            'account_holder' => 'nullable|string|max:255',
            'catatan_bayar'  => 'nullable|string|max:255',
            'is_public'      => 'boolean',
            'qris'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'hapus_qris'     => 'boolean',
        ];
    }

    /**
     * Simpan/ganti/hapus gambar QRIS, lalu kembalikan nilai kolom qris_path.
     * File lama selalu dihapus saat diganti — tanpa itu folder qris/ menumpuk
     * berkas yang tidak lagi dirujuk siapa pun.
     */
    private function urusQris(Request $request, ?Rekening $rekening): ?string
    {
        $lama = $rekening?->qris_path;

        if ($request->boolean('hapus_qris') && $lama) {
            Storage::disk('public')->delete($lama);
            return null;
        }

        if ($request->hasFile('qris')) {
            if ($lama) {
                Storage::disk('public')->delete($lama);
            }

            return $request->file('qris')->store('qris', 'public');
        }

        return $lama;
    }

    /**
     * Kas tunai tidak bisa dipublikasikan: wali tidak mungkin mentransfer ke
     * laci uang. Dijaga di server, bukan hanya disembunyikan di form.
     */
    private function bolehPublik(string $type, bool $diminta): bool
    {
        return $diminta && in_array($type, ['Bank', 'E-Wallet'], true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(array_merge([
            'account_name' => 'required|string|max:255|unique:accounts,account_name',
            'type'         => 'required|in:Tunai,Bank,E-Wallet',
            'balance'      => 'required|numeric|min:0',
        ], $this->aturanBayar()), [
            'account_name.required' => 'Nama rekening wajib diisi.',
            'account_name.unique'   => 'Nama rekening ini sudah terdaftar.',
            'type.required'         => 'Tipe rekening wajib dipilih.',
            'balance.required'      => 'Saldo awal wajib diisi.',
            'qris.image'            => 'Berkas QRIS harus berupa gambar.',
            'qris.max'              => 'Ukuran gambar QRIS maksimal 2 MB.',
        ]);

        unset($validated['qris'], $validated['hapus_qris']);

        $validated['qris_path'] = $this->urusQris($request, null);
        $validated['is_public'] = $this->bolehPublik($validated['type'], $request->boolean('is_public'));

        Rekening::create($validated);

        return redirect()
            ->route('keuangan.master-data.rekening')
            ->with('success', 'Rekening berhasil ditambahkan.');
    }

    public function update(Request $request, Rekening $rekening)
    {
        $validated = $request->validate(array_merge([
            'account_name' => 'required|string|max:255|unique:accounts,account_name,' . $rekening->id,
            'type'         => 'required|in:Tunai,Bank,E-Wallet',
            'balance'      => 'required|numeric|min:0',
        ], $this->aturanBayar()));

        unset($validated['qris'], $validated['hapus_qris']);

        $validated['qris_path'] = $this->urusQris($request, $rekening);
        $validated['is_public'] = $this->bolehPublik($validated['type'], $request->boolean('is_public'));

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

        if ($rekening->qris_path) {
            Storage::disk('public')->delete($rekening->qris_path);
        }

        $rekening->delete();

        return redirect()->back()->with('success', 'Rekening berhasil dihapus.');
    }
}