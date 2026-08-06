<?php

namespace App\Http\Controllers\Keuangan\Outbound;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\MasterData\RkasTemplate;
use App\Models\Keuangan\Outbound\RkasDetail;
use App\Models\Keuangan\Outbound\RkasHeader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RkasController extends Controller
{
    /** stage dari form → nilai enum di kolom status */
    private const STAGE = [
        'diperiksa' => 'Diperiksa',
        'final'     => 'Final',
        'closed'    => 'Closed',
    ];

    /** stage → permission yang wajib dimiliki */
    private const IZIN = [
        'diperiksa' => 'rkas.approve_diperiksa',
        'final'     => 'rkas.approve_final',
        'closed'    => 'rkas.approve_tutup_batch',
    ];

    /** stage → status yang harus dimiliki header sebelum transisi */
    private const SYARAT = [
        'diperiksa' => 'Draft',
        'final'     => 'Diperiksa',
        'closed'    => 'Final',
    ];

    public function index(Request $request)
    {
        $query = RkasHeader::with([
            'dibuatOleh:id,name',
            'diperiksaOleh:id,name',
            'difinalOleh:id,name',
            'ditutupOleh:id,name',
        ]);

        $query->when($request->filled('search'), fn ($q) =>
            $q->where('tahun_ajaran', 'like', '%' . $request->search . '%'));

        $query->when($request->filled('statusFilter'), fn ($q) =>
            $q->where('status', $request->statusFilter));

        $sortField     = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        $allowedSorts = ['tahun_ajaran', 'budget_limit', 'total_allocated', 'status', 'created_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        $headers = $query->paginate(10)->withQueryString();

        $details = $request->filled('header_id')
            ? RkasDetail::where('rkas_header_id', $request->header_id)
                ->orderBy('kode_kegiatan')
                ->get()
            : [];

        return Inertia::render('Keuangan/Pengeluaran/DataRKAS', [
            'headers'          => $headers,
            'details'          => $details,
            'selectedHeaderId' => $request->header_id,
            'filters'          => $request->only(['search', 'statusFilter', 'sort_field', 'sort_direction', 'header_id']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tahun_ajaran' => ['required', 'string', 'max:255',
                Rule::unique('rkas_headers', 'tahun_ajaran')->whereNull('deleted_at')],
            'budget_limit' => 'required|numeric|min:1',
        ], [
            'tahun_ajaran.required' => 'Tahun ajaran wajib diisi.',
            'tahun_ajaran.unique'   => 'RKAS untuk tahun ajaran ini sudah ada.',
            'budget_limit.required' => 'Pagu anggaran wajib diisi.',
            'budget_limit.numeric'  => 'Pagu anggaran harus berupa angka.',
        ]);

        RkasHeader::create([
            'tahun_ajaran'    => $request->tahun_ajaran,
            'budget_limit'    => $request->budget_limit,
            'total_allocated' => 0,
            'status'          => 'Draft',
            'created_by'      => Auth::id(),
        ]);

        return redirect()->back()->with('success', "RKAS {$request->tahun_ajaran} berhasil dibuat.");
    }

    public function update(Request $request, RkasHeader $header)
    {
        if ($header->status !== 'Draft') {
            return redirect()->back()->withErrors(['status' => 'RKAS hanya bisa diubah saat berstatus Draft.']);
        }

        $request->validate([
            'tahun_ajaran' => ['required', 'string', 'max:255',
                Rule::unique('rkas_headers', 'tahun_ajaran')->ignore($header->id)->whereNull('deleted_at')],
            'budget_limit' => 'required|numeric|min:1',
        ]);

        $header->update($request->only('tahun_ajaran', 'budget_limit'));

        return redirect()->back()->with('success', 'RKAS berhasil diperbarui.');
    }

    public function destroy(RkasHeader $header)
    {
        if ($header->status !== 'Draft') {
            return redirect()->back()->withErrors(['status' => 'RKAS hanya bisa dihapus saat berstatus Draft.']);
        }

        // Model ini pakai SoftDeletes, jadi ON DELETE CASCADE di database TIDAK
        // ikut terpicu — versi asli meninggalkan rkas_details jadi yatim.
        DB::transaction(function () use ($header) {
            $header->details()->delete();
            $header->delete();
        });

        return redirect()->back()->with('success', "RKAS {$header->tahun_ajaran} beserta rinciannya berhasil dihapus.");
    }

    public function generateStruktur(RkasHeader $header)
    {
        if ($header->status !== 'Draft') {
            return redirect()->back()->withErrors(['generate' => 'Struktur hanya bisa dibuat saat RKAS berstatus Draft.']);
        }

        if ($header->details()->exists()) {
            return redirect()->back()->withErrors(['generate' => 'Struktur sudah pernah dibuat.']);
        }

        $templates = RkasTemplate::orderBy('level')->orderBy('id')->get();

        if ($templates->isEmpty()) {
            return redirect()->back()->withErrors(['generate' => 'Belum ada Template RKAS. Buat dulu di Master Data.']);
        }

        DB::transaction(function () use ($templates, $header) {
            $peta = [];

            foreach ($templates as $t) {
                $detail = RkasDetail::create([
                    'rkas_header_id'    => $header->id,
                    'rkas_template_id'  => $t->id,
                    'parent_id'         => $t->parent_id ? ($peta[$t->parent_id] ?? null) : null,
                    'kode_kegiatan'     => $t->kode_judul,
                    'nama_kegiatan'     => $t->nama_judul,
                    'pagu_anggaran'     => 0,
                    'nominal_realisasi' => 0,
                    'is_parent'         => true,
                ]);

                $peta[$t->id] = $detail->id;
            }
        });

        return redirect()->back()->with('success', 'Kerangka struktur RKAS berhasil dibuat.');
    }

    public function storeKegiatan(Request $request, RkasHeader $header)
    {
        if ($header->status !== 'Draft') {
            return redirect()->back()->withErrors(['status' => 'Kegiatan hanya bisa ditambah saat RKAS berstatus Draft.']);
        }

        $request->validate($this->aturanKegiatan(true), $this->pesanKegiatan());

        DB::transaction(function () use ($request, $header) {
            $parent = RkasDetail::where('rkas_header_id', $header->id)
                ->findOrFail($request->parent_id);

            // count() bikin kode bentrok setelah ada kegiatan yang dihapus:
            // 1.1 dan 1.2 ada, 1.1 dihapus → count jadi 1 → kode baru 1.2 (duplikat).
            // Ambil sufiks terbesar yang pernah dipakai.
            $sufiksTerbesar = $parent->children()
                ->pluck('kode_kegiatan')
                ->map(fn ($kode) => (int) ltrim(strrchr((string) $kode, '.') ?: '.0', '.'))
                ->max() ?? 0;

            RkasDetail::create([
                'rkas_header_id' => $header->id,
                'parent_id'      => $parent->id,
                'kode_kegiatan'  => $parent->kode_kegiatan . '.' . ($sufiksTerbesar + 1),
                'nama_kegiatan'  => $request->nama_kegiatan,
                'volume_1'       => $request->volume_1,
                'satuan_1'       => $request->satuan_1,
                'volume_2'       => $request->volume_2,
                'satuan_2'       => $request->satuan_2,
                'harga_satuan'   => $request->harga_satuan,
                'pagu_anggaran'  => $this->hitungPagu($request),
                'is_parent'      => false,
            ]);

            $this->bubbleUp($parent->id);
            $this->hitungTotalHeader($header);
        });

        return redirect()->back()->with('success', 'Kegiatan berhasil ditambahkan.');
    }

    public function updateKegiatan(Request $request, RkasDetail $detail)
    {
        if ($detail->header->status !== 'Draft') {
            return redirect()->back()->withErrors(['status' => 'Kegiatan hanya bisa diubah saat RKAS berstatus Draft.']);
        }

        $request->validate($this->aturanKegiatan(false), $this->pesanKegiatan());

        DB::transaction(function () use ($request, $detail) {
            $detail->update([
                'nama_kegiatan' => $request->nama_kegiatan,
                'volume_1'      => $request->volume_1,
                'satuan_1'      => $request->satuan_1,
                'volume_2'      => $request->volume_2,
                'satuan_2'      => $request->satuan_2,
                'harga_satuan'  => $request->harga_satuan,
                'pagu_anggaran' => $this->hitungPagu($request),
            ]);

            if ($detail->parent_id) {
                $this->bubbleUp($detail->parent_id);
            }

            $this->hitungTotalHeader($detail->header);
        });

        return redirect()->back()->with('success', 'Kegiatan berhasil diperbarui.');
    }

    public function destroyKegiatan(RkasDetail $detail)
    {
        if ($detail->header->status !== 'Draft') {
            return redirect()->back()->withErrors(['status' => 'Kegiatan hanya bisa dihapus saat RKAS berstatus Draft.']);
        }

        if ($detail->is_parent) {
            return redirect()->back()->withErrors(['status' => 'Baris struktur tidak dapat dihapus.']);
        }

        DB::transaction(function () use ($detail) {
            $parentId = $detail->parent_id;
            $header   = $detail->header;

            $detail->delete();

            if ($parentId) {
                $this->bubbleUp($parentId);
            }

            $this->hitungTotalHeader($header);
        });

        return redirect()->back()->with('success', 'Kegiatan berhasil dihapus.');
    }

    public function approve(Request $request, RkasHeader $header)
    {
        $request->validate(['stage' => 'required|in:diperiksa,final,closed']);

        $stage = $request->stage;

        // BUG ASLI: pengecekan membandingkan 'Diperiksa'/'Final'/'Closed'
        // (huruf besar) padahal validasi hanya meloloskan huruf kecil, jadi
        // gerbang permission tidak pernah aktif. Route-nya sendiri cuma
        // dijaga permission:rkas.lihat.
        abort_unless(Auth::user()->can(self::IZIN[$stage]), 403,
            'Anda tidak memiliki akses untuk tahap ini.');

        if ($header->status !== self::SYARAT[$stage]) {
            return redirect()->back()->withErrors([
                'status' => 'Transisi tidak valid. RKAS harus berstatus ' . self::SYARAT[$stage] . ' terlebih dahulu.',
            ]);
        }

        if ($stage === 'diperiksa' && !$header->details()->exists()) {
            return redirect()->back()->withErrors([
                'status' => 'RKAS belum memiliki rincian anggaran.',
            ]);
        }

        $data = ['status' => self::STAGE[$stage]];

        match ($stage) {
            'diperiksa' => $data += ['diperiksa_by' => Auth::id(), 'diperiksa_at' => now()],
            'final'     => $data += ['final_by' => Auth::id(), 'final_at' => now()],
            'closed'    => $data += ['closed_by' => Auth::id(), 'closed_at' => now()],
        };

        $header->update($data);

        return redirect()->back()->with('success', 'Status RKAS menjadi ' . self::STAGE[$stage] . '.');
    }

    private function aturanKegiatan(bool $butuhParent): array
    {
        // Satuan menolak input yang isinya angka saja. Tanpa ini, angka yang
        // salah masuk ke kolom satuan hanya jadi label dan tidak ikut dikalikan —
        // pagu terlihat "salah hitung" padahal nilainya memang tidak dipakai.
        $satuan = ['string', 'max:50', 'not_regex:/^\s*\d+([.,]\d+)?\s*$/'];

        return array_filter([
            'parent_id'     => $butuhParent ? ['required', 'exists:rkas_details,id'] : null,
            'nama_kegiatan' => ['required', 'string', 'max:255'],
            'volume_1'      => ['required', 'integer', 'min:1'],
            'satuan_1'      => array_merge(['required'], $satuan),
            'volume_2'      => ['nullable', 'integer', 'min:1'],
            'satuan_2'      => array_merge(['nullable'], $satuan),
            'harga_satuan'  => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function pesanKegiatan(): array
    {
        return [
            'nama_kegiatan.required' => 'Nama/uraian kegiatan wajib diisi.',
            'volume_1.required'      => 'Volume 1 wajib diisi.',
            'volume_1.min'           => 'Volume 1 minimal 1.',
            'satuan_1.required'      => 'Satuan 1 wajib diisi.',
            'satuan_1.not_regex'     => 'Satuan diisi keterangan (Keg, Orang, Bulan), bukan angka. Angka pengali masuk ke kolom Volume.',
            'satuan_2.not_regex'     => 'Satuan diisi keterangan (Keg, Orang, Bulan), bukan angka. Angka pengali masuk ke kolom Volume.',
            'harga_satuan.required'  => 'Harga satuan wajib diisi.',
            'harga_satuan.numeric'   => 'Harga satuan harus berupa angka.',
        ];
    }

    /** Pagu = volume_1 × volume_2 (default 1) × harga_satuan. */
    private function hitungPagu(Request $request): float
    {
        $vol2 = (int) $request->volume_2;

        return (int) $request->volume_1 * ($vol2 > 0 ? $vol2 : 1) * (float) $request->harga_satuan;
    }

    /** Jumlahkan pagu anak ke induknya, naik sampai level teratas. */
    private function bubbleUp($detailId): void
    {
        $detail = RkasDetail::find($detailId);

        if (!$detail || !$detail->is_parent) {
            return;
        }

        $detail->update(['pagu_anggaran' => $detail->children()->sum('pagu_anggaran')]);

        if ($detail->parent_id) {
            $this->bubbleUp($detail->parent_id);
        }
    }

    private function hitungTotalHeader(RkasHeader $header): void
    {
        $header->update([
            'total_allocated' => $header->details()->whereNull('parent_id')->sum('pagu_anggaran'),
        ]);
    }
}