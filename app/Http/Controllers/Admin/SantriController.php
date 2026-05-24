<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Santri;
use App\Models\Kelas;
use App\Models\Guru;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SantriImport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Imports\SantriBulkUpdateImport;

class SantriController extends Controller
{
    use LogsActivity;

    public function index()
    {
        $kelasWithStats = Kelas::withCount('santris')
            ->with(['guru', 'santris' => function($query) {
                $query->select('kelas_id', 'gender', DB::raw('count(*) as total'))
                    ->groupBy('kelas_id', 'gender');
            }])
            ->orderBy('name', 'asc')
            ->get()
            ->map(function($kelas) {
                $lakiLaki = $kelas->santris->where('gender', 'Laki-laki')->sum('total');
                $perempuan = $kelas->santris->where('gender', 'Perempuan')->sum('total');
                
                return [
                    'id' => $kelas->id,
                    'name' => $kelas->name,
                    'guru_name' => $kelas->guru->name ?? 'Belum ada guru',
                    'guru_id' => $kelas->guru_id,
                    'gender_restriction' => $kelas->gender_restriction,
                    'total_santri' => $kelas->santris_count,
                    'laki_laki' => $lakiLaki,
                    'perempuan' => $perempuan,
                ];
            });

        return view('admin.santri.index', compact('kelasWithStats'));
    }

    public function detail($kelasId)
    {
        $kelas = Kelas::with('guru')->findOrFail($kelasId);
        
        $santris = Santri::with(['kelas', 'guru', 'user'])
            ->where('kelas_id', $kelasId)
            ->orderBy('name', 'asc')
            ->paginate(15);
        
        $allKelas = Kelas::with('guru')->get();
        
        $hasGenderMismatch = $kelas->hasGenderMismatch();
        $mismatchedSantris = $hasGenderMismatch ? $kelas->getMismatchedSantris() : collect();
        
        return view('admin.santri.detail', compact('kelas', 'santris', 'allKelas', 'hasGenderMismatch', 'mismatchedSantris'));
    }

