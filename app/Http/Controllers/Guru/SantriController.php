<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\LogActivity;
use App\Models\Santri;
use App\Models\Kelas;
use App\Models\Guru;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\GuruSantriImport;
use App\Imports\GuruSantriBulkUpdate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SantriController extends Controller
{
    public function index()
    {
        $guruUser = Auth::user();
        $guru     = Guru::where('user_id', $guruUser->id)->firstOrFail();
        
        $santris = Santri::with(['kelas', 'guru', 'user'])
            ->where('guru_id', $guru->id)
            ->orderBy('name', 'asc')
            ->paginate(15);
        
        $kelas = Kelas::all();
        
        return view('guru.santri.index', compact('santris', 'kelas', 'guru'));
    }

   public function store(Request $request)
{
    $guruUser = Auth::user();
    $guru     = Guru::where('user_id', $guruUser->id)->firstOrFail();

    $validated = $request->validate([
        'nis'        => 'required|string|unique:santris,nis',
        'name'       => 'required|string|max:255|unique:users,name',
        'gender'     => 'required|in:L,P',
        'target_juz' => 'required|numeric|min:0|max:30',
        'kelas_id'   => 'required|exists:kelas,id',
        'password'   => 'required|string|min:4|confirmed',
    ], [
        'password.min'       => 'Password minimal 4 karakter.',
        'password.confirmed' => 'Konfirmasi password tidak cocok.',
        'nis.unique'         => 'NIS sudah terdaftar.',
        'name.unique'        => 'Nama santri sudah terdaftar, gunakan nama lengkap yang berbeda (contoh: tambahkan nama ayah atau bin/binti).',
    ]);

    return DB::transaction(function () use ($validated, $guru, $request) {
        try {
            $name       = ucwords(strtolower(trim($validated['name'])));
            $genderFull = $validated['gender'] === 'L' ? 'Laki-laki' : 'Perempuan';
            
            $santriUser = User::create([
                'name'           => $name,
                'password'       => Hash::make($validated['password']),
                'plain_password' => $validated['password'],
                'role'           => 'santri',
            ]);
            
            $santri = Santri::create([
                'user_id'    => $santriUser->id,
                'kelas_id'   => $validated['kelas_id'],
                'guru_id'    => $guru->id,
                'nis'        => $validated['nis'],
                'name'       => $name,
                'gender'     => $genderFull,
                'target_juz' => $validated['target_juz'],
            ]);

            try {
                LogActivity::create([
                    'user_id'       => Auth::id(),
                    'role'          => Auth::user()->role,
                    'activity_type' => 'create',
                    'module'        => 'santri',
                    'description'   => "Menambahkan santri: {$santri->name} (NIS: {$santri->nis})",
                    'model_type'    => Santri::class,
                    'model_id'      => $santri->id,
                    'new_values'    => $santri->only(['nis', 'name', 'gender', 'target_juz', 'kelas_id']),
                    'ip_address'    => $request->ip(),
                    'user_agent'    => $request->userAgent(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to log santri create activity: ' . $e->getMessage());
            }

            return redirect()->route('guru.santris.index')
                ->with('success', 'Santri dan akun login berhasil ditambahkan!');

        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] === 1062) {
                return redirect()->back()->withInput()
                    ->with('error', 'Nama santri sudah terdaftar di sistem. Gunakan nama lengkap yang berbeda (contoh: tambahkan nama ayah atau bin/binti).');
            }
            Log::error("Error creating Santri by guru: " . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error("Error creating Santri by guru: " . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    });
}

    public function update(Request $request, Santri $santri)
    {
        $guruUser = Auth::user();
        $guru     = Guru::where('user_id', $guruUser->id)->firstOrFail();
        
        if ($santri->guru_id !== $guru->id) {
            return redirect()->route('guru.santris.index')
                ->with('error', 'Anda tidak memiliki akses untuk mengubah data santri ini!');
        }

        $oldValues = $santri->only(['nis', 'name', 'gender', 'target_juz', 'kelas_id']);

        $validated = $request->validate([
            'nis'        => ['required', 'string', Rule::unique('santris', 'nis')->ignore($santri->id)],
            'name'       => 'required|string|max:255',
            'gender'     => 'required|in:L,P',
            'target_juz' => 'required|numeric|min:0|max:30',
            'kelas_id'   => 'required|exists:kelas,id',
            'password'   => 'nullable|string|min:4',
        ], [
            'password.min' => 'Password minimal 4 karakter.',
        ]);

        return DB::transaction(function () use ($validated, $santri, $oldValues, $request) {
            try {
                $name       = ucwords(strtolower(trim($validated['name'])));
                $genderFull = $validated['gender'] === 'L' ? 'Laki-laki' : 'Perempuan';
                
                $santri->update([
                    'nis'        => $validated['nis'],
                    'name'       => $name,
                    'gender'     => $genderFull,
                    'target_juz' => $validated['target_juz'],
                    'kelas_id'   => $validated['kelas_id'],
                ]);

                if ($santri->user) {
                    $updateData = ['name' => $name];
                    if (!empty($validated['password'])) {
                        $updateData['password']       = Hash::make($validated['password']);
                        $updateData['plain_password']  = $validated['password'];
                    }
                    $santri->user->update($updateData);
                }

                try {
                    LogActivity::create([
                        'user_id'       => Auth::id(),
                        'role'          => Auth::user()->role,
                        'activity_type' => 'update',
                        'module'        => 'santri',
                        'description'   => "Mengubah data santri: {$santri->name} (NIS: {$santri->nis})",
                        'model_type'    => Santri::class,
                        'model_id'      => $santri->id,
                        'old_values'    => $oldValues,
                        'new_values'    => $santri->fresh()->only(array_keys($oldValues)),
                        'ip_address'    => $request->ip(),
                        'user_agent'    => $request->userAgent(),
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to log santri update activity: ' . $e->getMessage());
                }

                return redirect()->route('guru.santris.index')
                    ->with('updated', 'Data santri dan akun login berhasil diperbarui!');
                    
            } catch (\Exception $e) {
                Log::error("Error updating Santri by guru: " . $e->getMessage());
                return redirect()->back()->withInput()
                    ->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
            }
        });
    }

    public function destroy(Santri $santri)
    {
        $guruUser = Auth::user();
        $guru     = Guru::where('user_id', $guruUser->id)->firstOrFail();
        
        if ($santri->guru_id !== $guru->id) {
            return redirect()->route('guru.santris.index')
                ->with('error', 'Anda tidak memiliki akses untuk menghapus data santri ini!');
        }

        $name      = $santri->name;
        $nis       = $santri->nis;
        $oldValues = $santri->only(['nis', 'name', 'gender', 'target_juz', 'kelas_id']);

        try {
            DB::transaction(function () use ($santri) {
                $userId = $santri->user_id;
                $santri->delete();
                if ($userId) {
                    User::where('id', $userId)->delete();
                }
            });

            try {
                LogActivity::create([
                    'user_id'       => Auth::id(),
                    'role'          => Auth::user()->role,
                    'activity_type' => 'delete',
                    'module'        => 'santri',
                    'description'   => "Menghapus santri: {$name} (NIS: {$nis})",
                    'old_values'    => $oldValues,
                    'ip_address'    => request()->ip(),
                    'user_agent'    => request()->userAgent(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to log santri delete activity: ' . $e->getMessage());
            }

            return redirect()->route('guru.santris.index')
                ->with('deleted', 'Santri dan akun login berhasil dihapus!');
                
        } catch (\Exception $e) {
            Log::error("Error deleting Santri by guru: " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menghapus data.');
        }
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        
        $headers = ['nis', 'nama', 'gender', 'target_juz', 'password'];
        $sheet->fromArray($headers, NULL, 'A1');
        
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1F2937']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders'   => ['bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => 'D1D5DB']]]
        ]);
        
        $sheet->getStyle('A2:E3')->applyFromArray([
            'fill'    => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']],
            'font'    => ['size' => 9, 'color' => ['rgb' => '78716C'], 'italic' => true],
            'borders' => ['outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'FDE68A']]]
        ]);
        
        $exampleData = [
            ['12345 (Contoh)', 'Ahmad Zaki', 'L', '5.50', 'pass1234'],
            ['12346 (Contoh)', 'Aisyah Rahmawati', 'P', '3.25', 'pass8765'],
        ];
        foreach ($exampleData as $rowIndex => $row) {
            $actualRow = $rowIndex + 2;
            $sheet->setCellValue('A' . $actualRow, $row[0]);
            $sheet->setCellValue('B' . $actualRow, $row[1]);
            $sheet->setCellValue('C' . $actualRow, $row[2]);
            $sheet->setCellValue('D' . $actualRow, $row[3]);
            $sheet->setCellValue('E' . $actualRow, $row[4]);
        }
        
        $sheet->getColumnDimension('A')->setWidth(16);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(8);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('H')->setWidth(40);
        
        $sheet->setCellValue('H1', 'PETUNJUK IMPORT SANTRI');
        $sheet->setCellValue('H2', '1. NIS: Nomor Induk Santri (unik)');
        $sheet->setCellValue('H3', '2. Nama: Nama lengkap santri');
        $sheet->setCellValue('H4', '3. Gender: L (Laki-laki) atau P (Perempuan)');
        $sheet->setCellValue('H5', '4. Target Juz: 0.00 - 30.00');
        $sheet->setCellValue('H6', '5. Password: Minimal 4 karakter');
        $sheet->setCellValue('H7', '');
        $sheet->setCellValue('H8', 'CATATAN:');
        $sheet->setCellValue('H9', '• Baris 2-3 adalah CONTOH, hapus sebelum import');
        $sheet->setCellValue('H10', '• NIS yang diawali "(Contoh)" akan diabaikan');
        $sheet->setCellValue('H11', '• Santri akan otomatis masuk ke kelas Anda');
        $sheet->setCellValue('H12', '• Pastikan semua data sudah benar sebelum import');
        
        $sheet->getStyle('H1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('H8')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'DC2626']],
        ]);
        $sheet->getStyle('H1:H12')->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => '3B82F6']]]
        ]);
        
        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->freezePane('A2');
        
        $writer   = new Xlsx($spreadsheet);
        $fileName = 'Template_Import_Santri_Guru_' . date('Y-m-d') . '.xlsx';
        
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function exportForEdit()
    {
        $guruUser = Auth::user();
        $guru     = Guru::where('user_id', $guruUser->id)->firstOrFail();
        
        $santris = Santri::with(['kelas', 'guru', 'user'])
            ->where('guru_id', $guru->id)
            ->orderBy('name', 'asc')
            ->get();
        
        if ($santris->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data santri untuk diekspor.');
        }

        try {
            LogActivity::create([
                'user_id'       => Auth::id(),
                'role'          => Auth::user()->role,
                'activity_type' => 'download',
                'module'        => 'santri',
                'description'   => "Export data santri ke Excel (untuk edit) - {$santris->count()} santri",
                'new_values'    => ['total_santri' => $santris->count(), 'guru' => $guru->name],
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log export activity: ' . $e->getMessage());
        }
        
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        
        $headers = ['no_urut', 'nis', 'nama', 'gender', 'target_juz'];
        $sheet->fromArray($headers, NULL, 'A1');
        
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1F2937']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders'   => ['bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => 'D1D5DB']]]
        ]);
        
        $row    = 2;
        $noUrut = 1;
        foreach ($santris as $santri) {
            $sheet->setCellValue('A' . $row, $noUrut);
            $sheet->setCellValue('B' . $row, $santri->nis);
            $sheet->setCellValue('C' . $row, $santri->name);
            $sheet->setCellValue('D' . $row, $santri->gender === 'Laki-laki' ? 'L' : 'P');
            $sheet->setCellValue('E' . $row, $santri->target_juz);
            $sheet->getStyle('A' . $row)->applyFromArray([
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']]
            ]);
            $row++;
            $noUrut++;
        }
        
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(8);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(42);
        
        $sheet->setCellValue('H1', '📋 PANDUAN BULK UPDATE');
        $sheet->setCellValue('H2', '');
        $sheet->setCellValue('H3', '⚠️ ATURAN PENTING:');
        $sheet->setCellValue('H4', '• JANGAN ubah/hapus kolom NO URUT');
        $sheet->setCellValue('H5', '• JANGAN tambah/hapus baris');
        $sheet->setCellValue('H6', '• Kelas TIDAK bisa diubah via Excel');
        $sheet->setCellValue('H7', '• Password tidak bisa diubah via Excel');
        $sheet->setCellValue('H8', '');
        $sheet->setCellValue('H9', '📝 CARA UPDATE:');
        $sheet->setCellValue('H10', '1. Edit data yang ingin diubah');
        $sheet->setCellValue('H11', '2. Upload file Excel yang sudah diedit');
        
        $sheet->getStyle('H1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('H1:H11')->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => '059669']]]
        ]);
        
        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->freezePane('A2');
        
        $writer   = new Xlsx($spreadsheet);
        $fileName = 'Edit_Santri_Guru_' . str_replace(' ', '_', $guru->name) . '_' . date('Y-m-d') . '.xlsx';
        
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls|max:2048']);

        $guruUser = Auth::user();
        $guru     = Guru::where('user_id', $guruUser->id)->firstOrFail();

        try {
            $import = new GuruSantriImport($guru->id);
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $failedCount  = $import->getFailedCount();
            $errors       = $import->getErrors();

            if ($successCount > 0) {
                try {
                    LogActivity::create([
                        'user_id'       => Auth::id(),
                        'role'          => Auth::user()->role,
                        'activity_type' => 'import',
                        'module'        => 'santri',
                        'description'   => "Import {$successCount} data santri via Excel",
                        'new_values'    => ['success' => $successCount, 'failed' => $failedCount],
                        'ip_address'    => $request->ip(),
                        'user_agent'    => $request->userAgent(),
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to log import activity: ' . $e->getMessage());
                }
            }

            if ($failedCount > 0) {
                $errorMessage = "Berhasil: {$successCount} santri. Gagal: {$failedCount} santri.<br><br>";
                $errorMessage .= implode('<br>', $errors);
                return redirect()->route('guru.santris.index')->with('error', $errorMessage);
            }

            return redirect()->route('guru.santris.index')
                ->with('success', "Berhasil mengimport {$successCount} santri!");

        } catch (\Exception $e) {
            Log::error("Error importing santri (guru): " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengimport data: ' . $e->getMessage());
        }
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls|max:2048']);

        $guruUser = Auth::user();
        $guru     = Guru::where('user_id', $guruUser->id)->firstOrFail();

        try {
            $import = new GuruSantriBulkUpdate($guru->id);
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $failedCount  = $import->getFailedCount();
            $errors       = $import->getErrors();

            if ($successCount > 0) {
                try {
                    LogActivity::create([
                        'user_id'       => Auth::id(),
                        'role'          => Auth::user()->role,
                        'activity_type' => 'update',
                        'module'        => 'santri',
                        'description'   => "Bulk update {$successCount} data santri via Excel",
                        'new_values'    => ['success' => $successCount, 'failed' => $failedCount],
                        'ip_address'    => $request->ip(),
                        'user_agent'    => $request->userAgent(),
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to log bulk update activity: ' . $e->getMessage());
                }
            }

            if ($failedCount > 0) {
                $errorMessage = "Berhasil update: {$successCount} santri. Gagal: {$failedCount} santri.<br><br>";
                $errorMessage .= implode('<br>', $errors);
                return redirect()->route('guru.santris.index')->with('error', $errorMessage);
            }

            return redirect()->route('guru.santris.index')
                ->with('updated', "Berhasil mengupdate {$successCount} data santri!");

        } catch (\Exception $e) {
            Log::error("Error bulk updating santri (guru): " . $e->getMessage());
            return redirect()->route('guru.santris.index')
                ->with('error', 'Gagal mengupdate data: ' . $e->getMessage());
        }
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'santri_ids'   => 'required|array|min:1',
            'santri_ids.*' => 'exists:santris,id',
        ]);

        $guruUser = Auth::user();
        $guru     = Guru::where('user_id', $guruUser->id)->firstOrFail();

        try {
            $deletedCount = 0;
            $santriNames  = [];
            
            DB::transaction(function () use ($request, $guru, &$deletedCount, &$santriNames) {
                $santris = Santri::with('user')
                    ->whereIn('id', $request->santri_ids)
                    ->where('guru_id', $guru->id)
                    ->get();
                
                foreach ($santris as $santri) {
                    $santriNames[] = $santri->name;
                    if ($santri->user) {
                        $santri->user->delete();
                    }
                    $santri->delete();
                    $deletedCount++;
                }
            });

            if ($deletedCount === 0) {
                return redirect()->route('guru.santris.index')
                    ->with('error', 'Tidak ada santri yang berhasil dihapus!');
            }

            try {
                LogActivity::create([
                    'user_id'       => Auth::id(),
                    'role'          => Auth::user()->role,
                    'activity_type' => 'delete',
                    'module'        => 'santri',
                    'description'   => "Bulk delete {$deletedCount} santri: " . implode(', ', array_slice($santriNames, 0, 3)) . ($deletedCount > 3 ? '...' : ''),
                    'old_values'    => ['total' => $deletedCount, 'names' => $santriNames],
                    'ip_address'    => $request->ip(),
                    'user_agent'    => $request->userAgent(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to log bulk delete activity: ' . $e->getMessage());
            }

            return redirect()->route('guru.santris.index')
                ->with('deleted', "Berhasil menghapus {$deletedCount} santri!");
                
        } catch (\Exception $e) {
            Log::error("Error bulk deleting Santri (guru): " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}