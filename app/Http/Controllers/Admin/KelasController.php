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

class KelasController extends Controller
{
     use LogsActivity;
    /**
     * Menampilkan daftar semua Kelas/Halaqah.
     */
    public function index()
    {
        $kelas = Kelas::with('guru')->latest()->get();
        $gurus = Guru::orderBy('name')->get();
        $genderRestrictions = Kelas::GENDER_RESTRICTIONS;
        
        return view('admin.kelas.index', compact('kelas', 'gurus', 'genderRestrictions'));
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
          $oldValues = $kela->only(['name', 'tahun_ajaran', 'guru_id', 'gender_restriction']);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tahun_ajaran' => 'required|string|max:10',
            'guru_id' => 'required|exists:gurus,id',
            'gender_restriction' => 'required|in:ikhwan,akhwat,campuran',
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
        
        $kela->update($validated);
        $this->logUpdate('kelas', $kela->name, $kela, $oldValues);

        return redirect()->route('admin.kelas.index')
            ->with('updated', 'Data Kelas/Halaqah berhasil diperbarui.');
    }   

    /**
     * Menghapus Kelas.
     */
    public function destroy(Kelas $kela)
    {
        $name = $kela->name;
        $oldValues = $kela->toArray();
        $kela->delete();
        $this->logDelete('kelas', $name, $oldValues);
        
        return redirect()->route('admin.kelas.index')
            ->with('deleted', 'Kelas/Halaqah berhasil dihapus.');
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
                ->with('deleted', "Berhasil menghapus {$count} kelas!");
                
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
    
    // Header dengan no_gender (bukan gender langsung)
    $headers = ['nama_kelas', 'tahun_ajaran', 'no_guru', 'no_gender'];
    $sheet->fromArray($headers, NULL, 'A1');
    
    $sheet->getStyle('A1:D1')->applyFromArray([
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

    $sheet->getStyle('E2:E11')->applyFromArray([
        'font' => ['size' => 8, 'color' => ['rgb' => '6B7280']],
        'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]
    ]);

    $sheet->getStyle('E7')->applyFromArray([
        'font' => ['bold' => true, 'size' => 8, 'color' => ['rgb' => '374151']],
    ]);

    $sheet->getStyle('E1:E11')->applyFromArray([
        'borders' => [
            'outline' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'E5E7EB']
            ]
        ]
    ]);
    
    // Contoh data dengan no_gender
    $exampleData = [
        ['Kelas 7A (Contoh)', '2024/2025', '1', '1'],  // campuran
        ['Kelas 8B (Contoh)', '2024/2025', '1', '2'],  // ikhwan
    ];
    $sheet->fromArray($exampleData, NULL, 'A2');
    
    $sheet->getColumnDimension('A')->setWidth(25);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setWidth(12);
    $sheet->getColumnDimension('D')->setWidth(15);
    $sheet->getColumnDimension('E')->setWidth(2);
    $sheet->getColumnDimension('F')->setWidth(30);
    $sheet->getColumnDimension('G')->setWidth(2);
    $sheet->getColumnDimension('H')->setWidth(8);
    $sheet->getColumnDimension('I')->setWidth(22);
    $sheet->getColumnDimension('J')->setWidth(2);
    $sheet->getColumnDimension('K')->setWidth(8);
    $sheet->getColumnDimension('L')->setWidth(15);
    
    // Petunjuk
    $sheet->setCellValue('F1', 'PETUNJUK');
    $sheet->setCellValue('F2', 'Nama Kelas: Nama lengkap kelas');
    $sheet->setCellValue('F3', 'Tahun Ajaran: Format YYYY/YYYY');
    $sheet->setCellValue('F4', 'No Guru: Lihat tabel guru →');
    $sheet->setCellValue('F5', 'No Gender: Lihat tabel gender →');
    $sheet->setCellValue('F6', '');
    $sheet->setCellValue('F7', 'CATATAN PENTING:');
    $sheet->setCellValue('F8', '• Baris 2-3 hanya contoh');
    $sheet->setCellValue('F9', '• Jangan ubah header');
    $sheet->setCellValue('F10', '• Gunakan nomor urut guru & gender');
    $sheet->setCellValue('F11', '• No Gender wajib diisi (1/2/3)');
    
    $sheet->getStyle('F1')->applyFromArray([
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
    
    $sheet->getStyle('F2:F11')->applyFromArray([
        'font' => ['size' => 8, 'color' => ['rgb' => '6B7280']],
        'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]
    ]);
    
    $sheet->getStyle('F7')->applyFromArray([
        'font' => ['bold' => true, 'size' => 8, 'color' => ['rgb' => '374151']],
    ]);
    
    $sheet->getStyle('F1:F11')->applyFromArray([
        'borders' => [
            'outline' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'E5E7EB']
            ]
        ]
    ]);
    
    // Tabel Guru
    $sheet->setCellValue('H1', 'GURU');
    $sheet->mergeCells('H1:I1');
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
    
    $sheet->setCellValue('H2', 'No_Urut');
    $sheet->setCellValue('I2', 'Nama Guru');
    $sheet->getStyle('H2:I2')->applyFromArray([
        'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '6B7280']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ]);
    
    $currentRow = 3;
    foreach ($guruList as $index => $guru) {
        $sheet->setCellValue('H' . $currentRow, $index + 1);
        $sheet->setCellValue('I' . $currentRow, $guru->name);
        $sheet->getStyle('H' . $currentRow . ':I' . $currentRow)->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '374151']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);
        $currentRow++;
    }
    
    $lastRowGuru = $currentRow - 1;
    $sheet->getStyle('H1:I' . $lastRowGuru)->applyFromArray([
        'borders' => [
            'outline' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'E5E7EB']
            ]
        ]
    ]);
    
    // Tabel Gender Kelas (BARU)
    $sheet->setCellValue('K1', 'GENDER KELAS');
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
    $sheet->setCellValue('L2', 'Jenis Gender');
    $sheet->getStyle('K2:L2')->applyFromArray([
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
        $sheet->setCellValue('K' . $genderRow, $data[0]);
        $sheet->setCellValue('L' . $genderRow, $data[1]);
        $sheet->getStyle('K' . $genderRow . ':L' . $genderRow)->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '374151']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);
        $genderRow++;
    }
    
    $sheet->getStyle('K1:L5')->applyFromArray([
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
    
    // Header dengan no_gender
    $headers = ['no_urut', 'nama_kelas', 'tahun_ajaran', 'no_guru', 'no_gender'];
    $sheet->fromArray($headers, NULL, 'A1');
    
    $sheet->getStyle('A1:E1')->applyFromArray([
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
        
        $sheet->getStyle('A' . $row)->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F3F4F6']
            ]
        ]);
        
        $row++;
        $noUrut++;
    }
    
    $sheet->getColumnDimension('A')->setWidth(10);
    $sheet->getColumnDimension('B')->setWidth(30);
    $sheet->getColumnDimension('C')->setWidth(15);
    $sheet->getColumnDimension('D')->setWidth(12);
    $sheet->getColumnDimension('E')->setWidth(12);
    $sheet->getColumnDimension('F')->setWidth(2);
    $sheet->getColumnDimension('G')->setWidth(30);
    $sheet->getColumnDimension('H')->setWidth(2);
    $sheet->getColumnDimension('I')->setWidth(8);
    $sheet->getColumnDimension('J')->setWidth(22);
    $sheet->getColumnDimension('K')->setWidth(2);
    $sheet->getColumnDimension('L')->setWidth(8);
    $sheet->getColumnDimension('M')->setWidth(15);
    
    // Petunjuk
    $sheet->setCellValue('G1', 'PETUNJUK');
    $sheet->setCellValue('G2', 'No Urut: JANGAN ubah');
    $sheet->setCellValue('G3', 'Nama Kelas: Bisa diubah');
    $sheet->setCellValue('G4', 'Tahun Ajaran: Format YYYY/YYYY');
    $sheet->setCellValue('G5', 'No Guru: Lihat tabel guru →');
    $sheet->setCellValue('G6', 'No Gender: Lihat tabel gender →');
    $sheet->setCellValue('G7', '');
    $sheet->setCellValue('G8', 'CATATAN PENTING:');
    $sheet->setCellValue('G9', '• Kolom NO URUT harus tetap ada');
    $sheet->setCellValue('G10', '• Jangan hapus/tambah baris');
    $sheet->setCellValue('G11', '• No Gender wajib diisi (1/2/3)');
    
    $sheet->getStyle('G1')->applyFromArray([
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
    
    $sheet->getStyle('G2:G11')->applyFromArray([
        'font' => ['size' => 8, 'color' => ['rgb' => '6B7280']],
        'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]
    ]);
    
    $sheet->getStyle('G8')->applyFromArray([
        'font' => ['bold' => true, 'size' => 8, 'color' => ['rgb' => '374151']],
    ]);

    $sheet->getStyle('G1:G11')->applyFromArray([
        'borders' => [
            'outline' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'E5E7EB']
            ]
        ]
    ]);
    
    // Tabel Guru
    $sheet->setCellValue('I1', 'GURU');
    $sheet->mergeCells('I1:J1');
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
    
    $sheet->setCellValue('I2', 'No');
    $sheet->setCellValue('J2', 'Nama Guru');
    $sheet->getStyle('I2:J2')->applyFromArray([
        'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '6B7280']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ]);
    
    $currentRow = 3;
    foreach ($guruList as $index => $guru) {
        $sheet->setCellValue('I' . $currentRow, $index + 1);
        $sheet->setCellValue('J' . $currentRow, $guru->name);
        $sheet->getStyle('I' . $currentRow . ':J' . $currentRow)->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '374151']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);
        $currentRow++;
    }
    
    $lastRowGuru = $currentRow - 1;
    $sheet->getStyle('I1:J' . $lastRowGuru)->applyFromArray([
        'borders' => [
            'outline' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'E5E7EB']
            ]
        ]
    ]);
    
    // Tabel Gender Kelas (BARU)
    $sheet->setCellValue('L1', 'GENDER KELAS');
    $sheet->mergeCells('L1:M1');
    $sheet->getStyle('L1')->applyFromArray([
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
    
    $sheet->setCellValue('L2', 'No');
    $sheet->setCellValue('M2', 'Jenis Gender');
    $sheet->getStyle('L2:M2')->applyFromArray([
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
        $sheet->setCellValue('L' . $genderRow, $data[0]);
        $sheet->setCellValue('M' . $genderRow, $data[1]);
        $sheet->getStyle('L' . $genderRow . ':M' . $genderRow)->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '374151']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);
        $genderRow++;
    }
    
    $sheet->getStyle('L1:M5')->applyFromArray([
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
                ->with('updated', "Berhasil mengupdate {$successCount} data kelas!");

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