    /**
     * ✅ STORE - Dengan Upload Foto Profil
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nis' => 'required|string|unique:santris,nis',
            'name' => 'required|string|max:255',
            'gender' => 'required|in:L,P',
            'target_juz' => 'required|numeric|min:0|max:30',
            'kelas_id' => 'required|exists:kelas,id',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'nis.unique' => 'NIS sudah terdaftar.',
        ]);

        // Validasi gender sesuai kelas
        $kelas = Kelas::findOrFail($validated['kelas_id']);
        $genderFull = $validated['gender'] === 'L' ? 'Laki-laki' : 'Perempuan';
        
        if ($kelas->gender_restriction === 'ikhwan' && $genderFull !== 'Laki-laki') {
            return redirect()->back()->withInput()
                ->with('error', "Kelas {$kelas->name} adalah kelas khusus Ikhwan (Laki-laki). Santri perempuan tidak dapat ditambahkan ke kelas ini.");
        }
        
        if ($kelas->gender_restriction === 'akhwat' && $genderFull !== 'Perempuan') {
            return redirect()->back()->withInput()
                ->with('error', "Kelas {$kelas->name} adalah kelas khusus Akhwat (Perempuan). Santri laki-laki tidak dapat ditambahkan ke kelas ini.");
        }

        return DB::transaction(function () use ($validated) {
            try {
                $name = preg_replace_callback('/\b\w+/', function($matches) {
                    return ucfirst(strtolower($matches[0]));
                }, trim($validated['name']));
                
                $genderFull = $validated['gender'] === 'L' ? 'Laki-laki' : 'Perempuan';
                $kelas = Kelas::findOrFail($validated['kelas_id']);
                $guruId = $kelas->guru_id;
                
                $santriUser = User::create([
                    'name' => $name,
                    'password' => Hash::make($validated['password']),
                    'plain_password' => $validated['password'],
                    'role' => 'santri',
                ]);
                
                $santri = Santri::create([
                    'user_id' => $santriUser->id,
                    'kelas_id' => $validated['kelas_id'],
                    'guru_id' => $guruId,
                    'nis' => $validated['nis'],
                    'name' => $name, 
                    'gender' => $genderFull,
                    'target_juz' => $validated['target_juz'],
                ]);

                $this->logCreate('santri', "{$santri->name} (NIS: {$santri->nis})", $santri);

                return redirect()->route('admin.santris.detail', $validated['kelas_id'])
                    ->with('success', 'Santri dan akun login berhasil ditambahkan!');
                    
            } catch (\Exception $e) {
                \Log::error("Error creating Santri: " . $e->getMessage()); 
                return redirect()->back()->withInput()
                    ->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
            }
        });
    }

    public function update(Request $request, Santri $santri)
{
    $validated = $request->validate([
        'nis' => ['required', 'string', Rule::unique('santris', 'nis')->ignore($santri->id)],
        'name' => 'required|string|max:255',
        'gender' => 'required|in:L,P',
        'target_juz' => 'required|numeric|min:0|max:30',
        'kelas_id' => 'required|exists:kelas,id',
        'password' => 'nullable|string|min:8',
    ], [
        'password.min' => 'Password minimal 8 karakter.',
    ]);

    $oldValues = $santri->only(['nis', 'name', 'gender', 'target_juz', 'kelas_id', 'guru_id']);
    
    // Validasi gender
    $kelas = Kelas::findOrFail($validated['kelas_id']);
    $genderFull = $validated['gender'] === 'L' ? 'Laki-laki' : 'Perempuan';
    
    if ($kelas->gender_restriction === 'ikhwan' && $genderFull !== 'Laki-laki') {
        return redirect()->back()->withInput()
            ->with('error', "Kelas {$kelas->name} adalah kelas khusus Ikhwan (Laki-laki).");
    }
    
    if ($kelas->gender_restriction === 'akhwat' && $genderFull !== 'Perempuan') {
        return redirect()->back()->withInput()
            ->with('error', "Kelas {$kelas->name} adalah kelas khusus Akhwat (Perempuan).");
    }

    return DB::transaction(function () use ($validated, $santri, $oldValues) {
        try {
            $name = preg_replace_callback('/\b\w+/', function($matches) {
                return ucfirst(strtolower($matches[0]));
            }, trim($validated['name']));
            
            $genderFull = $validated['gender'] === 'L' ? 'Laki-laki' : 'Perempuan';
            $kelas = Kelas::findOrFail($validated['kelas_id']);
            $guruId = $kelas->guru_id;
            
            // Update data santri
            $santri->update([
                'nis' => $validated['nis'],
                'name' => $name,
                'gender' => $genderFull,
                'target_juz' => $validated['target_juz'],
                'kelas_id' => $validated['kelas_id'],
                'guru_id' => $guruId,
            ]);

            $this->logUpdate('santri', $santri->name, $santri, $oldValues);

            // UPDATE USER
            if ($santri->user) {
                $updateData = ['name' => $name];
                
                // Update password jika diisi
                if (!empty($validated['password'])) {
                    $updateData['password'] = Hash::make($validated['password']);
                    $updateData['plain_password'] = $validated['password'];
                }
                
                $santri->user->update($updateData);
            }

            return redirect()->route('admin.santris.detail', $validated['kelas_id'])
                ->with('updated', 'Data santri dan akun login berhasil diperbarui!');
                
        } catch (\Exception $e) {
            \Log::error("Error updating Santri: " . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    });
}



    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'santri_ids' => 'required|array|min:1',
            'santri_ids.*' => 'exists:santris,id',
        ]);

        try {
            $kelasId = $request->kelas_id;
            $deletedCount = count($request->santri_ids);
            $santriNames = Santri::whereIn('id', $request->santri_ids)->pluck('name')->toArray();

            DB::transaction(function () use ($request) {
                $santris = Santri::with('user')->whereIn('id', $request->santri_ids)->get();
                
                foreach ($santris as $santri) {
                    if ($santri->user) {
                        $santri->user->delete();
                    }
                    $santri->delete();
                }
            });
            $this->logActivity(
                'delete',
                'santri',
                "Bulk delete {$deletedCount} santri: " . implode(', ', array_slice($santriNames, 0, 3)) . ($deletedCount > 3 ? '...' : ''),
                null,
                ['ids' => $request->santri_ids, 'total' => $deletedCount],
                null
            );

            if ($kelasId) {
                return redirect()->route('admin.santris.detail', $kelasId)
                    ->with('deleted', "Berhasil menghapus {$deletedCount} santri dan akun login terkait!");
            }
            
            return redirect()->route('admin.santris.index')
                ->with('deleted', "Berhasil menghapus {$deletedCount} santri dan akun login terkait!");
                
        } catch (\Exception $e) {
            \Log::error("Error bulk deleting Santri: " . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }



    
   /**
 * ✅ DOWNLOAD TEMPLATE - Tanpa Foto Profil (DIPERBAIKI)
 */
public function downloadTemplate()
{
    // ✅ LOG ACTIVITY - TAMBAHAN
    $this->logDownload('santri', 'Template Import Excel');

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $kelasList = Kelas::with('guru')->orderBy('id')->get();
    
    // Headers (hanya 6 kolom, tanpa foto_profil)
    $headers = ['nis', 'nama', 'gender', 'target_juz', 'no_kelas', 'password'];
    $sheet->fromArray($headers, NULL, 'A1');
    
    // Style header (A1:F1, bukan A1:G1)
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
    
    // Style baris contoh (A2:F3, bukan A2:G3)
    $sheet->getStyle('A2:F3')->applyFromArray([
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'FFFBEB']
        ],
        'font' => ['size' => 9, 'color' => ['rgb' => '78716C'], 'italic' => true],
        'borders' => [
            'outline' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'FDE68A']
            ]
        ]
    ]);
    
    // Data contoh dengan prefix CONTOH: agar tidak ikut terimport
    $exampleData = [
        ['12345 (Contoh)', 'Ahmad ', 'L', '5.50', '1', 'pass1234'],
        ['12346 (Contoh)', 'Aisyah ', 'P', '3.25', '1', 'pass8765'],
    ];
    
    foreach ($exampleData as $rowIndex => $row) {
        $actualRow = $rowIndex + 2;
        $sheet->setCellValue('A' . $actualRow, $row[0]);
        $sheet->setCellValue('B' . $actualRow, $row[1]);
        $sheet->setCellValue('C' . $actualRow, $row[2]);
        $sheet->setCellValue('D' . $actualRow, $row[3]);
        $sheet->setCellValue('E' . $actualRow, $row[4]);
        $sheet->setCellValue('F' . $actualRow, $row[5]);
    }
    
    // Lebar kolom (hanya sampai F, kolom G tidak digunakan)
    $sheet->getColumnDimension('A')->setWidth(16);  // NIS
    $sheet->getColumnDimension('B')->setWidth(30);  // Nama
    $sheet->getColumnDimension('C')->setWidth(8);   // Gender
    $sheet->getColumnDimension('D')->setWidth(12);  // Target Juz
    $sheet->getColumnDimension('E')->setWidth(10);  // No Kelas
    $sheet->getColumnDimension('F')->setWidth(18);  // Password
    $sheet->getColumnDimension('G')->setWidth(2);   // Spacing
    $sheet->getColumnDimension('H')->setWidth(2);   // Spacing
    $sheet->getColumnDimension('I')->setWidth(40);  // Petunjuk
    $sheet->getColumnDimension('J')->setWidth(2);   // Spacing
    $sheet->getColumnDimension('K')->setWidth(8);   // No Kelas
    $sheet->getColumnDimension('L')->setWidth(35);  // Kelas - Guru
    
    // PETUNJUK IMPORT
    $sheet->setCellValue('I1', 'PETUNJUK IMPORT SANTRI');
    $sheet->setCellValue('I2', '1. NIS: Nomor Induk Santri (unik)');
    $sheet->setCellValue('I3', '2. Nama: Nama lengkap santri');
    $sheet->setCellValue('I4', '3. Gender: L (Laki-laki) atau P (Perempuan)');
    $sheet->setCellValue('I5', '4. Target Juz: 0.00 - 30.00');
    $sheet->setCellValue('I6', '5. No Kelas: Lihat tabel kanan →');
    $sheet->setCellValue('I7', '6. Password: Minimal 8 karakter (huruf/angka)');
    $sheet->setCellValue('I8', '');
    $sheet->setCellValue('I9', 'CATATAN:');
    $sheet->setCellValue('I10', '• Baris 2-3 adalah CONTOH, hapus sebelum import');
    $sheet->setCellValue('I11', '• NIS yang diawali "CONTOH:" akan diabaikan sistem');
    $sheet->setCellValue('I12', '• Pastikan semua data sudah benar sebelum import');
    
    // Style petunjuk header
    $sheet->getStyle('I1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '2563EB']
        ],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
    ]);
    
    // Style isi petunjuk
    $sheet->getStyle('I2:I8')->applyFromArray([
        'font' => ['size' => 9, 'color' => ['rgb' => '374151']],
    ]);
    
    // Style catatan
    $sheet->getStyle('I9')->applyFromArray([
        'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'DC2626']],
    ]);
    
    $sheet->getStyle('I10:I12')->applyFromArray([
        'font' => ['size' => 8, 'color' => ['rgb' => '374151']],
    ]);
    
    // Border petunjuk
    $sheet->getStyle('I1:I12')->applyFromArray([
        'borders' => [
            'outline' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                'color' => ['rgb' => '3B82F6']
            ]
        ]
    ]);
    
    // TABEL KELAS & GURU
    $sheet->setCellValue('K1', 'KELAS & GURU');
    $sheet->mergeCells('K1:L1');
    $sheet->getStyle('K1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1F2937']],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'F3F4F6']
        ],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        'borders' => [
            'bottom' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                'color' => ['rgb' => 'D1D5DB']
            ]
        ]
    ]);
    
    $sheet->setCellValue('K2', 'No');
    $sheet->setCellValue('L2', 'Kelas - Guru');
    $sheet->getStyle('K2:L2')->applyFromArray([
        'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '6B7280']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ]);
    
    // Isi tabel kelas
    $currentRow = 3;
    foreach ($kelasList as $index => $kelas) {
        $sheet->setCellValue('K' . $currentRow, $index + 1);
        $guruName = $kelas->guru ? $kelas->guru->name : 'Tanpa Guru';
        $sheet->setCellValue('L' . $currentRow, $kelas->name . ' - ' . $guruName);
        $sheet->getStyle('K' . $currentRow . ':L' . $currentRow)->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '374151']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);
        $currentRow++;
    }
    
    // Border tabel kelas
    $lastRowKelas = $currentRow - 1;
    $sheet->getStyle('K1:L' . $lastRowKelas)->applyFromArray([
        'borders' => [
            'outline' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'E5E7EB']
            ]
        ]
    ]);
    
    // Freeze pane dan row height
    $sheet->getRowDimension(1)->setRowHeight(22);
    $sheet->freezePane('A2');
    
    // Export file
    $writer = new Xlsx($spreadsheet);
    $fileName = 'Template_Import_Santri_' . date('Y-m-d') . '.xlsx';
    
    return response()->streamDownload(function() use ($writer) {
        $writer->save('php://output');
    }, $fileName, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
}

    /**
 * ✅ EXPORT UNTUK EDIT - Tanpa Foto Profil (DIPERBAIKI)
 */
