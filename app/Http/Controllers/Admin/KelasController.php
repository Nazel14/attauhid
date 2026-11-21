<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Guru; 
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\KelasImport;
use App\Imports\KelasBulkUpdateImport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Inertia\Inertia;

class KelasController extends Controller
{
     use LogsActivity;
    /**
     * Menampilkan daftar semua Kelas/Halaqah.
     */
    public function index()
    {
        // withCount santris: dipakai menampilkan "terisi / kapasitas" di tabel.
        $kelas = Kelas::with('guru')->withCount('santris')->latest()->get();
        $gurus = Guru::orderBy('name')->get();
        $genderRestrictions = Kelas::GENDER_RESTRICTIONS;

        // Tahun ajaran aktif (untuk dropdown di modal create/edit)
        $tahunAjaranAktif = \App\Models\TahunAjaran::getAktif();

        return Inertia::render('Admin/Kelas/Index', [
            'kelas' => $kelas,
            'gurus' => $gurus,
            'genderRestrictions' => $genderRestrictions,
            'tahunAjaranAktif' => $tahunAjaranAktif,
            // TK ditagih lewat gelombang tagihan, Rumah Quran membayar infaq
            // sukarela — pilihannya dibaca dari model supaya labelnya sama
            // dengan panel keuangan.
            'lembagaOptions' => Kelas::LEMBAGA,
        ]);
    }
    /**
     * Menyimpan Kelas baru ke database.
     */
    public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'tahun_ajaran' => 'required|string|max:10',
        'guru_id' => 'required|exists:gurus,id',
        'gender_restriction' => 'required|in:ikhwan,akhwat,campuran',
        // Tiga kolom di bawah dipakai bersama modul keuangan.
        'tingkat' => 'nullable|string|max:100',
        'lembaga' => 'required|in:' . implode(',', array_keys(Kelas::LEMBAGA)),
        'kapasitas' => 'required|integer|min:1|max:100',
        'is_active' => 'boolean',
    ], [
        'lembaga.required' => 'Lembaga wajib dipilih.',
        'lembaga.in' => 'Lembaga tidak dikenali.',
        'kapasitas.required' => 'Kapasitas kelas wajib diisi.',
        'kapasitas.min' => 'Kapasitas minimal 1 santri.',
    ]);

    $request->validate([
        'name' => [
            'required', 
            'string', 
            'max:255',
            Rule::unique('kelas')->where(function ($query) use ($validated) {
                return $query->where('name', $validated['name'])
                            ->where('tahun_ajaran', $validated['tahun_ajaran']);
            }),
        ],
    ], [
        'name.unique' => 'Kelas dengan nama dan Tahun Ajaran ini sudah ada. Silakan periksa kembali.'
    ]);

    $validated['name'] = $this->smartCapitalize($validated['name']);
    $validated['is_active'] = $request->boolean('is_active', true);

    // ✅ SIMPAN HASIL CREATE KE VARIABLE
    $kelas = Kelas::create($validated);
    
    // ✅ LOG ACTIVITY SETELAH CREATE
    $this->logCreate('kelas', $kelas->name, $kelas);

    return redirect()->route('admin.kelas.index')
        ->with('success', 'Kelas/Halaqah berhasil ditambahkan.');
}

    /**
     * Memperbarui data Kelas.
     */
    public function update(Request $request, Kelas $kela)
    {
          $oldValues = $kela->only(['name', 'tahun_ajaran', 'guru_id', 'gender_restriction', 'tingkat', 'lembaga', 'kapasitas', 'is_active']);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tahun_ajaran' => 'required|string|max:10',
            'guru_id' => 'required|exists:gurus,id',
            'gender_restriction' => 'required|in:ikhwan,akhwat,campuran',
            'tingkat' => 'nullable|string|max:100',
            'lembaga' => 'required|in:' . implode(',', array_keys(Kelas::LEMBAGA)),
            'kapasitas' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean',
        ], [
            'lembaga.required' => 'Lembaga wajib dipilih.',
            'lembaga.in' => 'Lembaga tidak dikenali.',
            'kapasitas.required' => 'Kapasitas kelas wajib diisi.',
            'kapasitas.min' => 'Kapasitas minimal 1 santri.',
        ]);
        
        $request->validate([
            'name' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('kelas')->ignore($kela->id)->where(function ($query) use ($validated) {
                    return $query->where('name', $validated['name'])
                                ->where('tahun_ajaran', $validated['tahun_ajaran']);
                }),
            ],
        ], [
            'name.unique' => 'Kelas dengan nama dan Tahun Ajaran ini sudah ada. Silakan periksa kembali.'
        ]);

        $validated['name'] = $this->smartCapitalize($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);

        // Kapasitas tidak boleh diturunkan di bawah jumlah santri yang
        // sudah masuk — angkanya jadi menyesatkan di panel keuangan.
        $terisi = $kela->santris()->count();
        if ($validated['kapasitas'] < $terisi) {
            return redirect()->back()->withInput()->withErrors([
                'kapasitas' => "Kelas ini sudah berisi {$terisi} santri. Kapasitas tidak boleh kurang dari itu.",
            ]);
        }

        $kela->update($validated);
        $this->logUpdate('kelas', $kela->name, $kela, $oldValues);

        return redirect()->route('admin.kelas.index')
            ->with('success', 'Data Kelas/Halaqah berhasil diperbarui.');
    }   

    /**
     * Menghapus Kelas.
     */
    public function destroy(Kelas $kela)
    {
        if ($alasan = $this->alasanTidakBisaDihapus($kela)) {
            return redirect()->route('admin.kelas.index')->with('error', $alasan);
        }

        $name = $kela->name;
        $oldValues = $kela->toArray();
        $kela->delete();
        $this->logDelete('kelas', $name, $oldValues);

        return redirect()->route('admin.kelas.index')
            ->with('success', 'Kelas/Halaqah berhasil dihapus.');
    }

    /**
     * Alasan sebuah kelas TIDAK boleh dihapus, atau null bila aman.
     *
     * Di database, menghapus kelas tidak berhenti di baris kelas saja:
     *   santris.kelas_id               ON DELETE CASCADE  → santri ikut terhapus
     *   santri_kelas_history.kelas_id  ON DELETE CASCADE  → riwayat kelas ikut terhapus
     *   setorans.kelas_id              ON DELETE SET NULL → setoran kehilangan kelasnya
     *
     * Santri yang sudah DIARSIPKAN wajib ikut dihitung (withTrashed). Hitungan
     * biasa melewatkan mereka, padahal CASCADE tetap menghapusnya permanen —
     * itu yang membuat kelas Ummu Shofi sempat terhapus beserta riwayatnya.
     *
     * Dipakai destroy() dan bulkDestroy() supaya dua jalur hapus tidak
     * pernah punya aturan berbeda.
     */
    private function alasanTidakBisaDihapus(Kelas $kelas): ?string
    {
        $jumlahSantri = $kelas->santris()->withTrashed()->count();

        if ($jumlahSantri > 0) {
            return "Kelas \"{$kelas->name}\" masih tercatat untuk {$jumlahSantri} santri (termasuk yang sudah diarsipkan). Pindahkan santrinya dulu, atau nonaktifkan kelas ini saja.";
        }

        $punyaRiwayat = DB::table('santri_kelas_history')->where('kelas_id', $kelas->id)->exists()
            || DB::table('setorans')->where('kelas_id', $kelas->id)->exists();

        if ($punyaRiwayat) {
            return "Kelas \"{$kelas->name}\" sudah punya riwayat kelas atau setoran, jadi tidak bisa dihapus. Nonaktifkan saja lewat Edit: hilangkan centang \"Kelas Aktif\".";
        }

        return null;
    }

    /**
     * Bulk delete kelas (hapus banyak sekaligus)
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'kelas_ids' => 'required|array|min:1',
            'kelas_ids.*' => 'exists:kelas,id',
        ], [
            'kelas_ids.required' => 'Tidak ada kelas yang dipilih.',
            'kelas_ids.min' => 'Minimal pilih 1 kelas untuk dihapus.',
        ]);

        try {
            // Aturan yang sama persis dengan hapus satuan. Satu kelas yang
            // tidak boleh dihapus membatalkan seluruh pilihan — hapus massal
            // yang separuh jalan lebih membingungkan daripada ditolak utuh.
            $ditolak = Kelas::whereIn('id', $request->kelas_ids)->get()
                ->filter(fn ($k) => $this->alasanTidakBisaDihapus($k) !== null)
                ->pluck('name')
                ->toArray();

            if (!empty($ditolak)) {
                return redirect()->route('admin.kelas.index')
                    ->with('error', 'Tidak ada yang dihapus. Kelas berikut masih punya santri (termasuk yang diarsipkan), riwayat kelas, atau setoran: <strong>' . e(implode(', ', $ditolak)) . '</strong>. Hilangkan centangnya dari pilihan, atau nonaktifkan kelas tersebut.');
            }

            $deletedCount = count($request->kelas_ids);
            $kelasNames = Kelas::whereIn('id', $request->kelas_ids)->pluck('name')->toArray();
            DB::transaction(function () use ($request) {
                Kelas::whereIn('id', $request->kelas_ids)->delete();
            });

            $this->logActivity(
                'delete',
                'kelas',
                "Bulk delete {$deletedCount} kelas: " . implode(', ', $kelasNames),
                null,
                ['ids' => $request->kelas_ids, 'total' => $deletedCount],
                null
            );

            $count = count($request->kelas_ids);
            return redirect()->route('admin.kelas.index')
                ->with('success', "Berhasil menghapus {$count} kelas!");
                
        } catch (\Exception $e) {
            \Log::error("Error bulk deleting Kelas: " . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    /**
     * Download template Excel untuk import kelas
     */
        public function downloadTemplate()
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $guruList = Guru::orderBy('id')->get();

    // Tahun ajaran aktif — kelas hanya boleh ikut TA aktif (1 opsi)
    $taAktif = \App\Models\TahunAjaran::getAktif();
    $taNama  = $taAktif->nama ?? '2024/2025';

    // tingkat & kapasitas ditambahkan: tabel kelas kini dipakai bersama
    // modul keuangan. is_active sengaja tidak diikutkan — boolean di Excel
    // rawan salah ketik, dan penonaktifan kelas lebih aman lewat form.
    $headers = ['nama_kelas', 'tahun_ajaran', 'no_guru', 'no_gender', 'tingkat', 'kapasitas'];
    $sheet->fromArray($headers, NULL, 'A1');

    $sheet->getStyle('A1:F1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1F2937']],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'F3F4F6']
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'bottom' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                'color' => ['rgb' => 'D1D5DB']
            ]
        ]
    ]);

    // Contoh data (tahun_ajaran = TA aktif)
    $exampleData = [
        ['Kelas 7A (Contoh)', $taNama, '1', '1', 'VII', '20'],  // campuran
        ['Kelas 8B (Contoh)', $taNama, '1', '2', 'VIII', '25'], // ikhwan
    ];
    $sheet->fromArray($exampleData, NULL, 'A2');

    // Baris contoh dibuat lebih gelap supaya jelas "ini hanya contoh, hapus"
    $sheet->getStyle('A2:F3')->applyFromArray([
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'D1D5DB']   // abu-abu lebih gelap
        ],
        'font' => ['size' => 9, 'color' => ['rgb' => '4B5563'], 'italic' => true],
        'borders' => [
            'outline' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => '9CA3AF']
            ]
        ]
    ]);

    // Data bergeser 2 kolom: tabel bantuan kini mulai dari H, bukan F.
    $sheet->getColumnDimension('A')->setWidth(25);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setWidth(12);
    $sheet->getColumnDimension('D')->setWidth(15);
    $sheet->getColumnDimension('E')->setWidth(12);
    $sheet->getColumnDimension('F')->setWidth(12);
    $sheet->getColumnDimension('G')->setWidth(2);
    $sheet->getColumnDimension('H')->setWidth(32);
    $sheet->getColumnDimension('I')->setWidth(2);
    $sheet->getColumnDimension('J')->setWidth(8);
    $sheet->getColumnDimension('K')->setWidth(22);
    $sheet->getColumnDimension('L')->setWidth(2);
    $sheet->getColumnDimension('M')->setWidth(8);
    $sheet->getColumnDimension('N')->setWidth(15);
    $sheet->getColumnDimension('O')->setWidth(2);
    $sheet->getColumnDimension('P')->setWidth(10);
    $sheet->getColumnDimension('Q')->setWidth(22);

    // Petunjuk
    $sheet->setCellValue('H1', 'PETUNJUK');
    $sheet->setCellValue('H2', 'Nama Kelas: Nama lengkap kelas');
    $sheet->setCellValue('H3', 'Tahun Ajaran: Pakai TA AKTIF (lihat tabel kanan →)');
    $sheet->setCellValue('H4', 'No Guru: Lihat tabel guru →');
    $sheet->setCellValue('H5', 'No Gender: Lihat tabel gender →');
    $sheet->setCellValue('H6', 'Tingkat: Opsional, contoh VII / VIII');
    $sheet->setCellValue('H7', 'Kapasitas: Daya tampung santri (1-100)');
    $sheet->setCellValue('H8', '');
    $sheet->setCellValue('H9', 'CATATAN PENTING:');
    $sheet->setCellValue('H10', '• Baris 2-3 hanya contoh');
    $sheet->setCellValue('H11', '• Jangan ubah header');
    $sheet->setCellValue('H12', '• No Gender wajib diisi (1/2/3)');
    $sheet->setCellValue('H13', '• Kapasitas kosong → otomatis 20');

    $sheet->getStyle('H1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '374151']],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'F9FAFB']
        ],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        'borders' => [
            'bottom' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'E5E7EB']
            ]
        ]
    ]);

    $sheet->getStyle('H2:H13')->applyFromArray([
        'font' => ['size' => 8, 'color' => ['rgb' => '6B7280']],
        'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]
    ]);

    $sheet->getStyle('H9')->applyFromArray([
        'font' => ['bold' => true, 'size' => 8, 'color' => ['rgb' => '374151']],
    ]);

    $sheet->getStyle('H1:H13')->applyFromArray([
        'borders' => [
            'outline' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'E5E7EB']
            ]
        ]
    ]);

    // Tabel Guru
    $sheet->setCellValue('J1', 'GURU');
    $sheet->mergeCells('J1:K1');
    $sheet->getStyle('J1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '374151']],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'F9FAFB']
        ],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        'borders' => [
            'bottom' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'E5E7EB']
            ]
        ]
    ]);

    $sheet->setCellValue('J2', 'No_Urut');
    $sheet->setCellValue('K2', 'Nama Guru');
    $sheet->getStyle('J2:K2')->applyFromArray([
        'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '6B7280']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ]);

    $currentRow = 3;
    foreach ($guruList as $index => $guru) {
        $sheet->setCellValue('J' . $currentRow, $index + 1);
        $sheet->setCellValue('K' . $currentRow, $guru->name);
        $sheet->getStyle('J' . $currentRow . ':K' . $currentRow)->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '374151']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);
        $currentRow++;
    }

    $lastRowGuru = $currentRow - 1;
    $sheet->getStyle('J1:K' . $lastRowGuru)->applyFromArray([
        'borders' => [
            'outline' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'E5E7EB']
            ]
        ]
    ]);

    // Tabel Gender Kelas
    $sheet->setCellValue('M1', 'GENDER KELAS');
    $sheet->mergeCells('M1:N1');
    $sheet->getStyle('M1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '374151']],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'F9FAFB']
        ],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        'borders' => [
            'bottom' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'E5E7EB']
            ]
        ]
    ]);

    $sheet->setCellValue('M2', 'No');
    $sheet->setCellValue('N2', 'Jenis Gender');
    $sheet->getStyle('M2:N2')->applyFromArray([
        'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '6B7280']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ]);

    // Data gender dengan urutan: campuran, ikhwan, akhwat
    $genderData = [
        ['1', 'Campuran'],
        ['2', 'Ikhwan'],
        ['3', 'Akhwat']
    ];

    $genderRow = 3;
    foreach ($genderData as $data) {
        $sheet->setCellValue('M' . $genderRow, $data[0]);
        $sheet->setCellValue('N' . $genderRow, $data[1]);
        $sheet->getStyle('M' . $genderRow . ':N' . $genderRow)->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '374151']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);
        $genderRow++;
    }

    $sheet->getStyle('M1:N5')->applyFromArray([
        'borders' => [
            'outline' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'E5E7EB']
            ]
        ]
    ]);

    // ── Tabel Tahun Ajaran Aktif (hanya 1 opsi) ──
    $sheet->setCellValue('P1', 'TAHUN AJARAN');
    $sheet->mergeCells('P1:Q1');
    $sheet->getStyle('P1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '374151']],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'F9FAFB']
        ],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        'borders' => [
            'bottom' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'E5E7EB']
            ]
        ]
    ]);

    $sheet->setCellValue('P2', 'Status');
    $sheet->setCellValue('Q2', 'Tahun Ajaran');
    $sheet->getStyle('P2:Q2')->applyFromArray([
        'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '6B7280']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ]);

    $sheet->setCellValue('P3', 'AKTIF');
    $sheet->setCellValue('Q3', $taNama);
    $sheet->getStyle('P3:Q3')->applyFromArray([
        'font' => ['size' => 9, 'color' => ['rgb' => '374151']],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
        ]
    ]);
    // tonjolkan TA aktif (hijau bold)
    $sheet->getStyle('P3')->getFont()->setBold(true)->getColor()->setRGB('059669');
    $sheet->getStyle('Q3')->getFont()->setBold(true)->getColor()->setRGB('059669');

    $sheet->getStyle('P1:Q3')->applyFromArray([
        'borders' => [
            'outline' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'E5E7EB']
            ]
        ]
    ]);

    $sheet->getRowDimension(1)->setRowHeight(22);
    $sheet->getRowDimension(2)->setRowHeight(20);
    $sheet->getRowDimension(3)->setRowHeight(20);

    $sheet->freezePane('A2');

    $writer = new Xlsx($spreadsheet);
    $fileName = 'Template_Import_Kelas.xlsx';

    return response()->streamDownload(function() use ($writer) {
        $writer->save('php://output');
    }, $fileName, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
}

    /**
     * Export kelas untuk di-edit
     */
   public function exportForEdit(Request $request)
{
    $guruId = $request->get('guru_id');
    
    $query = Kelas::with('guru');
    
    if ($guruId) {
        $query->where('guru_id', $guruId);
    }
    
    $kelasList = $query->orderBy('name', 'asc')->get();
    
    if ($kelasList->isEmpty()) {
        return redirect()->back()->with('error', 'Tidak ada data kelas untuk diekspor.');
    }

    // ✅ LOG ACTIVITY - TAMBAHAN
    $this->logExport('kelas', 'Excel (Export for Edit)');
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // tingkat & kapasitas ikut diekspor agar bisa diperbarui massal.
    $headers = ['no_urut', 'nama_kelas', 'tahun_ajaran', 'no_guru', 'no_gender', 'tingkat', 'kapasitas'];
    $sheet->fromArray($headers, NULL, 'A1');

    $sheet->getStyle('A1:G1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1F2937']],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'F3F4F6']
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'bottom' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                'color' => ['rgb' => 'D1D5DB']
            ]
        ]
    ]);
    
    // Mapping guru dan gender
    $guruList = Guru::orderBy('id')->get();
    $guruIdToNoUrut = [];
    foreach ($guruList as $index => $guru) {
        $guruIdToNoUrut[$guru->id] = $index + 1;
    }
    
    // Mapping gender ke nomor
    $genderToNo = [
        'campuran' => 1,
        'ikhwan' => 2,
        'akhwat' => 3
    ];
    
    // Isi data kelas
    $row = 2;
    $noUrut = 1;
    foreach ($kelasList as $kelas) {
        $noGuru = $guruIdToNoUrut[$kelas->guru_id] ?? '';
        $noGender = $genderToNo[$kelas->gender_restriction] ?? '';
        
        $sheet->setCellValue('A' . $row, $noUrut);
        $sheet->setCellValue('B' . $row, $kelas->name);
        $sheet->setCellValue('C' . $row, $kelas->tahun_ajaran);
        $sheet->setCellValue('D' . $row, $noGuru);
        $sheet->setCellValue('E' . $row, $noGender);
        $sheet->setCellValue('F' . $row, $kelas->tingkat);
        $sheet->setCellValue('G' . $row, $kelas->kapasitas);

        $sheet->getStyle('A' . $row)->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F3F4F6']
            ]
        ]);
        
        $row++;
        $noUrut++;
    }
    
    // Data bergeser 2 kolom: tabel bantuan kini mulai dari I, bukan G.
    $sheet->getColumnDimension('A')->setWidth(10);
    $sheet->getColumnDimension('B')->setWidth(30);
    $sheet->getColumnDimension('C')->setWidth(15);
    $sheet->getColumnDimension('D')->setWidth(12);
    $sheet->getColumnDimension('E')->setWidth(12);
    $sheet->getColumnDimension('F')->setWidth(12);
    $sheet->getColumnDimension('G')->setWidth(12);
    $sheet->getColumnDimension('H')->setWidth(2);
    $sheet->getColumnDimension('I')->setWidth(32);
    $sheet->getColumnDimension('J')->setWidth(2);
    $sheet->getColumnDimension('K')->setWidth(8);
    $sheet->getColumnDimension('L')->setWidth(22);
    $sheet->getColumnDimension('M')->setWidth(2);
    $sheet->getColumnDimension('N')->setWidth(8);
    $sheet->getColumnDimension('O')->setWidth(15);

    // Petunjuk
    $sheet->setCellValue('I1', 'PETUNJUK');
    $sheet->setCellValue('I2', 'No Urut: JANGAN ubah');
    $sheet->setCellValue('I3', 'Nama Kelas: Bisa diubah');
    $sheet->setCellValue('I4', 'Tahun Ajaran: Format YYYY/YYYY');
    $sheet->setCellValue('I5', 'No Guru: Lihat tabel guru →');
    $sheet->setCellValue('I6', 'No Gender: Lihat tabel gender →');
    $sheet->setCellValue('I7', 'Tingkat: Opsional, contoh VII');
    $sheet->setCellValue('I8', 'Kapasitas: Daya tampung santri (1-100)');
    $sheet->setCellValue('I9', '');
    $sheet->setCellValue('I10', 'CATATAN PENTING:');
    $sheet->setCellValue('I11', '• Kolom NO URUT harus tetap ada');
    $sheet->setCellValue('I12', '• Jangan hapus/tambah baris');
    $sheet->setCellValue('I13', '• No Gender wajib diisi (1/2/3)');
    $sheet->setCellValue('I14', '• Kapasitas tidak boleh < jumlah santri');

    $sheet->getStyle('I1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '374151']],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'F9FAFB']
        ],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        'borders' => [
            'bottom' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'E5E7EB']
            ]
        ]
    ]);
    
    $sheet->getStyle('I2:I14')->applyFromArray([
        'font' => ['size' => 8, 'color' => ['rgb' => '6B7280']],
        'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]
    ]);

    $sheet->getStyle('I10')->applyFromArray([
        'font' => ['bold' => true, 'size' => 8, 'color' => ['rgb' => '374151']],
    ]);

    $sheet->getStyle('I1:I14')->applyFromArray([
        'borders' => [
            'outline' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'E5E7EB']
            ]
        ]
    ]);

    // Tabel Guru
    $sheet->setCellValue('K1', 'GURU');
    $sheet->mergeCells('K1:L1');
    $sheet->getStyle('K1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '374151']],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'F9FAFB']
        ],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        'borders' => [
            'bottom' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'E5E7EB']
            ]
        ]
    ]);
    
    $sheet->setCellValue('K2', 'No');
    $sheet->setCellValue('L2', 'Nama Guru');
    $sheet->getStyle('K2:L2')->applyFromArray([
        'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '6B7280']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ]);

    $currentRow = 3;
    foreach ($guruList as $index => $guru) {
        $sheet->setCellValue('K' . $currentRow, $index + 1);
        $sheet->setCellValue('L' . $currentRow, $guru->name);
        $sheet->getStyle('K' . $currentRow . ':L' . $currentRow)->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '374151']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);
        $currentRow++;
    }
    
    $lastRowGuru = $currentRow - 1;
    $sheet->getStyle('K1:L' . $lastRowGuru)->applyFromArray([
        'borders' => [
            'outline' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'E5E7EB']
            ]
        ]
    ]);

    // Tabel Gender Kelas
    $sheet->setCellValue('N1', 'GENDER KELAS');
    $sheet->mergeCells('N1:O1');
    $sheet->getStyle('N1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '374151']],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'F9FAFB']
        ],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        'borders' => [
            'bottom' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'E5E7EB']
            ]
        ]
    ]);
    
    $sheet->setCellValue('N2', 'No');
    $sheet->setCellValue('O2', 'Jenis Gender');
    $sheet->getStyle('N2:O2')->applyFromArray([
        'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '6B7280']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ]);
    
    // Data gender
    $genderData = [
        ['1', 'Campuran'],
        ['2', 'Ikhwan'],
        ['3', 'Akhwat']
    ];
    
    $genderRow = 3;
    foreach ($genderData as $data) {
        $sheet->setCellValue('N' . $genderRow, $data[0]);
        $sheet->setCellValue('O' . $genderRow, $data[1]);
        $sheet->getStyle('N' . $genderRow . ':O' . $genderRow)->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '374151']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);
        $genderRow++;
    }
    
    $sheet->getStyle('N1:O5')->applyFromArray([
        'borders' => [
            'outline' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'E5E7EB']
            ]
        ]
    ]);

    $sheet->getRowDimension(1)->setRowHeight(22);
    $sheet->freezePane('A2');

    $writer = new Xlsx($spreadsheet);
    $fileName = 'Edit_Kelas_' . date('Y-m-d') . '.xlsx';
    
    return response()->streamDownload(function() use ($writer) {
        $writer->save('php://output');
    }, $fileName, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
}

    /**
     * Bulk update kelas dari Excel
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:2048',
        ], [
            'file.required' => 'File Excel wajib diupload',
            'file.mimes' => 'File harus berformat Excel (.xlsx atau .xls)',
            'file.max' => 'Ukuran file maksimal 2MB',
        ]);

        try {
            $import = new KelasBulkUpdateImport();
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $failedCount = $import->getFailedCount();
            $errors = $import->getErrors();

            if ($failedCount > 0) {
                $errorMessage = "Berhasil update: {$successCount} kelas. Gagal: {$failedCount} kelas.<br><br>";
                $errorMessage .= "<strong>Detail Error:</strong><br>";
                $errorMessage .= implode('<br>', $errors);
                
                return redirect()->route('admin.kelas.index')
                    ->with('error', $errorMessage);
            }

            // ✅ LOG ACTIVITY - TAMBAHAN
            $this->logActivity(
                'update',
                'kelas',
                "Bulk update {$successCount} data kelas via Excel",
                null,
                null,
                ['total_updated' => $successCount]
            );

            return redirect()->route('admin.kelas.index')
                ->with('success', "Berhasil mengupdate {$successCount} data kelas!");

        } catch (\Exception $e) {
            \Log::error("Error bulk updating kelas: " . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Gagal mengupdate data: ' . $e->getMessage());
        }
    }

    /**
     * Smart capitalize - capitalize huruf pertama setiap kata dan setelah angka
     */
    private function smartCapitalize($text)
    {
        $text = trim($text);
        
        // Split berdasarkan spasi dan angka
        $result = preg_replace_callback('/(\d+|[a-zA-Z]+)/', function($matches) {
            $word = $matches[1];
            // Jika bukan angka, capitalize huruf pertama
            if (!is_numeric($word)) {
                return ucfirst(strtolower($word));
            }
            return $word;
        }, $text);
        
        return $result;
    }


    /**
     * Import kelas baru dari Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:2048',
        ], [
            'file.required' => 'File Excel wajib diupload',
            'file.mimes' => 'File harus berformat Excel (.xlsx atau .xls)',
            'file.max' => 'Ukuran file maksimal 2MB',
        ]);

        try {
            $import = new KelasImport();
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $failedCount = $import->getFailedCount();
            $errors = $import->getErrors();

            if ($failedCount > 0) {
                $errorMessage = "Berhasil import: {$successCount} kelas. Gagal: {$failedCount} kelas.<br><br>";
                $errorMessage .= "<strong>Detail Error:</strong><br>";
                $errorMessage .= implode('<br>', $errors);

                return redirect()->route('admin.kelas.index')->with('error', $errorMessage);
            }

            $this->logActivity(
                'create',
                'kelas',
                "Import {$successCount} data kelas via Excel",
                null,
                null,
                ['total_imported' => $successCount]
            );

            return redirect()->route('admin.kelas.index')
                ->with('success', "Berhasil import {$successCount} data kelas!");

        } catch (\Exception $e) {
            \Log::error("Error importing kelas: " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal import data: ' . $e->getMessage());
        }
    }
}