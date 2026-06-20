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
use App\Enums\MetodeTarget;
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
            'target_metode' => ['required', Rule::enum(MetodeTarget::class)],
            'target_juz'    => ['nullable', 'required_if:target_metode,quran', 'numeric', 'min:0', 'max:30'],
            'target_jilid'  => ['nullable', 'required_if:target_metode,iqro', 'integer', 'min:1', 'max:6'],
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
                // Null-kan field yang tidak dipakai metode terpilih
                if ($validated['target_metode'] === 'quran') {
                    $validated['target_jilid'] = null;
                } else {
                    $validated['target_juz'] = null;
                }
                
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
                    'target_metode' => $validated['target_metode'],
                    'target_juz' => $validated['target_juz'] ?? null,
                    'target_jilid' => $validated['target_jilid'] ?? null,
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
        'target_metode' => ['required', Rule::enum(MetodeTarget::class)],
        'target_juz'    => ['nullable', 'required_if:target_metode,quran', 'numeric', 'min:0', 'max:30'],
        'target_jilid'  => ['nullable', 'required_if:target_metode,iqro', 'integer', 'min:1', 'max:6'],
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

            // Null-kan field yang tidak dipakai metode terpilih
            if ($validated['target_metode'] === 'quran') {
                $validated['target_jilid'] = null;
            } else {
                $validated['target_juz'] = null;
            }

            $genderFull = $validated['gender'] === 'L' ? 'Laki-laki' : 'Perempuan';
            $kelas = Kelas::findOrFail($validated['kelas_id']);
            $guruId = $kelas->guru_id;
            
            // Update data santri
            $santri->update([
                    'nis' => $validated['nis'],
                    'name' => $name,
                    'gender' => $genderFull,
                    'target_metode' => $validated['target_metode'],
                    'target_juz' => $validated['target_juz'] ?? null,
                    'target_jilid' => $validated['target_jilid'] ?? null,
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
    $this->logDownload('santri', 'Template Import Excel');

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $kelasList = Kelas::with('guru')->orderBy('id')->get();

    // Headers (8 kolom: tambah metode & target_jilid)
    $headers = ['nis', 'nama', 'gender', 'metode', 'target_juz', 'target_jilid', 'no_kelas', 'password'];
    $sheet->fromArray($headers, NULL, 'A1');

    // Style header (A1:H1)
    $sheet->getStyle('A1:H1')->applyFromArray([
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

    // Style baris contoh (A2:H3)
    $sheet->getStyle('A2:H3')->applyFromArray([
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

    // Data contoh: satu Qur'an, satu Iqro
    $exampleData = [
        ['12345 (Contoh)', 'Ahmad',  'L', 'quran', '5.50', '',  '1', 'pass1234'],
        ['12346 (Contoh)', 'Aisyah', 'P', 'iqro',  '',     '3', '1', 'pass8765'],
    ];

    foreach ($exampleData as $rowIndex => $row) {
        $actualRow = $rowIndex + 2;
        $col = 'A';
        foreach ($row as $val) {
            $sheet->setCellValue($col . $actualRow, $val);
            $col++;
        }
    }

    // Lebar kolom data (A-H)
    $sheet->getColumnDimension('A')->setWidth(16);  // NIS
    $sheet->getColumnDimension('B')->setWidth(28);  // Nama
    $sheet->getColumnDimension('C')->setWidth(8);   // Gender
    $sheet->getColumnDimension('D')->setWidth(10);  // Metode
    $sheet->getColumnDimension('E')->setWidth(11);  // Target Juz
    $sheet->getColumnDimension('F')->setWidth(11);  // Target Jilid
    $sheet->getColumnDimension('G')->setWidth(9);   // No Kelas
    $sheet->getColumnDimension('H')->setWidth(16);  // Password
    $sheet->getColumnDimension('I')->setWidth(2);   // spacing
    $sheet->getColumnDimension('J')->setWidth(46);  // Petunjuk
    $sheet->getColumnDimension('K')->setWidth(2);   // spacing
    $sheet->getColumnDimension('L')->setWidth(8);   // No Kelas
    $sheet->getColumnDimension('M')->setWidth(35);  // Kelas - Guru

    // PETUNJUK IMPORT (kolom J)
    $sheet->setCellValue('J1', 'PETUNJUK IMPORT SANTRI');
    $sheet->setCellValue('J2', '1. NIS: Nomor Induk Santri (unik)');
    $sheet->setCellValue('J3', '2. Nama: Nama lengkap santri');
    $sheet->setCellValue('J4', '3. Gender: L (Laki-laki) atau P (Perempuan)');
    $sheet->setCellValue('J5', '4. Metode: quran atau iqro');
    $sheet->setCellValue('J6', '5. Target Juz: 0.00 - 30.00 (isi bila metode quran)');
    $sheet->setCellValue('J7', '6. Target Jilid: 1 - 6 (isi bila metode iqro)');
    $sheet->setCellValue('J8', '7. No Kelas: Lihat tabel kanan →');
    $sheet->setCellValue('J9', '8. Password: Minimal 8 karakter');
    $sheet->setCellValue('J10', '');
    $sheet->setCellValue('J11', 'CATATAN:');
    $sheet->setCellValue('J12', '• Metode quran → isi Target Juz, kosongkan Target Jilid');
    $sheet->setCellValue('J13', '• Metode iqro → isi Target Jilid, kosongkan Target Juz');
    $sheet->setCellValue('J14', '• Baris 2-3 adalah CONTOH, hapus sebelum import');
    $sheet->setCellValue('J15', '• NIS yang mengandung "(Contoh)" akan diabaikan sistem');

    $sheet->getStyle('J1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '2563EB']
        ],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
    ]);
    $sheet->getStyle('J2:J9')->applyFromArray([
        'font' => ['size' => 9, 'color' => ['rgb' => '374151']],
    ]);
    $sheet->getStyle('J11')->applyFromArray([
        'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'DC2626']],
    ]);
    $sheet->getStyle('J12:J15')->applyFromArray([
        'font' => ['size' => 8, 'color' => ['rgb' => '374151']],
    ]);
    $sheet->getStyle('J1:J15')->applyFromArray([
        'borders' => [
            'outline' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                'color' => ['rgb' => '3B82F6']
            ]
        ]
    ]);

    // TABEL KELAS & GURU (kolom L-M)
    $sheet->setCellValue('L1', 'KELAS & GURU');
    $sheet->mergeCells('L1:M1');
    $sheet->getStyle('L1')->applyFromArray([
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

    $sheet->setCellValue('L2', 'No');
    $sheet->setCellValue('M2', 'Kelas - Guru');
    $sheet->getStyle('L2:M2')->applyFromArray([
        'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '6B7280']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ]);

    $currentRow = 3;
    foreach ($kelasList as $index => $kelas) {
        $sheet->setCellValue('L' . $currentRow, $index + 1);
        $guruName = $kelas->guru ? $kelas->guru->name : 'Tanpa Guru';
        $sheet->setCellValue('M' . $currentRow, $kelas->name . ' - ' . $guruName);
        $sheet->getStyle('L' . $currentRow . ':M' . $currentRow)->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '374151']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);
        $currentRow++;
    }

    $lastRowKelas = $currentRow - 1;
    $sheet->getStyle('L1:M' . $lastRowKelas)->applyFromArray([
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

    $this->logExport('santri', "Excel (Export for Edit - Kelas {$kelas->name})");

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Headers (8 kolom: tambah metode, target_juz, target_jilid)
    $headers = ['no_urut', 'nis', 'nama', 'gender', 'metode', 'target_juz', 'target_jilid', 'no_kelas_baru'];
    $sheet->fromArray($headers, NULL, 'A1');

    // Style header (A1:H1)
    $sheet->getStyle('A1:H1')->applyFromArray([
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
        $metodeVal = $santri->target_metode?->value ?? 'quran';

        $sheet->setCellValue('A' . $row, $noUrut);
        $sheet->setCellValue('B' . $row, $santri->nis);
        $sheet->setCellValue('C' . $row, $santri->name);
        $sheet->setCellValue('D' . $row, $santri->gender === 'Laki-laki' ? 'L' : 'P');
        $sheet->setCellValue('E' . $row, $metodeVal);
        $sheet->setCellValue('F' . $row, $metodeVal === 'iqro' ? '' : $santri->target_juz);
        $sheet->setCellValue('G' . $row, $metodeVal === 'iqro' ? $santri->target_jilid : '');
        $sheet->setCellValue('H' . $row, ''); // no_kelas_baru (kosong, isi manual jika pindah kelas)

        // Kolom NO URUT background abu-abu (read-only indicator)
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
    $sheet->getColumnDimension('C')->setWidth(28);  // nama
    $sheet->getColumnDimension('D')->setWidth(8);   // gender
    $sheet->getColumnDimension('E')->setWidth(10);  // metode
    $sheet->getColumnDimension('F')->setWidth(11);  // target_juz
    $sheet->getColumnDimension('G')->setWidth(11);  // target_jilid
    $sheet->getColumnDimension('H')->setWidth(14);  // no_kelas_baru
    $sheet->getColumnDimension('I')->setWidth(2);   // spacing
    $sheet->getColumnDimension('J')->setWidth(46);  // petunjuk
    $sheet->getColumnDimension('K')->setWidth(2);   // spacing
    $sheet->getColumnDimension('L')->setWidth(8);   // no kelas
    $sheet->getColumnDimension('M')->setWidth(35);  // kelas - guru

    // PETUNJUK BULK UPDATE (kolom J)
    $sheet->setCellValue('J1', '📋 PANDUAN BULK UPDATE');
    $sheet->setCellValue('J2', '');
    $sheet->setCellValue('J3', '⚠️ ATURAN PENTING:');
    $sheet->setCellValue('J4', '• JANGAN ubah/hapus kolom NO URUT');
    $sheet->setCellValue('J5', '• JANGAN tambah/hapus baris');
    $sheet->setCellValue('J6', '• Password tidak bisa diubah via Excel');
    $sheet->setCellValue('J7', '');
    $sheet->setCellValue('J8', '📝 METODE & TARGET:');
    $sheet->setCellValue('J9', '• Metode: quran atau iqro');
    $sheet->setCellValue('J10', '• quran → isi Target Juz (0-30), Jilid kosong');
    $sheet->setCellValue('J11', '• iqro → isi Target Jilid (1-6), Juz kosong');
    $sheet->setCellValue('J12', '');
    $sheet->setCellValue('J13', '📝 PINDAH KELAS:');
    $sheet->setCellValue('J14', '• Isi "No Kelas Baru" (lihat tabel kanan →)');
    $sheet->setCellValue('J15', '• Kosongkan jika tidak pindah kelas');
    $sheet->setCellValue('J16', '• NIS harus tetap unik (tidak boleh sama)');

    $sheet->getStyle('J1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '059669']
        ],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
    ]);
    $sheet->getStyle('J3')->applyFromArray([
        'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'DC2626']],
    ]);
    $sheet->getStyle('J8')->applyFromArray([
        'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '2563EB']],
    ]);
    $sheet->getStyle('J13')->applyFromArray([
        'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'D97706']],
    ]);
    $sheet->getStyle('J2:J16')->applyFromArray([
        'font' => ['size' => 8, 'color' => ['rgb' => '374151']],
    ]);
    $sheet->getStyle('J1:J16')->applyFromArray([
        'borders' => [
            'outline' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                'color' => ['rgb' => '059669']
            ]
        ]
    ]);

    // TABEL KELAS & GURU (kolom L-M)
    $kelasList = Kelas::with('guru')->orderBy('id')->get();

    $sheet->setCellValue('L1', 'KELAS & GURU');
    $sheet->mergeCells('L1:M1');
    $sheet->getStyle('L1')->applyFromArray([
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

    $sheet->setCellValue('L2', 'No');
    $sheet->setCellValue('M2', 'Kelas - Guru');
    $sheet->getStyle('L2:M2')->applyFromArray([
        'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '6B7280']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ]);

    $currentRow = 3;
    foreach ($kelasList as $index => $kelasItem) {
        $sheet->setCellValue('L' . $currentRow, $index + 1);
        $guruName = $kelasItem->guru ? $kelasItem->guru->name : 'Tanpa Guru';
        $sheet->setCellValue('M' . $currentRow, $kelasItem->name . ' - ' . $guruName);
        $sheet->getStyle('L' . $currentRow . ':M' . $currentRow)->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '374151']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);
        $currentRow++;
    }

    $lastRowKelas = $currentRow - 1;
    $sheet->getStyle('L1:M' . $lastRowKelas)->applyFromArray([
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