public function exportForEdit($kelasId)
{
    $kelas = Kelas::with('guru')->findOrFail($kelasId);
    
    $santris = Santri::with(['kelas', 'guru', 'user'])
        ->where('kelas_id', $kelasId)
        ->orderBy('name', 'asc')
        ->get();
    
    if ($santris->isEmpty()) {
        return redirect()->back()->with('error', 'Tidak ada data santri untuk diekspor.');
    }

    // ✅ LOG ACTIVITY - TAMBAHAN
    $this->logExport('santri', "Excel (Export for Edit - Kelas {$kelas->name})");
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Headers (hanya 6 kolom, tanpa foto_profil)
    $headers = ['no_urut', 'nis', 'nama', 'gender', 'target_juz', 'no_kelas_baru'];
    $sheet->fromArray($headers, NULL, 'A1');
    
    // Style header (A1:F1, bukan A1:G1)
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
    
    // ISI DATA
    $row = 2;
    $noUrut = 1;
    foreach ($santris as $santri) {
        $sheet->setCellValue('A' . $row, $noUrut);
        $sheet->setCellValue('B' . $row, $santri->nis);
        $sheet->setCellValue('C' . $row, $santri->name);
        $sheet->setCellValue('D' . $row, $santri->gender === 'Laki-laki' ? 'L' : 'P');
        $sheet->setCellValue('E' . $row, $santri->target_juz);
        $sheet->setCellValue('F' . $row, ''); // no_kelas_baru (kosong, diisi manual jika ingin pindah kelas)
        
        // Style kolom NO URUT dengan background abu-abu (read-only indicator)
        $sheet->getStyle('A' . $row)->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F3F4F6']
            ]
        ]);
        
        $row++;
        $noUrut++;
    }
    
    // LEBAR KOLOM
    $sheet->getColumnDimension('A')->setWidth(10);  // no_urut
    $sheet->getColumnDimension('B')->setWidth(16);  // nis
    $sheet->getColumnDimension('C')->setWidth(30);  // nama
    $sheet->getColumnDimension('D')->setWidth(8);   // gender
    $sheet->getColumnDimension('E')->setWidth(12);  // target_juz
    $sheet->getColumnDimension('F')->setWidth(14);  // no_kelas_baru
    $sheet->getColumnDimension('G')->setWidth(2);   // spacing
    $sheet->getColumnDimension('H')->setWidth(2);   // spacing
    $sheet->getColumnDimension('I')->setWidth(42);  // petunjuk
    $sheet->getColumnDimension('J')->setWidth(2);   // spacing
    $sheet->getColumnDimension('K')->setWidth(8);   // no kelas
    $sheet->getColumnDimension('L')->setWidth(35);  // kelas - guru
    
    // PETUNJUK BULK UPDATE
    $sheet->setCellValue('I1', '📋 PANDUAN BULK UPDATE');
    $sheet->setCellValue('I2', '');
    $sheet->setCellValue('I3', '⚠️ ATURAN PENTING:');
    $sheet->setCellValue('I4', '• JANGAN ubah/hapus kolom NO URUT');
    $sheet->setCellValue('I5', '• JANGAN tambah/hapus baris');
    $sheet->setCellValue('I6', '• Password tidak bisa diubah via Excel');
    $sheet->setCellValue('I7', '');
    $sheet->setCellValue('I8', '📝 CARA UPDATE:');
    $sheet->setCellValue('I9', '1. Edit data yang ingin diubah');
    $sheet->setCellValue('I10', '2. Isi "No Kelas Baru" untuk pindah kelas');
    $sheet->setCellValue('I11', '3. Lihat nomor kelas di tabel sebelah →');
    $sheet->setCellValue('I12', '4. Upload file Excel yang sudah diedit');
    $sheet->setCellValue('I13', '');
    $sheet->setCellValue('I14', 'CATATAN:');
    $sheet->setCellValue('I15', '• Kosongkan "No Kelas Baru" jika tidak pindah');
    $sheet->setCellValue('I16', '• NIS harus tetap unik (tidak boleh sama)');
    
    // Style header petunjuk
    $sheet->getStyle('I1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '059669']
        ],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
    ]);
    
    // Style aturan penting
    $sheet->getStyle('I3')->applyFromArray([
        'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'DC2626']],
    ]);
    
    // Style cara update
    $sheet->getStyle('I8')->applyFromArray([
        'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '2563EB']],
    ]);
    
    // Style catatan
    $sheet->getStyle('I14')->applyFromArray([
        'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'D97706']],
    ]);
    
    // Style isi petunjuk
    $sheet->getStyle('I2:I16')->applyFromArray([
        'font' => ['size' => 8, 'color' => ['rgb' => '374151']],
    ]);
    
    // Border petunjuk
    $sheet->getStyle('I1:I16')->applyFromArray([
        'borders' => [
            'outline' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                'color' => ['rgb' => '059669']
            ]
        ]
    ]);
    
    // TABEL KELAS & GURU
    $kelasList = Kelas::with('guru')->orderBy('id')->get();
    
    $sheet->setCellValue('K1', 'KELAS & GURU');
    $sheet->mergeCells('K1:L1');
    $sheet->getStyle('K1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1F2937']],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'F3F4F6']
        ],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        'borders' => [
            'bottom' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                'color' => ['rgb' => 'D1D5DB']
            ]
        ]
    ]);
    
    $sheet->setCellValue('K2', 'No');
    $sheet->setCellValue('L2', 'Kelas - Guru');
    $sheet->getStyle('K2:L2')->applyFromArray([
        'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '6B7280']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ]);
    
    // Isi tabel kelas
    $currentRow = 3;
    foreach ($kelasList as $index => $kelasItem) {
        $sheet->setCellValue('K' . $currentRow, $index + 1);
        $guruName = $kelasItem->guru ? $kelasItem->guru->name : 'Tanpa Guru';
        $sheet->setCellValue('L' . $currentRow, $kelasItem->name . ' - ' . $guruName);
        $sheet->getStyle('K' . $currentRow . ':L' . $currentRow)->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '374151']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);
        $currentRow++;
    }
    
    // Border tabel kelas
    $lastRowKelas = $currentRow - 1;
    $sheet->getStyle('K1:L' . $lastRowKelas)->applyFromArray([
        'borders' => [
            'outline' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'E5E7EB']
            ]
        ]
    ]);
    
    // Freeze pane dan row height
    $sheet->getRowDimension(1)->setRowHeight(22);
    $sheet->freezePane('A2');
    
    // Export file
    $writer = new Xlsx($spreadsheet);
    
    $kelasName = str_replace(' ', '_', $kelas->name);
    $fileName = 'Edit_Santri_' . $kelasName . '_' . date('Y-m-d') . '.xlsx';
    
    return response()->streamDownload(function() use ($writer) {
        $writer->save('php://output');
    }, $fileName, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
}

   

   /**
     * ✅ IMPORT 
     */
    public function import(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:xlsx,xls|max:2048',
    ], [
        'file.required' => 'File Excel wajib diupload',
        'file.mimes' => 'File harus berformat Excel (.xlsx atau .xls)',
    ]);

    try {
        $import = new SantriImport();
        Excel::import($import, $request->file('file'));

        $successCount = $import->getSuccessCount();
        $failedCount = $import->getFailedCount();
        $errors = $import->getErrors();

        if ($failedCount > 0) {
            $errorMessage = "Berhasil: {$successCount} santri. Gagal: {$failedCount} santri.<br><br>";
            $errorMessage .= "<strong>Detail Error:</strong><br>";
            $errorMessage .= implode('<br>', $errors);
            
            return redirect()->route('admin.santris.index')
                ->with('error', $errorMessage);
        }

        $this->logActivity(
            'import',
            'santri',
            "Import {$successCount} data santri dari Excel",
            null,
            null,
            ['total_imported' => $successCount, 'kelas_stats' => $import->getKelasStats()]
        );

        $kelasStats = $import->getKelasStats();
        $statsText = [];
        foreach ($kelasStats as $kelasName => $count) {
            $statsText[] = "{$kelasName}: {$count} santri";
        }
        
        $message = "Berhasil mengimport {$successCount} santri!";
        if (!empty($statsText)) {
            $message .= " (" . implode(', ', $statsText) . ")";
        }

        return redirect()->route('admin.santris.index')
            ->with('success', $message);

    } catch (\Exception $e) {
        \Log::error("Error importing santri: " . $e->getMessage());
        \Log::error("Stack trace: " . $e->getTraceAsString());
        
        return redirect()->back()
            ->with('error', 'Gagal mengimport data: ' . $e->getMessage());
    }
}

    /**
     * ✅ BULK UPDATE - Dengan ZIP Foto (DIPERBAIKI)
     */
    public function bulkUpdate(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:xlsx,xls|max:2048',
        'kelas_id' => 'required|exists:kelas,id',
    ], [
        'file.required' => 'File Excel wajib diupload',
        'file.mimes' => 'File harus berformat Excel (.xlsx atau .xls)',
        'file.max' => 'Ukuran file maksimal 2MB',
        'kelas_id.required' => 'Kelas ID tidak ditemukan',
    ]);

    $kelasId = $request->kelas_id;

    try {
        $import = new SantriBulkUpdateImport($kelasId);
        Excel::import($import, $request->file('file'));

        $successCount = $import->getSuccessCount();
        $failedCount = $import->getFailedCount();
        $errors = $import->getErrors();

        if ($failedCount > 0) {
            $errorMessage = "Berhasil update: {$successCount} santri. Gagal: {$failedCount} santri.<br><br>";
            $errorMessage .= "<strong>Detail Error:</strong><br>";
            $errorMessage .= implode('<br>', $errors);
            
            return redirect()->route('admin.santris.detail', $kelasId)
                ->with('error', $errorMessage);
        }

        $this->logActivity(
            'update',
            'santri',
            "Bulk update {$successCount} data santri dari Excel di kelas ID {$kelasId}",
            null,
            null,
            ['total_updated' => $successCount, 'kelas_id' => $kelasId]
        );

        return redirect()->route('admin.santris.detail', $kelasId)
            ->with('updated', "Berhasil mengupdate {$successCount} data santri!");

    } catch (\Exception $e) {
        \Log::error("Error bulk updating santri: " . $e->getMessage());
        \Log::error("Stack trace: " . $e->getTraceAsString());
        
        return redirect()->route('admin.santris.detail', $kelasId)
            ->with('error', 'Gagal mengupdate data: ' . $e->getMessage());
    }
}
    
    /**
     * Destroy - hapus santri beserta foto profil
     */
    public function destroy(Santri $santri)
    {
        try {
            $name = $santri->name;
            $kelasId = $santri->kelas_id;
            $oldValues = $santri->toArray();
            
            DB::beginTransaction();

            // Hapus user terkait
            if ($santri->user) {
                $santri->user->delete();
            }

            // Hapus santri
            $santri->delete();
            
            $this->logDelete('santri', $name, $oldValues);

            DB::commit();

            return redirect()->route('admin.santris.detail', $kelasId)
                ->with('deleted', 'Santri berhasil dihapus!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal menghapus Santri. ' . $e->getMessage());
        }
    }
}