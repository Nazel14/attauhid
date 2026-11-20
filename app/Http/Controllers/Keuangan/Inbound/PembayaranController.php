<?php

namespace App\Http\Controllers\Keuangan\Inbound;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\Finance\MutasiKas;
use App\Models\Keuangan\Inbound\Pembayaran;
use App\Models\Keuangan\Inbound\Tagihan;
use App\Models\Keuangan\MasterData\Rekening;
use App\Models\Keuangan\MasterData\Siswa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PembayaranController extends Controller
{
    /**
     * Kategori yang mewajibkan siswa terdaftar.
     *
     * 'uang_masuk' sengaja TIDAK di sini: uang pendaftaran dibayar calon
     * siswa yang masih ikut seleksi dan belum tentu diterima, jadi belum
     * ada di master siswa. Namanya diisi manual lewat `nama_pihak`.
     *
     * 'infaq_arahan' memilih santri terdaftar tapi TANPA tagihan:
     * nominalnya bebas dan tidak pernah menyisakan tunggakan.
     */
    private const PERLU_SISWA = ['tagihan', 'infaq_arahan'];

    /** Kategori yang mewajibkan nama pihak diisi manual. */
    private const PERLU_NAMA = ['uang_masuk', 'donatur'];

    public function index(Request $request)
    {
        $kategori = $request->input('kategori', 'tagihan');

        if (!array_key_exists($kategori, Pembayaran::KATEGORI)) {
            $kategori = 'tagihan';
        }

        $query = Pembayaran::with([
            'siswa:id,nis,name',
            'tagihan:id,invoice_number',
            'rekening:id,account_name',
            'pemeriksa:id,name',
            'penyetuju:id,name',
            'dibuatOleh:id,name',
        ])->where('kategori', $kategori);

        // 'ilike' diganti 'like', orWhereHas dibungkus closure supaya tidak
        // melompati filter kategori/bulan/status di atas dan di bawahnya.
        $query->when($request->search, function ($q, $cari) {
            $q->where(function ($w) use ($cari) {
                $w->where('receipt_number', 'like', "%{$cari}%")
                  ->orWhere('nama_pihak', 'like', "%{$cari}%")
                  ->orWhereHas('siswa', fn ($s) => $s->where('name', 'like', "%{$cari}%"));
            });
        });

        $query->when($request->monthFilter, function ($q, $bulan) {
            $q->whereYear('payment_date', substr($bulan, 0, 4))
              ->whereMonth('payment_date', substr($bulan, 5, 2));
        });

        $query->when($request->statusFilter, fn ($q, $s) => $q->where('status_approval', $s));

        $sortField     = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        $allowedSorts = ['receipt_number', 'payment_date', 'nominal', 'status_approval', 'created_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        $pembayaran = $query->paginate(10)->withQueryString();

        $pembayaran->getCollection()->transform(function ($item) {
            $item->attachments = $item->getMedia('bukti_pembayaran')->map(fn ($m) => [
                'id'        => $m->id,
                'url'       => route('keuangan.media.show', $m->id),
                'file_name' => $m->file_name,
            ]);

            return $item;
        });

        return Inertia::render('Keuangan/Pemasukan/Pembayaran', [
            'pembayaran'   => $pembayaran,
            'kategoriAktif'=> $kategori,
            'daftarKategori' => Pembayaran::KATEGORI,
            'listSiswa'    => $this->daftarSiswa($kategori),
            'listRekening' => Rekening::orderBy('account_name')->get(['id', 'account_name', 'type']),
            'listTagihan'  => $kategori === 'tagihan'
                ? Tagihan::whereHas('header', fn ($q) => $q->where('status', 'Published'))
                    ->whereIn('status', ['Unpaid', 'Installment'])
                    ->get(['id', 'invoice_number', 'student_id', 'remaining_balance'])
                : [],
            'filters' => $request->only(['search', 'kategori', 'monthFilter', 'statusFilter', 'sort_field', 'sort_direction']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);

        return DB::transaction(function () use ($request, $data) {
            $kategori = $data['kategori'];
            $tagihan  = $data['invoice_id'] ?? null ? Tagihan::find($data['invoice_id']) : null;

            $pembayaran = Pembayaran::create([
                // time() saja bertabrakan bila dua pembayaran dibuat dalam
                // detik yang sama — ditambah string acak.
                'receipt_number'  => Pembayaran::PREFIX[$kategori] . '-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
                'kategori'        => $kategori,
                // Hanya kategori ber-siswa yang menyimpan student_id; uang masuk
                // memakai nama manual, jadi student_id dibiarkan kosong.
                'student_id'      => $tagihan?->student_id
                                     ?? (in_array($kategori, self::PERLU_SISWA, true) ? ($data['student_id'] ?? null) : null),
                'invoice_id'      => $data['invoice_id'] ?? null,
                'nama_pihak'      => $data['nama_pihak'] ?? null,
                'account_id'      => $data['account_id'],
                'nominal'         => $data['nominal'],
                'payment_date'    => $data['payment_date'],
                'payment_method'  => $data['payment_method'],
                'catatan'         => $data['catatan'] ?? null,
                'status_approval' => 'draft',
                'created_by'      => Auth::id(),
            ]);

            $this->simpanBukti($request, $pembayaran);

            return redirect()->back()->with('success', 'Pembayaran berhasil dicatat sebagai draft.');
        });
    }

    public function update(Request $request, Pembayaran $pembayaran)
    {
        if ($pembayaran->status_approval !== 'draft') {
            return redirect()->back()->withErrors(['approve' => 'Hanya pembayaran berstatus draft yang dapat diubah.']);
        }

        $data = $this->validasi($request, $pembayaran);

        DB::transaction(function () use ($request, $pembayaran, $data) {
            $pembayaran->update([
                'payment_date'   => $data['payment_date'],
                'account_id'     => $data['account_id'],
                'payment_method' => $data['payment_method'],
                'nominal'        => $data['nominal'],
                'nama_pihak'     => $data['nama_pihak'] ?? null,
                'catatan'        => $data['catatan'] ?? null,
            ]);

            // Bukti lama hanya diganti bila ada unggahan baru.
            if ($request->hasFile('proof_of_payment')) {
                $pembayaran->clearMediaCollection('bukti_pembayaran');
                $this->simpanBukti($request, $pembayaran);
            }
        });

        return redirect()->back()->with('success', 'Pembayaran berhasil diperbarui.');
    }

    public function destroy(Pembayaran $pembayaran)
    {
        if ($pembayaran->status_approval !== 'draft') {
            return redirect()->back()->withErrors(['approve' => 'Pembayaran yang sudah diproses tidak dapat dihapus.']);
        }

        $pembayaran->clearMediaCollection('bukti_pembayaran');
        $pembayaran->delete();

        return redirect()->back()->with('success', 'Pembayaran berhasil dihapus.');
    }

    public function approve(Request $request, Pembayaran $pembayaran)
    {
        // Versi asli membaca $request->stage tanpa validasi sama sekali.
        $request->validate(['stage' => 'required|in:diperiksa,disetujui']);

        $stage = $request->stage;
        $izin  = ['diperiksa' => 'pembayaran.approve_diperiksa', 'disetujui' => 'pembayaran.approve_disetujui'];

        abort_unless(Auth::user()->can($izin[$stage]), 403, 'Anda tidak memiliki akses untuk tahap ini.');

        $syarat = ['diperiksa' => 'draft', 'disetujui' => 'diperiksa'];

        if ($pembayaran->status_approval !== $syarat[$stage]) {
            return redirect()->back()->withErrors([
                'approve' => 'Transisi tidak valid. Status saat ini: ' . $pembayaran->status_approval . '.',
            ]);
        }

        if ($stage === 'diperiksa') {
            $pembayaran->update([
                'status_approval' => 'diperiksa',
                'diperiksa_by'    => Auth::id(),
                'diperiksa_at'    => now(),
            ]);

            return redirect()->back()->with('success', 'Pembayaran ditandai sudah diperiksa.');
        }

        try {
            DB::transaction(function () use ($pembayaran) {
                $now = now();

                // Baris rekening dikunci agar pembacaan saldo dan penulisan
                // mutasi tidak balapan dengan approval lain.
                $rekening = Rekening::lockForUpdate()->findOrFail($pembayaran->account_id);

                if ($pembayaran->invoice_id) {
                    $tagihan = Tagihan::lockForUpdate()->findOrFail($pembayaran->invoice_id);

                    // Nominal divalidasi saat draft dibuat, tapi saldo baru
                    // berkurang di sini. Dua draft bisa sama-sama lolos lalu
                    // bersama-sama melebihi sisa tagihan — dicek ulang.
                    if (bccomp((string) $pembayaran->nominal, (string) $tagihan->remaining_balance, 2) === 1) {
                        throw new \RuntimeException(
                            'Nominal melebihi sisa tagihan saat ini (Rp '
                            . number_format((float) $tagihan->remaining_balance, 0, ',', '.') . ').'
                        );
                    }

                    $tagihan->remaining_balance -= $pembayaran->nominal;
                    $tagihan->status = $tagihan->remaining_balance <= 0 ? 'Paid' : 'Installment';
                    $tagihan->save();
                }

                $rekening->balance += $pembayaran->nominal;
                $rekening->save();

                $pembayaran->update([
                    'status_approval' => 'disetujui',
                    'disetujui_by'    => Auth::id(),
                    'disetujui_at'    => $now,
                ]);

                MutasiKas::create([
                    'account_id'              => $rekening->id,
                    'tanggal_transaksi'       => $now,
                    'keterangan'              => Pembayaran::KATEGORI[$pembayaran->kategori]
                                                 . ' — ' . ($pembayaran->pihak !== '—' ? $pembayaran->pihak : $pembayaran->receipt_number),
                    'jenis'                   => 'Debit',
                    'nominal'                 => $pembayaran->nominal,
                    'saldo_setelah_transaksi' => $rekening->balance,
                    'reference_type'          => Pembayaran::class,
                    'reference_id'            => $pembayaran->id,
                    'created_by'              => Auth::id(),
                ]);
            });
        } catch (\RuntimeException $e) {
            return redirect()->back()->withErrors(['approve' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Pembayaran disetujui dan tercatat di buku kas.');
    }

    public function printKwitansi(Pembayaran $pembayaran)
    {
        abort_unless($pembayaran->status_approval === 'disetujui', 403,
            'Kwitansi hanya untuk pembayaran yang sudah disetujui.');

        $pembayaran->load(['siswa', 'tagihan.header', 'rekening', 'penyetuju']);

        return Pdf::loadView('pdf.kwitansi', compact('pembayaran'))
            ->setPaper('A5', 'landscape')
            ->stream('Kwitansi_' . $pembayaran->receipt_number . '.pdf');
    }

    /* ================= Helper ================= */

    private function validasi(Request $request, ?Pembayaran $existing = null): array
    {
        $kategori = $existing?->kategori ?? $request->input('kategori');

        $aturan = [
            'kategori'          => 'required|in:' . implode(',', array_keys(Pembayaran::KATEGORI)),
            'account_id'        => 'required|exists:accounts,id',
            'payment_method'    => 'required|in:Tunai,Transfer Bank',
            'payment_date'      => 'required|date',
            'catatan'           => 'nullable|string|max:500',
            'proof_of_payment'  => 'nullable|array|max:3',
            'proof_of_payment.*'=> 'file|mimes:jpg,jpeg,png,pdf|max:2048',
            'nominal'           => ['required', 'numeric', 'min:1', function ($attr, $value, $fail) use ($request, $existing) {
                $invoiceId = $existing?->invoice_id ?? $request->input('invoice_id');

                if (!$invoiceId) {
                    return;
                }

                $tagihan = Tagihan::find($invoiceId);

                if ($tagihan && $value > $tagihan->remaining_balance) {
                    $fail('Nominal melebihi sisa tagihan (Rp ' . number_format((float) $tagihan->remaining_balance, 0, ',', '.') . ').');
                }
            }],
        ];

        // Bukti transfer wajib hanya saat metode transfer DAN belum ada bukti
        // tersimpan. Versi asli mewajibkannya di setiap update, termasuk tunai.
        if ($request->payment_method === 'Transfer Bank'
            && !($existing && $existing->getMedia('bukti_pembayaran')->isNotEmpty())) {
            $aturan['proof_of_payment'] = 'required|array|min:1|max:3';
        }

        if (in_array($kategori, self::PERLU_SISWA, true)) {
            // Tabel students sudah dilebur ke santris.
            $aturan['student_id'] = 'required|exists:santris,id';
        }

        if ($kategori === 'tagihan') {
            $aturan['invoice_id'] = 'required|exists:invoices,id';
        }

        // Infaq hanya untuk santri Rumah Quran. Daftar di layar memang sudah
        // tersaring, tapi id-nya datang dari browser.
        if ($kategori === 'infaq_arahan' && ! $existing) {
            $aturan['student_id'] = ['required', 'exists:santris,id', function ($attr, $value, $fail) {
                if (! Siswa::whereKey($value)->arahan()->exists()) {
                    $fail('Santri ini bukan santri Rumah Quran. Siswa TK membayar lewat Pembayaran Tagihan.');
                }
            }];
        }

        $aturan['nama_pihak'] = in_array($kategori, self::PERLU_NAMA, true)
            ? 'required|string|max:255'
            : 'nullable|string|max:255';

        return $request->validate($aturan, [
            'student_id.required'       => $kategori === 'infaq_arahan' ? 'Santri wajib dipilih.' : 'Siswa wajib dipilih.',
            'invoice_id.required'       => 'Tagihan wajib dipilih.',
            'nama_pihak.required'       => $kategori === 'uang_masuk'
                                            ? 'Nama calon siswa wajib diisi.'
                                            : 'Nama donatur wajib diisi.',
            'account_id.required'       => 'Rekening tujuan wajib dipilih.',
            'proof_of_payment.required' => 'Bukti transfer wajib diunggah.',
        ]);
    }

    /**
     * Siswa yang boleh dipilih di form, sesuai kategori yang sedang dibuka.
     * Tagihan hanya untuk TK, infaq hanya untuk Rumah Quran — memisahkannya di
     * sini membuat salah pilih lembaga tidak mungkin terjadi dari layar.
     */
    private function daftarSiswa(string $kategori)
    {
        $query = Siswa::aktif()->orderBy('name');

        return match ($kategori) {
            'tagihan'      => $query->tk()->get(['id', 'nis', 'name']),
            'infaq_arahan' => $query->arahan()->get(['id', 'nis', 'name']),
            default        => [],
        };
    }

    private function simpanBukti(Request $request, Pembayaran $pembayaran): void
    {
        if (!$request->hasFile('proof_of_payment')) {
            return;
        }

        foreach ($request->file('proof_of_payment') as $file) {
            $pembayaran->addMedia($file)
                ->usingFileName(time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension())
                ->toMediaCollection('bukti_pembayaran');
        }
    }
}