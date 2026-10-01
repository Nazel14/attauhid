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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use App\Enums\MetodeTarget;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\GuruSantriImport;
use App\Imports\GuruSantriBulkUpdate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SantriController extends Controller
{
    use \App\Traits\UsesKelasAktif;
    use \App\Traits\PenghapusanSantri;

    public function index()
{
    $guruUser = Auth::user();
    $guru     = Guru::where('user_id', $guruUser->id)->firstOrFail();

    $kelasAktifId = session('kelas_aktif_id');

    $santris = Santri::with(['kelas', 'guru', 'user'])
        ->where('guru_id', $guru->id)
        ->where('kelas_id', $kelasAktifId)
        ->orderBy('name', 'asc')
        ->paginate(15)
        ->withQueryString();

    // Semua kelas guru tetap dikirim supaya santri masih bisa DIPINDAH antar kelas
    // lewat form edit. Daftar di atas tetap hanya kelas yang sedang aktif.
    $kelasList = Kelas::where('guru_id', $guru->id)->orderBy('name', 'asc')->get();

    $targetMetodeOptions = collect(MetodeTarget::cases())->map(fn ($case) => [
        'value' => $case->value,
        'label' => ucfirst($case->value),
    ])->values();

    return Inertia::render('Guru/Santri/Index', [
        'santris' => $santris,
        'kelasList' => $kelasList,
        'kelasAktifId' => $kelasAktifId,
        'targetMetodeOptions' => $targetMetodeOptions,
        'guru' => $guru,
    ]);
}

   public function store(Request $request)
{
    $guruUser = Auth::user();
    $guru     = Guru::where('user_id', $guruUser->id)->firstOrFail();

    $validated = $request->validate([
        'nis'           => 'required|string|unique:santris,nis',
        'name'          => 'required|string|max:255',
        'gender'        => 'required|in:L,P',
       'target_metode'   => ['required', Rule::enum(MetodeTarget::class)],
        'target_metode_2' => ['nullable', 'in:quran', 'prohibited_if:target_metode,quran'],
        'target_juz'      => ['nullable', 'numeric', 'min:0', 'max:30', 'required_if:target_metode,quran', 'required_if:target_metode_2,quran'],
        'target_jilid'    => ['nullable', 'required_if:target_metode,iqro,aisar', 'integer', 'min:1', 'max:6'],
        'kelas_id'      => ['required', Rule::exists('kelas', 'id')->where('guru_id', $guru->id)],
        'password'      => 'required|string|min:8|confirmed',
        'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
    ], [
        'password.min'       => 'Password minimal 8 karakter.',
        'password.confirmed' => 'Konfirmasi password tidak cocok.',
        'nis.unique'         => 'NIS sudah terdaftar.',
        'kelas_id.exists'    => 'Kelas tidak valid — Anda hanya bisa menambahkan santri ke kelas Anda sendiri.',
    ]);

    return DB::transaction(function () use ($validated, $guru, $request) {
        try {
            $name       = ucwords(strtolower(trim($validated['name'])));
            $genderFull = $validated['gender'] === 'L' ? 'Laki-laki' : 'Perempuan';

            // Null-kan field yang tidak dipakai metode terpilih
            if ($validated['target_metode'] === 'quran') {
                // Al-Qur'an sebagai metode 1 → tidak ada metode 2
                $validated['target_jilid']    = null;
                $validated['target_metode_2'] = null;
            } else {
                // Iqro/Aisar metode 1: juz hanya dipakai bila metode 2 = quran
                if (($validated['target_metode_2'] ?? null) !== 'quran') {
                    $validated['target_metode_2'] = null;
                    $validated['target_juz']      = null;
                }
            }
            
            $santriUser = User::create([
                'name'           => $name,
                'password'       => Hash::make($validated['password']),
                'plain_password' => $validated['password'],
                'role'           => 'santri',
            ]);

            if ($request->hasFile('profile_photo')) {
                $foto = $request->file('profile_photo');
                $santriUser->profile_photo      = $this->processProfilePhoto($foto);
                $santriUser->profile_photo_name = $foto->getClientOriginalName();
                $santriUser->save();
            }
            
            $santri = Santri::create([
                'user_id'       => $santriUser->id,
                'kelas_id'      => $validated['kelas_id'],
                'guru_id'       => $guru->id,
                'nis'           => $validated['nis'],
                'name'          => $name,
                'gender'        => $genderFull,
                'target_metode' => $validated['target_metode'],
                'target_metode_2' => $validated['target_metode_2'] ?? null,
                'target_juz'    => $validated['target_juz'] ?? null,
                'target_jilid'  => $validated['target_jilid'] ?? null,
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
        
        if ($santri->guru_id !== $guru->id
            || (int) $santri->kelas_id !== (int) session('kelas_aktif_id')) {
            return redirect()->route('guru.santris.index')
                ->with('error', 'Santri ini tidak ada di kelas yang sedang aktif.');
        }

        $oldValues = $santri->only(['nis', 'name', 'gender', 'target_juz', 'kelas_id']);
        $validated = $request->validate([
                    'nis'           => ['required', 'string', Rule::unique('santris', 'nis')->ignore($santri->id)],
                    'name'          => 'required|string|max:255',
                    'gender'        => 'required|in:L,P',
                    'target_metode'   => ['required', Rule::enum(MetodeTarget::class)],
                    'target_metode_2' => ['nullable', 'in:quran', 'prohibited_if:target_metode,quran'],
                    'target_juz'      => ['nullable', 'numeric', 'min:0', 'max:30', 'required_if:target_metode,quran', 'required_if:target_metode_2,quran'],
                    'target_jilid'    => ['nullable', 'required_if:target_metode,iqro,aisar', 'integer', 'min:1', 'max:6'],                    'kelas_id'      => ['required', Rule::exists('kelas', 'id')->where('guru_id', $guru->id)],
                    'password'      => 'nullable|string|min:8',
                    'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                ], [
                    'password.min'    => 'Password minimal 8 karakter.',
                    'kelas_id.exists' => 'Kelas tidak valid — Anda hanya bisa memindahkan santri di dalam kelas Anda sendiri.',
                ]);

        return DB::transaction(function () use ($validated, $santri, $oldValues, $request) {
            try {
                $name       = ucwords(strtolower(trim($validated['name'])));
                $genderFull = $validated['gender'] === 'L' ? 'Laki-laki' : 'Perempuan';

                // Null-kan field yang tidak dipakai metode terpilih
                if ($validated['target_metode'] === 'quran') {
                    $validated['target_jilid']    = null;
                    $validated['target_metode_2'] = null;
                } else {
                    if (($validated['target_metode_2'] ?? null) !== 'quran') {
                        $validated['target_metode_2'] = null;
                        $validated['target_juz']      = null;
                    }
                }
                
                $santri->update([
                    'nis'           => $validated['nis'],
                    'name'          => $name,
                    'gender'        => $genderFull,
                    'target_metode' => $validated['target_metode'],
                     'target_metode_2' => $validated['target_metode_2'] ?? null,
                    'target_juz'    => $validated['target_juz'] ?? null,
                    'target_jilid'  => $validated['target_jilid'] ?? null,
                    'kelas_id'      => $validated['kelas_id'],
                ]);

                if ($santri->user) {
                    $updateData = ['name' => $name];
                    if (!empty($validated['password'])) {
                        $updateData['password']       = Hash::make($validated['password']);
                        $updateData['plain_password']  = $validated['password'];
                    }
                    $santri->user->update($updateData);

                    // Foto profil (hapus / ganti)
                    if ($request->boolean('delete_photo') && $santri->user->profile_photo) {
                        if (Storage::disk('public')->exists($santri->user->profile_photo)) {
                            Storage::disk('public')->delete($santri->user->profile_photo);
                        }
                        $santri->user->profile_photo      = null;
                        $santri->user->profile_photo_name = null;
                        $santri->user->save();
                    }
                    if ($request->hasFile('profile_photo')) {
                        if ($santri->user->profile_photo && Storage::disk('public')->exists($santri->user->profile_photo)) {
                            Storage::disk('public')->delete($santri->user->profile_photo);
                        }
                        $foto = $request->file('profile_photo');
                        $santri->user->profile_photo      = $this->processProfilePhoto($foto);
                        $santri->user->profile_photo_name = $foto->getClientOriginalName();
                        $santri->user->save();
                    }
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
                    ->with('success', 'Data santri dan akun login berhasil diperbarui!');
                    
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
        
        if ($santri->guru_id !== $guru->id
            || (int) $santri->kelas_id !== (int) session('kelas_aktif_id')) {
            return redirect()->route('guru.santris.index')
                ->with('error', 'Santri ini tidak ada di kelas yang sedang aktif.');
        }

        // Diperiksa SEBELUM mencoba menghapus. Menangkap error foreign key
        // setelahnya tetap mungkin, tapi pesannya tidak pernah bisa menyebut
        // tagihan mana yang menahan — dan transaksinya sudah terlanjur gagal.
        $terkunci = $this->tagihanPerSantri([$santri->id]);

        if (! empty($terkunci)) {
            return redirect()->back()->with(
                'error',
                $this->pesanSantriTerkunci($santri->name, (int) reset($terkunci))
            );
        }

        $name      = $santri->name;
        $nis       = $santri->nis;
        $oldValues = $santri->only(['nis', 'name', 'gender', 'target_juz', 'kelas_id']);

        try {
            DB::transaction(function () use ($santri) {
                $userId = $santri->user_id;
                if ($santri->user && $santri->user->profile_photo && Storage::disk('public')->exists($santri->user->profile_photo)) {
                    Storage::disk('public')->delete($santri->user->profile_photo);
                }
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
                ->with('success', 'Santri dan akun login berhasil dihapus!');
                
        } catch (\Exception $e) {
            Log::error("Error deleting Santri by guru: " . $e->getMessage());
            return redirect()->back()->with('error', $this->pesanGagalHapusSantri($e, $name));
        }
    }

 public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        // 9 kolom: tambah metode_2 (opsional, "quran")
        $headers = ['nis', 'nama', 'gender', 'metode', 'metode_2', 'target_juz', 'target_jilid', 'password', 'nama_file_foto'];
        $sheet->fromArray($headers, NULL, 'A1');

        $sheet->getStyle('A1:I1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1F2937']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders'   => ['bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => 'D1D5DB']]]
        ]);

        $sheet->getStyle('A2:I4')->applyFromArray([
            'fill'    => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']],
            'font'    => ['size' => 9, 'color' => ['rgb' => '78716C'], 'italic' => true],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'FDE68A']]]
        ]);

        // Contoh: quran, iqro, dan aisar-DUAL (aisar + target Al-Qur'an)
        $exampleData = [
            ['12345 (Contoh)', 'Ahmad',  'L', 'quran', '',      '5.50', '',  'pass1234', 'ahmad.jpg'],
            ['12346 (Contoh)', 'Aisyah', 'P', 'iqro',  '',      '',     '3', 'pass8765', 'aisyah.jpg'],
            ['12347 (Contoh)', 'Bilal',  'L', 'aisar', 'quran', '2.00', '1', 'pass5678', 'bilal.jpg'],
        ];
        foreach ($exampleData as $rowIndex => $row) {
            $actualRow = $rowIndex + 2;
            $col = 'A';
            foreach ($row as $val) {
                $sheet->setCellValue($col . $actualRow, $val);
                $col++;
            }
        }

        // Area input baris 5-104: putih bergaris (cegah Excel auto-extend format contoh)
        $sheet->getStyle('A5:I104')->applyFromArray([
            'fill'    => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
            'font'    => ['italic' => false, 'bold' => false, 'size' => 10, 'color' => ['rgb' => '111827']],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]]
        ]);

        $sheet->getColumnDimension('A')->setWidth(16);  // nis
        $sheet->getColumnDimension('B')->setWidth(28);  // nama
        $sheet->getColumnDimension('C')->setWidth(8);   // gender
        $sheet->getColumnDimension('D')->setWidth(10);  // metode
        $sheet->getColumnDimension('E')->setWidth(10);  // metode_2
        $sheet->getColumnDimension('F')->setWidth(11);  // target_juz
        $sheet->getColumnDimension('G')->setWidth(11);  // target_jilid
        $sheet->getColumnDimension('H')->setWidth(16);  // password
        $sheet->getColumnDimension('I')->setWidth(20);  // nama_file_foto
        $sheet->getColumnDimension('J')->setWidth(2);   // spacing
        $sheet->getColumnDimension('K')->setWidth(48);  // petunjuk

        $sheet->setCellValue('K1', 'PETUNJUK IMPORT SANTRI');
        $sheet->setCellValue('K2', '1. NIS: Nomor Induk Santri (unik)');
        $sheet->setCellValue('K3', '2. Nama: Nama lengkap santri');
        $sheet->setCellValue('K4', '3. Gender: L (Laki-laki) atau P (Perempuan)');
        $sheet->setCellValue('K5', '4. Metode: quran, iqro, atau aisar');
        $sheet->setCellValue('K6', '5. Metode 2: kosongkan, atau "quran" (hanya jika metode iqro/aisar)');
        $sheet->setCellValue('K7', '6. Target Juz: 0.00 - 30.00');
        $sheet->setCellValue('K8', '7. Target Jilid: 1 - 6 (isi bila metode iqro/aisar)');
        $sheet->setCellValue('K9', '8. Password: Minimal 4 karakter');
        $sheet->setCellValue('K10', '9. Nama File Foto: tanpa spasi, mis: ahmad.jpg (opsional)');
        $sheet->setCellValue('K11', '');
        $sheet->setCellValue('K12', 'CATATAN:');
        $sheet->setCellValue('K13', '• Metode quran → isi Target Juz. Metode 2 & Jilid kosong.');
        $sheet->setCellValue('K14', '• Metode iqro / aisar → isi Target Jilid. Juz kosong.');
        $sheet->setCellValue('K15', '• DUAL: metode iqro/aisar + Metode 2 = quran →');
        $sheet->setCellValue('K16', '   isi Target Jilid DAN Target Juz sekaligus (lihat contoh Bilal).');
        $sheet->setCellValue('K17', '• Foto diupload terpisah sebagai 1 ZIP saat import');
        $sheet->setCellValue('K18', '• Baris 2-4 adalah CONTOH, hapus sebelum import');
        $sheet->setCellValue('K19', '• NIS yang mengandung "(Contoh)" akan diabaikan');
        $sheet->setCellValue('K20', '• Santri akan otomatis masuk ke kelas Anda');

        $sheet->getStyle('K1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('K2:K10')->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '374151']],
        ]);
        $sheet->getStyle('K12')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'DC2626']],
        ]);
        $sheet->getStyle('K13:K20')->applyFromArray([
            'font' => ['size' => 8, 'color' => ['rgb' => '374151']],
        ]);
        $sheet->getStyle('K1:K20')->applyFromArray([
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
            ->where('kelas_id', session('kelas_aktif_id'))
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

        // 9 kolom: tambah metode_2
        $headers = ['no_urut', 'nis', 'nama', 'gender', 'metode', 'metode_2', 'target_juz', 'target_jilid', 'nama_file_foto'];
        $sheet->fromArray($headers, NULL, 'A1');

        $sheet->getStyle('A1:I1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1F2937']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders'   => ['bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => 'D1D5DB']]]
        ]);

        $row    = 2;
        $noUrut = 1;
        foreach ($santris as $santri) {
            $metodeVal    = $santri->target_metode?->value ?? 'quran';
            $isJilidBased = in_array($metodeVal, ['iqro', 'aisar'], true);
            $hasQuran2    = ($santri->target_metode_2?->value ?? '') === 'quran';

            $sheet->setCellValue('A' . $row, $noUrut);
            $sheet->setCellValue('B' . $row, $santri->nis);
            $sheet->setCellValue('C' . $row, $santri->name);
            $sheet->setCellValue('D' . $row, $santri->gender === 'Laki-laki' ? 'L' : 'P');
            $sheet->setCellValue('E' . $row, $metodeVal);
            $sheet->setCellValue('F' . $row, $hasQuran2 ? 'quran' : '');
            // target_juz terisi bila quran (utama) ATAU dual (sekunder)
            $sheet->setCellValue('G' . $row, (!$isJilidBased || $hasQuran2) ? $santri->target_juz : '');
            $sheet->setCellValue('H' . $row, $isJilidBased ? $santri->target_jilid : '');

            // nama_file_foto: nama file asli saat diupload
            $fotoSaran = '';
            if ($santri->user && $santri->user->profile_photo) {
                $fotoSaran = $santri->user->profile_photo_name ?: basename($santri->user->profile_photo);
            }
            $sheet->setCellValue('I' . $row, $fotoSaran);

            $sheet->getStyle('A' . $row)->applyFromArray([
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']]
            ]);
            $row++;
            $noUrut++;
        }

        $sheet->getColumnDimension('A')->setWidth(10);  // no_urut
        $sheet->getColumnDimension('B')->setWidth(16);  // nis
        $sheet->getColumnDimension('C')->setWidth(28);  // nama
        $sheet->getColumnDimension('D')->setWidth(8);   // gender
        $sheet->getColumnDimension('E')->setWidth(10);  // metode
        $sheet->getColumnDimension('F')->setWidth(10);  // metode_2
        $sheet->getColumnDimension('G')->setWidth(11);  // target_juz
        $sheet->getColumnDimension('H')->setWidth(11);  // target_jilid
        $sheet->getColumnDimension('I')->setWidth(20);  // nama_file_foto
        $sheet->getColumnDimension('J')->setWidth(2);   // spacing
        $sheet->getColumnDimension('K')->setWidth(48);  // petunjuk

        $sheet->setCellValue('K1', '📋 PANDUAN BULK UPDATE');
        $sheet->setCellValue('K2', '');
        $sheet->setCellValue('K3', '⚠️ ATURAN PENTING:');
        $sheet->setCellValue('K4', '• JANGAN ubah/hapus kolom NO URUT');
        $sheet->setCellValue('K5', '• JANGAN tambah/hapus baris');
        $sheet->setCellValue('K6', '• Kelas TIDAK bisa diubah via Excel');
        $sheet->setCellValue('K7', '• Password tidak bisa diubah via Excel');
        $sheet->setCellValue('K8', '');
        $sheet->setCellValue('K9', '📝 METODE & TARGET:');
        $sheet->setCellValue('K10', '• Metode: quran, iqro, atau aisar');
        $sheet->setCellValue('K11', '• Metode 2: kosong atau "quran" (hanya untuk iqro/aisar)');
        $sheet->setCellValue('K12', '• quran → isi Target Juz (0-30), Jilid kosong');
        $sheet->setCellValue('K13', '• iqro/aisar → isi Target Jilid (1-6), Juz kosong');
        $sheet->setCellValue('K14', '• DUAL (iqro/aisar + Metode 2 quran) → isi Jilid DAN Juz');
        $sheet->setCellValue('K15', '');
        $sheet->setCellValue('K16', '📷 FOTO:');
        $sheet->setCellValue('K17', '• Kolom nama_file_foto = nama file foto SAAT INI');
        $sheet->setCellValue('K18', '• Biarkan apa adanya = foto tidak diubah');
        $sheet->setCellValue('K19', '• Ganti dgn nama file lain + upload ZIP = foto diganti');
        $sheet->setCellValue('K20', '• Tulis HAPUS = foto dihapus jadi tanpa foto');

        $sheet->getStyle('K1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('K3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'DC2626']],
        ]);
        $sheet->getStyle('K9')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '2563EB']],
        ]);
        $sheet->getStyle('K16')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '7C3AED']],
        ]);
        $sheet->getStyle('K2:K20')->applyFromArray([
            'font' => ['size' => 8, 'color' => ['rgb' => '374151']],
        ]);
        $sheet->getStyle('K1:K20')->applyFromArray([
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
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:2048',
            'photos_zip' => 'nullable|file|mimes:zip|max:20480',
        ]);

        $guruUser = Auth::user();
        $guru     = Guru::where('user_id', $guruUser->id)->firstOrFail();

        try {
            [$photos, $tmpDir] = $this->extractPhotosZip($request->file('photos_zip'));

            $import = new GuruSantriImport($guru->id, $this->kelasAktifId(), $photos);
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $failedCount  = $import->getFailedCount();
            $errors       = $import->getErrors();
            $warnings     = $import->getWarnings();

            $this->cleanupTmp($tmpDir);

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
                $errorMessage .= implode('<br>', array_merge($errors, $warnings));
                return redirect()->route('guru.santris.index')->with('error', $errorMessage);
            }

            $msg = "Berhasil mengimport {$successCount} santri!";
            if (!empty($warnings)) {
                $msg .= "<br><br><strong>Catatan foto:</strong><br>" . implode('<br>', $warnings);
            }

            return redirect()->route('guru.santris.index')->with('success', $msg);

        } catch (\Exception $e) {
            Log::error("Error importing santri (guru): " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengimport data: ' . $e->getMessage());
        }
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:2048',
            'photos_zip' => 'nullable|file|mimes:zip|max:20480',
        ]);

        $guruUser = Auth::user();
        $guru     = Guru::where('user_id', $guruUser->id)->firstOrFail();

        try {
            [$photos, $tmpDir] = $this->extractPhotosZip($request->file('photos_zip'));

            $import = new GuruSantriBulkUpdate($guru->id, $this->kelasAktifId(), $photos);
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $failedCount  = $import->getFailedCount();
            $errors       = $import->getErrors();
            $warnings     = $import->getWarnings();

            $this->cleanupTmp($tmpDir);

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
                $errorMessage .= implode('<br>', array_merge($errors, $warnings));
                return redirect()->route('guru.santris.index')->with('error', $errorMessage);
            }

            $msg = "Berhasil mengupdate {$successCount} data santri!";
            if (!empty($warnings)) {
                $msg .= "<br><br><strong>Catatan foto:</strong><br>" . implode('<br>', $warnings);
            }

            return redirect()->route('guru.santris.index')->with('success', $msg);

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
            $dilewati     = [];

            $terkunci = $this->tagihanPerSantri($request->santri_ids);

            DB::transaction(function () use ($request, $guru, $terkunci, &$deletedCount, &$santriNames, &$dilewati) {
                $santris = Santri::with('user')
                    ->whereIn('id', $request->santri_ids)
                    ->where('guru_id', $guru->id)
                    ->where('kelas_id', session('kelas_aktif_id'))
                    ->get();
                
                foreach ($santris as $santri) {
                    // Dilewati, bukan menggagalkan seluruh transaksi. Satu
                    // santri bertagihan tidak boleh membatalkan penghapusan
                    // sembilan lainnya yang sebenarnya tidak bermasalah.
                    if (isset($terkunci[$santri->id])) {
                        $dilewati[] = $santri->name;
                        continue;
                    }

                    $santriNames[] = $santri->name;
                    if ($santri->user) {
                        if ($santri->user->profile_photo && Storage::disk('public')->exists($santri->user->profile_photo)) {
                            Storage::disk('public')->delete($santri->user->profile_photo);
                        }
                        $santri->user->delete();
                    }
                    $santri->delete();
                    $deletedCount++;
                }
            });

            if ($deletedCount === 0) {
                return redirect()->route('guru.santris.index')->with(
                    'error',
                    $dilewati
                        ? 'Tidak ada santri yang dihapus. ' . $this->pesanSantriDilewati($dilewati)
                        : 'Tidak ada santri yang berhasil dihapus.'
                );
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

            $pesan = "Berhasil menghapus {$deletedCount} santri!";

            if ($dilewati) {
                $pesan .= '<br><br>' . $this->pesanSantriDilewati($dilewati);
            }

            return redirect()->route('guru.santris.index')->with('success', $pesan);
                
        } catch (\Exception $e) {
            Log::error("Error bulk deleting Santri (guru): " . $e->getMessage());
            return redirect()->back()->with('error', $this->pesanGagalHapusSantri($e, 'santri terpilih'));
        }
    }

    private function extractPhotosZip($zipFile): array
    {
        if (!$zipFile) return [[], null];
        if (!class_exists('ZipArchive')) return [[], null];

        $tmpDir = storage_path('app/tmp_photos_' . uniqid());
        @mkdir($tmpDir, 0755, true);

        $zip = new \ZipArchive();
        if ($zip->open($zipFile->getRealPath()) !== true) {
            return [[], $tmpDir];
        }
        $zip->extractTo($tmpDir);
        $zip->close();

        $photos = [];
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tmpDir, \FilesystemIterator::SKIP_DOTS));
        foreach ($rii as $f) {
            if (!$f->isFile()) continue;
            $ext = strtolower($f->getExtension());
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $key = strtolower(str_replace(' ', '_', $f->getFilename()));
                $photos[$key] = $f->getPathname();
            }
        }

        return [$photos, $tmpDir];
    }

    private function cleanupTmp($tmpDir): void
    {
        if (!$tmpDir || !is_dir($tmpDir)) return;
        $rii = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tmpDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($rii as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        @rmdir($tmpDir);
    }

    private function processProfilePhoto($file): string
    {
        $maxSize = 400;
        $path = $file->store('profile-photos', 'public');
        $fullPath = Storage::disk('public')->path($path);

        $info = getimagesize($fullPath);
        if (!$info) return $path;

        [$width, $height] = $info;
        $src = match ($info['mime']) {
            'image/jpeg' => imagecreatefromjpeg($fullPath),
            'image/png'  => imagecreatefrompng($fullPath),
            default      => null,
        };
        if (!$src) return $path;

        $min  = min($width, $height);
        $srcX = (int) (($width - $min) / 2);
        $srcY = ($height > $width) ? 0 : (int) (($height - $min) / 2);
        $size = min($min, $maxSize);
        $dst  = imagecreatetruecolor($size, $size);
        imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $size, $size, $min, $min);
        imagejpeg($dst, $fullPath, 95);
        imagedestroy($src);
        imagedestroy($dst);

        return $path;
    }
}