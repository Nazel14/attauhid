<?php

namespace App\Http\Controllers\Keuangan\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Kelas as KelasSantri;
use App\Models\Keuangan\MasterData\Siswa;
use App\Models\Keuangan\MasterData\Tarif;
use App\Models\Keuangan\MasterData\TarifTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TarifController extends Controller
{
    public function index(Request $request)
    {
        $query = Tarif::query()->with('targets');

        $query->when($request->search, function ($q, $search) {
            $q->where('billing_name', 'like', "%{$search}%");
        });

        $query->when($request->periodFilter, fn ($q, $period) => $q->where('period', $period));

        $query->when($request->statusFilter, function ($q, $status) {
            $q->where('is_active', $status === 'Aktif' ? 1 : 0);
        });

        // Filter baru: tampilkan tarif yang menargetkan kelas tertentu.
        $query->when($request->kelasFilter, function ($q, $kelasId) {
            $q->whereHas('targets', fn ($t) => $t
                ->where('target_type', 'kelas')
                ->where('target_value', $kelasId));
        });

        $sortField     = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        // target_student_type dibuang — sekarang target ada di tabel terpisah
        // dan tidak bisa di-sort lewat kolom.
        $allowedSorts = ['billing_name', 'nominal', 'period', 'is_active', 'created_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        $tarif = $query->paginate(10)->withQueryString();

        $tarif->through(fn (Tarif $t) => [
            'id'             => $t->id,
            'billing_name'   => $t->billing_name,
            'nominal'        => $t->nominal,
            'period'         => $t->period,
            'is_active'      => $t->is_active,
            'is_donation'    => $t->is_donation,
            'target_summary' => $t->targetSummary(),
            'targets'        => $t->targets->map(fn ($r) => [
                'type'  => $r->target_type,
                'value' => $r->target_value,
            ])->values(),
        ]);

        return Inertia::render('Keuangan/MasterData/DataTarif', [
            'tarif'      => $tarif,
            'dimensions' => $this->dimensionOptions(),
            'filters'    => $request->only([
                'search', 'periodFilter', 'statusFilter', 'kelasFilter',
                'sort_field', 'sort_direction',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            $this->rules(),
            $this->messages()
        );

        DB::transaction(function () use ($validated) {
            $tarif = Tarif::create(Arr::except($validated, 'targets'));
            $this->syncTargets($tarif, $validated['targets'] ?? []);
        });

        return redirect()
            ->route('keuangan.master-data.tarif')
            ->with('success', 'Tarif berhasil ditambahkan.');
    }

    public function update(Request $request, Tarif $tarif)
    {
        $validated = $request->validate(
            $this->rules(),
            $this->messages()
        );

        DB::transaction(function () use ($tarif, $validated) {
            $tarif->update(Arr::except($validated, 'targets'));
            $this->syncTargets($tarif, $validated['targets'] ?? []);
        });

        return redirect()->back()->with('success', 'Tarif berhasil diperbarui.');
    }

    public function destroy(Tarif $tarif)
    {
        if ($tarif->itemTagihan()->exists()) {
            return redirect()->back()->withErrors([
                'delete_error' => 'Gagal menghapus! Tarif ini sudah digunakan dalam tagihan siswa yang aktif.',
            ]);
        }

        // Aturan target sengaja tidak dihapus: Tarif pakai SoftDeletes,
        // jadi kalau nanti di-restore aturannya masih utuh.
        $tarif->delete();

        return redirect()->back()->with('success', 'Tarif berhasil dihapus.');
    }

    /* ------------------------------------------------------------------ */

    private function rules(): array
    {
        return [
            'billing_name'      => 'required|string|max:255',
            'nominal'           => 'required|numeric|min:0',
            'period'            => 'required|in:Bulanan,Semester,Tahunan,Sekali Bayar',
            'is_active'         => 'boolean',
            'is_donation'       => 'boolean',

            'targets'           => 'array',
            'targets.*.type'    => ['required', Rule::in(array_keys(TarifTarget::DIMENSIONS))],
            'targets.*.values'  => 'required|array|min:1',
            'targets.*.values.*' => 'required|string|max:100',
        ];
    }

    private function messages(): array
    {
        return [
            'billing_name.required'    => 'Nama tarif wajib diisi.',
            'nominal.required'         => 'Nominal tarif wajib diisi.',
            'nominal.numeric'          => 'Nominal harus berupa angka.',
            'targets.*.type.in'        => 'Jenis kriteria target tidak dikenali.',
            'targets.*.values.required' => 'Setiap kriteria harus punya minimal satu nilai.',
        ];
    }

    /**
     * Tulis ulang seluruh aturan target. Sengaja delete-then-insert:
     * jumlahnya sedikit dan ini menghindari logika diff yang rawan.
     */
    private function syncTargets(Tarif $tarif, array $targets): void
    {
        $tarif->targets()->delete();

        // Tarif donasi tidak menargetkan santri manapun.
        if ($tarif->is_donation) {
            $tarif->setRelation('targets', collect());
            return;
        }

        $rows = [];

        foreach ($targets as $target) {
            foreach (array_unique($target['values']) as $value) {
                $rows[] = [
                    'tariff_id'    => $tarif->id,
                    'target_type'  => $target['type'],
                    'target_value' => (string) $value,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }
        }

        if ($rows) {
            TarifTarget::insert($rows);
        }

        $tarif->load('targets');
    }

    /** Pilihan nilai untuk tiap dimensi, dikirim ke builder kriteria di UI. */
    private function dimensionOptions(): array
    {
        $angkatan = Siswa::aktif()
            ->whereNotNull('angkatan')
            ->distinct()
            ->orderByDesc('angkatan')
            ->pluck('angkatan');

        return [
            'kelas' => [
                'label'   => TarifTarget::LABELS['kelas'],
                'options' => KelasSantri::where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn ($k) => ['value' => (string) $k->id, 'label' => $k->name]),
            ],
            'angkatan' => [
                'label'   => TarifTarget::LABELS['angkatan'],
                'options' => $angkatan->map(fn ($a) => ['value' => (string) $a, 'label' => (string) $a]),
            ],
            'student_type' => [
                'label'   => TarifTarget::LABELS['student_type'],
                'options' => collect(['Reguler', 'Fullday'])
                    ->map(fn ($v) => ['value' => $v, 'label' => $v]),
            ],
            'gender' => [
                'label'   => TarifTarget::LABELS['gender'],
                'options' => collect(['Laki-laki', 'Perempuan'])
                    ->map(fn ($v) => ['value' => $v, 'label' => $v]),
            ],
        ];
    }
}