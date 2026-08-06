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
use Inertia\Inertia;

class SantriController extends Controller
{
    use LogsActivity;

    /**
     * Label metode untuk tampilan (ucfirst quran → Al-Qur'an dsb bisa disesuaikan).
     */
    private function metodeLabel(string $value): string
    {
        return match ($value) {
            'quran' => "Al-Qur'an",
            'iqro'  => 'Iqro',
            'aisar' => 'Aisar',
            default => ucfirst($value),
        };
    }

    public function index()
    {
        $kelasWithStats = Kelas::withCount('santris')
            ->with(['guru', 'santris' => function ($query) {
                $query->select('kelas_id', 'gender', DB::raw('count(*) as total'))
                    ->groupBy('kelas_id', 'gender');
            }])
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($kelas) {
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

        return Inertia::render('Admin/Santri/Index', [
            'kelasWithStats' => $kelasWithStats,
        ]);
    }

    public function detail($kelasId)
    {
        $kelas = Kelas::with('guru')->findOrFail($kelasId);

        $santris = Santri::with(['kelas', 'guru', 'user'])
            ->where('kelas_id', $kelasId)
            ->orderBy('name', 'asc')
            ->paginate(15)
            ->withQueryString();

        $allKelas = Kelas::with('guru')->orderBy('name', 'asc')->get();

        $hasGenderMismatch = $kelas->hasGenderMismatch();
        $mismatchedSantris = $hasGenderMismatch ? $kelas->getMismatchedSantris() : collect();

        $targetMetodeOptions = collect(MetodeTarget::cases())->map(fn ($case) => [
            'value' => $case->value,
            'label' => $this->metodeLabel($case->value),
        ])->values();

        // Jumlah jilid per metode dari config (fallback 6)
        $jilidMaxPerMetode = [
            'iqro'  => (int) config('iqro.max_jilid', 6),
            'aisar' => (int) config('aisar.max_jilid', 6),
        ];

        // Dipakai frontend untuk menampilkan sisa daya tampung di form.
        $kelas->loadCount('santris');

        return Inertia::render('Admin/Santri/Detail', [
            'kelas' => $kelas,
            'santris' => $santris,
            'allKelas' => $allKelas,
            'hasGenderMismatch' => $hasGenderMismatch,
            'mismatchedSantris' => $mismatchedSantris,
            'targetMetodeOptions' => $targetMetodeOptions,
            'jilidMaxPerMetode' => $jilidMaxPerMetode,
        ]);
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
            'target_metode'   => ['required', Rule::enum(MetodeTarget::class)],
            'target_metode_2' => ['nullable', 'in:quran', 'prohibited_if:target_metode,quran'],
            'target_juz'      => ['nullable', 'numeric', 'min:0', 'max:30', 'required_if:target_metode,quran', 'required_if:target_metode_2,quran'],
            'target_jilid'    => ['nullable', 'required_if:target_metode,iqro,aisar', 'integer', 'min:1', 'max:6'],
            'kelas_id' => 'required|exists:kelas,id',
            'password' => 'required|string|min:8|confirmed',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            // Data wali dipakai modul keuangan untuk penagihan.
            'parent_name' => 'required|string|max:255',
            'parent_contact' => 'required|string|max:20',
            'student_type' => 'required|in:Reguler,Fullday',
        ], [
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'nis.unique' => 'NIS sudah terdaftar.',
            'target_jilid.required_if' => 'Target Jilid wajib diisi untuk metode Iqro / Aisar.',
            'parent_name.required' => 'Nama orang tua/wali wajib diisi.',
            'parent_contact.required' => 'Nomor kontak wali wajib diisi — dipakai untuk penagihan.',
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
                $name = preg_replace_callback('/\b\w+/', function ($matches) {
                    return ucfirst(strtolower($matches[0]));
                }, trim($validated['name']));

                // Null-kan field yang tidak dipakai metode terpilih.
                // quran → target_juz; iqro/aisar → target_jilid (+ target_juz bila metode 2 = quran)
                if ($validated['target_metode'] === 'quran') {
                    $validated['target_jilid']    = null;
                    $validated['target_metode_2'] = null;
                } else {
                    if (($validated['target_metode_2'] ?? null) !== 'quran') {
                        $validated['target_metode_2'] = null;
                        $validated['target_juz']      = null;
                    }
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

                // ✅ Upload foto profil jika ada
                if (request()->hasFile('profile_photo')) {
                    $foto = request()->file('profile_photo');
                    $santriUser->profile_photo      = $this->processProfilePhoto($foto);
                    $santriUser->profile_photo_name = $foto->getClientOriginalName();
                    $santriUser->save();
                }

                // Angkatan diambil dari tahun awal TA kelas (2026/2027 → 2026),
                // supaya bendahara tidak perlu mengisinya manual.
                $angkatan = (int) substr((string) $kelas->tahun_ajaran, 0, 4) ?: (int) date('Y');

                $santri = Santri::create([
                    'user_id' => $santriUser->id,
                    'kelas_id' => $validated['kelas_id'],
                    'guru_id' => $guruId,
                    'nis' => $validated['nis'],
                    'name' => $name,
                    'gender' => $genderFull,
                    'target_metode'   => $validated['target_metode'],
                    'target_metode_2' => $validated['target_metode_2'] ?? null,
                    'target_juz'      => $validated['target_juz'] ?? null,
                    'target_jilid'    => $validated['target_jilid'] ?? null,
                    // --- data untuk modul keuangan ---
                    'parent_name'    => $validated['parent_name'],
                    'parent_contact' => $validated['parent_contact'],
                    'student_type'   => $validated['student_type'],
                    'angkatan'       => $angkatan,
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
            'target_metode'   => ['required', Rule::enum(MetodeTarget::class)],
            'target_metode_2' => ['nullable', 'in:quran', 'prohibited_if:target_metode,quran'],
            'target_juz'      => ['nullable', 'numeric', 'min:0', 'max:30', 'required_if:target_metode,quran', 'required_if:target_metode_2,quran'],
            'target_jilid'    => ['nullable', 'required_if:target_metode,iqro,aisar', 'integer', 'min:1', 'max:6'],
            'kelas_id' => 'required|exists:kelas,id',
            'password' => 'nullable|string|min:8',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'parent_name' => 'required|string|max:255',
            'parent_contact' => 'required|string|max:20',
            'student_type' => 'required|in:Reguler,Fullday',
        ], [
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'target_jilid.required_if' => 'Target Jilid wajib diisi untuk metode Iqro / Aisar.',
            'parent_name.required' => 'Nama orang tua/wali wajib diisi.',
            'parent_contact.required' => 'Nomor kontak wali wajib diisi — dipakai untuk penagihan.',
        ]);

        $oldValues = $santri->only(['nis', 'name', 'gender', 'target_juz', 'kelas_id', 'guru_id', 'parent_name', 'parent_contact', 'student_type']);

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

        // Kapasitas hanya dicek bila santri benar-benar pindah kelas.
        if ((int) $validated['kelas_id'] !== (int) $santri->kelas_id) {
            $terisi = $kelas->santris()->count();
            if ($terisi >= $kelas->kapasitas) {
                return redirect()->back()->withInput()
                    ->with('error', "Kelas {$kelas->name} sudah penuh ({$terisi}/{$kelas->kapasitas}). Tambah kapasitasnya dulu.");
            }
        }

        return DB::transaction(function () use ($validated, $santri, $oldValues) {
            try {
                $name = preg_replace_callback('/\b\w+/', function ($matches) {
                    return ucfirst(strtolower($matches[0]));
                }, trim($validated['name']));

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

                $genderFull = $validated['gender'] === 'L' ? 'Laki-laki' : 'Perempuan';
                $kelas = Kelas::findOrFail($validated['kelas_id']);
                $guruId = $kelas->guru_id;

                // Update data santri
                $santri->update([
                    'nis' => $validated['nis'],
                    'name' => $name,
                    'gender' => $genderFull,
                    'target_metode'   => $validated['target_metode'],
                    'target_metode_2' => $validated['target_metode_2'] ?? null,
                    'target_juz'      => $validated['target_juz'] ?? null,
                    'target_jilid'    => $validated['target_jilid'] ?? null,
                    'kelas_id' => $validated['kelas_id'],
                    'guru_id' => $guruId,
                    'parent_name'    => $validated['parent_name'],
                    'parent_contact' => $validated['parent_contact'],
                    'student_type'   => $validated['student_type'],
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

                    // ✅ Foto profil (hapus / ganti)
                    if (request()->boolean('delete_photo') && $santri->user->profile_photo) {
                        if (Storage::disk('public')->exists($santri->user->profile_photo)) {
                            Storage::disk('public')->delete($santri->user->profile_photo);
                        }
                        $santri->user->profile_photo      = null;
                        $santri->user->profile_photo_name = null;
                        $santri->user->save();
                    }
                    if (request()->hasFile('profile_photo')) {
                        if ($santri->user->profile_photo && Storage::disk('public')->exists($santri->user->profile_photo)) {
                            Storage::disk('public')->delete($santri->user->profile_photo);
                        }
                        $foto = request()->file('profile_photo');
                        $santri->user->profile_photo      = $this->processProfilePhoto($foto);
                        $santri->user->profile_photo_name = $foto->getClientOriginalName();
                        $santri->user->save();
                    }
                }

                return redirect()->route('admin.santris.detail', $validated['kelas_id'])
                    ->with('success', 'Data santri dan akun login berhasil diperbarui!');

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
                        if ($santri->user->profile_photo && Storage::disk('public')->exists($santri->user->profile_photo)) {
                            Storage::disk('public')->delete($santri->user->profile_photo);
                        }
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
                    ->with('success', "Berhasil menghapus {$deletedCount} santri dan akun login terkait!");
            }

            return redirect()->route('admin.santris.index')
                ->with('success', "Berhasil menghapus {$deletedCount} santri dan akun login terkait!");

        } catch (\Exception $e) {
            \Log::error("Error bulk deleting Santri: " . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    /**
     * ✅ DOWNLOAD TEMPLATE - metode: quran / iqro / aisar
     */
    public function downloadTemplate()
    {
        $this->logDownload('santri', 'Template Import Excel');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $kelasList = Kelas::with('guru')->withCount('santris')->orderBy('id')->get();

        // 13 kolom — 3 tambahan (nama_wali, kontak_wali, tipe) dipakai modul keuangan.
        $headers = ['nis', 'nama', 'gender', 'metode', 'metode_2', 'target_juz', 'target_jilid', 'no_kelas', 'nama_wali', 'kontak_wali', 'tipe', 'password', 'nama_file_foto'];
        $sheet->fromArray($headers, NULL, 'A1');

        $sheet->getStyle('A1:M1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1F2937']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
            'borders' => ['bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => 'D1D5DB']]]
        ]);

        $sheet->getStyle('A2:M4')->applyFromArray([
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']],
            'font' => ['size' => 9, 'color' => ['rgb' => '78716C'], 'italic' => true],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'FDE68A']]]
        ]);

        // Contoh: quran, iqro, dan aisar-DUAL (aisar + target Al-Qur'an)
        $exampleData = [
            ['12345 (Contoh)', 'Ahmad',  'L', 'quran', '',      '5.50', '',  '1', 'Bapak Sanusi', '081234567890', 'Reguler', 'pass1234', 'ahmad.jpg'],
            ['12346 (Contoh)', 'Aisyah', 'P', 'iqro',  '',      '',     '3', '1', 'Ibu Halimah',  '081298765432', 'Fullday', 'pass8765', 'aisyah.jpg'],
            ['12347 (Contoh)', 'Bilal',  'L', 'aisar', 'quran', '2.00', '1', '1', 'Bapak Umar',   '085711223344', 'Reguler', 'pass5678', 'bilal.jpg'],
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
        $sheet->getStyle('A5:M104')->applyFromArray([
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
            'font' => ['italic' => false, 'bold' => false, 'size' => 10, 'color' => ['rgb' => '111827']],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
        ]);

        $sheet->getColumnDimension('A')->setWidth(16);  // nis
        $sheet->getColumnDimension('B')->setWidth(28);  // nama
        $sheet->getColumnDimension('C')->setWidth(8);   // gender
        $sheet->getColumnDimension('D')->setWidth(10);  // metode
        $sheet->getColumnDimension('E')->setWidth(10);  // metode_2
        $sheet->getColumnDimension('F')->setWidth(11);  // target_juz
        $sheet->getColumnDimension('G')->setWidth(11);  // target_jilid
        $sheet->getColumnDimension('H')->setWidth(9);   // no_kelas
        $sheet->getColumnDimension('I')->setWidth(24);  // nama_wali
        $sheet->getColumnDimension('J')->setWidth(16);  // kontak_wali
        $sheet->getColumnDimension('K')->setWidth(10);  // tipe
        $sheet->getColumnDimension('L')->setWidth(16);  // password
        $sheet->getColumnDimension('M')->setWidth(20);  // nama_file_foto
        $sheet->getColumnDimension('N')->setWidth(2);   // spacing
        $sheet->getColumnDimension('O')->setWidth(48);  // petunjuk
        $sheet->getColumnDimension('P')->setWidth(2);   // spacing
        $sheet->getColumnDimension('Q')->setWidth(8);   // no kelas
        $sheet->getColumnDimension('R')->setWidth(35);  // kelas - guru

        // PETUNJUK IMPORT (kolom O — bergeser 3 kolom karena tambahan data wali)
        $sheet->setCellValue('O1', 'PETUNJUK IMPORT SANTRI');
        $sheet->setCellValue('O2', '1. NIS: Nomor Induk Santri (unik)');
        $sheet->setCellValue('O3', '2. Nama: Nama lengkap santri');
        $sheet->setCellValue('O4', '3. Gender: L (Laki-laki) atau P (Perempuan)');
        $sheet->setCellValue('O5', '4. Metode: quran, iqro, atau aisar');
        $sheet->setCellValue('O6', '5. Metode 2: kosongkan, atau "quran" (hanya jika metode iqro/aisar)');
        $sheet->setCellValue('O7', '6. Target Juz: 0.00 - 30.00');
        $sheet->setCellValue('O8', '7. Target Jilid: 1 - 6 (isi bila metode iqro/aisar)');
        $sheet->setCellValue('O9', '8. No Kelas: Lihat tabel kanan →');
        $sheet->setCellValue('O10', '9. Nama Wali: nama orang tua/wali santri');
        $sheet->setCellValue('O11', '10. Kontak Wali: nomor HP, mis 081234567890');
        $sheet->setCellValue('O12', '11. Tipe: Reguler atau Fullday');
        $sheet->setCellValue('O13', '12. Password: Minimal 8 karakter');
        $sheet->setCellValue('O14', '13. Nama File Foto: tanpa spasi, mis: ahmad.jpg (opsional)');
        $sheet->setCellValue('O15', '');
        $sheet->setCellValue('O16', 'CATATAN:');
        $sheet->setCellValue('O17', '• Metode quran → isi Target Juz. Metode 2 & Jilid kosong.');
        $sheet->setCellValue('O18', '• Metode iqro / aisar → isi Target Jilid. Juz kosong.');
        $sheet->setCellValue('O19', '• DUAL: metode iqro/aisar + Metode 2 = quran →');
        $sheet->setCellValue('O20', '   isi Target Jilid DAN Target Juz sekaligus (lihat contoh Bilal).');
        $sheet->setCellValue('O21', '• Nama & Kontak Wali WAJIB — dipakai untuk penagihan.');
        $sheet->setCellValue('O22', '• Kelas yang sudah penuh akan menolak santri baru.');
        $sheet->setCellValue('O23', '• Foto diupload terpisah sebagai 1 ZIP saat import');
        $sheet->setCellValue('O24', '• Baris 2-4 adalah CONTOH, hapus sebelum import');

        $sheet->getStyle('O1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('O2:O14')->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '374151']],
        ]);
        $sheet->getStyle('O16')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'DC2626']],
        ]);
        $sheet->getStyle('O17:O24')->applyFromArray([
            'font' => ['size' => 8, 'color' => ['rgb' => '374151']],
        ]);
        $sheet->getStyle('O1:O24')->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => '3B82F6']]]
        ]);

        // TABEL KELAS & GURU (kolom Q-R)
        $sheet->setCellValue('Q1', 'KELAS & GURU');
        $sheet->mergeCells('Q1:R1');
        $sheet->getStyle('Q1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1F2937']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => 'D1D5DB']]]
        ]);

        $sheet->setCellValue('Q2', 'No');
        $sheet->setCellValue('R2', 'Kelas - Guru (sisa kuota)');
        $sheet->getStyle('Q2:R2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '6B7280']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ]);

        $currentRow = 3;
        foreach ($kelasList as $index => $kelas) {
            $sheet->setCellValue('Q' . $currentRow, $index + 1);
            $guruName = $kelas->guru ? $kelas->guru->name : 'Tanpa Guru';
            // Sisa kuota ditampilkan agar tidak salah pilih kelas yang sudah penuh.
            $sisa = max(0, (int) $kelas->kapasitas - (int) $kelas->santris_count);
            $sheet->setCellValue('R' . $currentRow, $kelas->name . ' - ' . $guruName . " (sisa {$sisa})");
            $sheet->getStyle('Q' . $currentRow . ':R' . $currentRow)->applyFromArray([
                'font' => ['size' => 9, 'color' => ['rgb' => '374151']],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ]);
            $currentRow++;
        }

        $lastRowKelas = $currentRow - 1;
        $sheet->getStyle('Q1:R' . $lastRowKelas)->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]]
        ]);

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->freezePane('A2');

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Template_Import_Santri_' . date('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * ✅ EXPORT UNTUK EDIT - metode: quran / iqro / aisar
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

        // 13 kolom — 3 tambahan (nama_wali, kontak_wali, tipe) dipakai modul keuangan.
        $headers = ['no_urut', 'nis', 'nama', 'gender', 'metode', 'metode_2', 'target_juz', 'target_jilid', 'nama_wali', 'kontak_wali', 'tipe', 'no_kelas_baru', 'nama_file_foto'];
        $sheet->fromArray($headers, NULL, 'A1');

        $sheet->getStyle('A1:M1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1F2937']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
            'borders' => ['bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => 'D1D5DB']]]
        ]);

        $row = 2;
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
            $sheet->setCellValue('I' . $row, $santri->parent_name);
            $sheet->setCellValue('J' . $row, $santri->parent_contact);
            $sheet->setCellValue('K' . $row, $santri->student_type);
            $sheet->setCellValue('L' . $row, ''); // no_kelas_baru (kosong, isi manual jika pindah kelas)

            // nama_file_foto: nama file asli saat diupload
            $fotoSaran = '';
            if ($santri->user && $santri->user->profile_photo) {
                $fotoSaran = $santri->user->profile_photo_name ?: basename($santri->user->profile_photo);
            }
            $sheet->setCellValue('M' . $row, $fotoSaran);

            // Kolom NO URUT background abu-abu (read-only indicator)
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
        $sheet->getColumnDimension('I')->setWidth(24);  // nama_wali
        $sheet->getColumnDimension('J')->setWidth(16);  // kontak_wali
        $sheet->getColumnDimension('K')->setWidth(10);  // tipe
        $sheet->getColumnDimension('L')->setWidth(14);  // no_kelas_baru
        $sheet->getColumnDimension('M')->setWidth(20);  // nama_file_foto
        $sheet->getColumnDimension('N')->setWidth(2);   // spacing
        $sheet->getColumnDimension('O')->setWidth(48);  // petunjuk
        $sheet->getColumnDimension('P')->setWidth(2);   // spacing
        $sheet->getColumnDimension('Q')->setWidth(8);   // no kelas
        $sheet->getColumnDimension('R')->setWidth(35);  // kelas - guru

        // PETUNJUK BULK UPDATE (kolom O — bergeser 3 kolom karena tambahan data wali)
        $sheet->setCellValue('O1', '📋 PANDUAN BULK UPDATE');
        $sheet->setCellValue('O2', '');
        $sheet->setCellValue('O3', '⚠️ ATURAN PENTING:');
        $sheet->setCellValue('O4', '• JANGAN ubah/hapus kolom NO URUT');
        $sheet->setCellValue('O5', '• JANGAN tambah/hapus baris');
        $sheet->setCellValue('O6', '• Password tidak bisa diubah via Excel');
        $sheet->setCellValue('O7', '');
        $sheet->setCellValue('O8', '📝 METODE & TARGET:');
        $sheet->setCellValue('O9', '• Metode: quran, iqro, atau aisar');
        $sheet->setCellValue('O10', '• Metode 2: kosong atau "quran" (hanya untuk iqro/aisar)');
        $sheet->setCellValue('O11', '• quran → isi Target Juz (0-30), Jilid kosong');
        $sheet->setCellValue('O12', '• iqro / aisar → isi Target Jilid (1-6), Juz kosong');
        $sheet->setCellValue('O13', '• DUAL (iqro/aisar + Metode 2 quran) → isi Jilid DAN Juz');
        $sheet->setCellValue('O14', '');
        $sheet->setCellValue('O15', '👨‍👩‍👦 DATA WALI:');
        $sheet->setCellValue('O16', '• Nama & Kontak Wali WAJIB — dipakai untuk penagihan');
        $sheet->setCellValue('O17', '• Tipe: Reguler atau Fullday (menentukan tarif)');
        $sheet->setCellValue('O18', '');
        $sheet->setCellValue('O19', '📝 PINDAH KELAS:');
        $sheet->setCellValue('O20', '• Isi "No Kelas Baru" (lihat tabel kanan →)');
        $sheet->setCellValue('O21', '• Kosongkan jika tidak pindah kelas');
        $sheet->setCellValue('O22', '• Kelas penuh akan menolak perpindahan');
        $sheet->setCellValue('O23', '');
        $sheet->setCellValue('O24', '📷 FOTO:');
        $sheet->setCellValue('O25', '• Biarkan apa adanya = foto tidak diubah');
        $sheet->setCellValue('O26', '• Ganti dgn nama file lain + upload ZIP = foto diganti');
        $sheet->setCellValue('O27', '• Tulis HAPUS = foto dihapus jadi tanpa foto');

        $sheet->getStyle('O1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('O3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'DC2626']],
        ]);
        $sheet->getStyle('O8')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '2563EB']],
        ]);
        $sheet->getStyle('O15')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '059669']],
        ]);
        $sheet->getStyle('O19')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'D97706']],
        ]);
        $sheet->getStyle('O24')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '7C3AED']],
        ]);
        $sheet->getStyle('O2:O27')->applyFromArray([
            'font' => ['size' => 8, 'color' => ['rgb' => '374151']],
        ]);
        $sheet->getStyle('O1:O27')->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => '059669']]]
        ]);

        // TABEL KELAS & GURU (kolom Q-R)
        $kelasList = Kelas::with('guru')->withCount('santris')->orderBy('id')->get();

        $sheet->setCellValue('Q1', 'KELAS & GURU');
        $sheet->mergeCells('Q1:R1');
        $sheet->getStyle('Q1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1F2937']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => 'D1D5DB']]]
        ]);

        $sheet->setCellValue('Q2', 'No');
        $sheet->setCellValue('R2', 'Kelas - Guru (sisa kuota)');
        $sheet->getStyle('Q2:R2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '6B7280']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ]);

        $currentRow = 3;
        foreach ($kelasList as $index => $kelasItem) {
            $sheet->setCellValue('Q' . $currentRow, $index + 1);
            $guruName = $kelasItem->guru ? $kelasItem->guru->name : 'Tanpa Guru';
            $sisa = max(0, (int) $kelasItem->kapasitas - (int) $kelasItem->santris_count);
            $sheet->setCellValue('R' . $currentRow, $kelasItem->name . ' - ' . $guruName . " (sisa {$sisa})");
            $sheet->getStyle('Q' . $currentRow . ':R' . $currentRow)->applyFromArray([
                'font' => ['size' => 9, 'color' => ['rgb' => '374151']],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ]);
            $currentRow++;
        }

        $lastRowKelas = $currentRow - 1;
        $sheet->getStyle('Q1:R' . $lastRowKelas)->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]]
        ]);

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->freezePane('A2');

        $writer = new Xlsx($spreadsheet);
        $kelasName = str_replace(' ', '_', $kelas->name);
        $fileName = 'Edit_Santri_' . $kelasName . '_' . date('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
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
            'photos_zip' => 'nullable|file|mimes:zip|max:20480',
        ], [
            'file.required' => 'File Excel wajib diupload',
            'file.mimes' => 'File harus berformat Excel (.xlsx atau .xls)',
            'photos_zip.mimes' => 'File foto harus berformat ZIP',
            'photos_zip.max' => 'Ukuran ZIP foto maksimal 20MB',
        ]);

        try {
            [$photos, $tmpDir] = $this->extractPhotosZip($request->file('photos_zip'));

            $import = new SantriImport($photos);
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $failedCount = $import->getFailedCount();
            $errors = $import->getErrors();
            $warnings = $import->getWarnings();

            $this->cleanupTmp($tmpDir);

            if ($failedCount > 0) {
                $errorMessage = "Berhasil: {$successCount} santri. Gagal: {$failedCount} santri.<br><br>";
                $errorMessage .= "<strong>Detail Error:</strong><br>";
                $errorMessage .= implode('<br>', array_merge($errors, $warnings));

                return redirect()->route('admin.santris.index')->with('error', $errorMessage);
            }

            $this->logActivity('import', 'santri', "Import {$successCount} data santri dari Excel", null, null, ['total_imported' => $successCount, 'kelas_stats' => $import->getKelasStats()]);

            $kelasStats = $import->getKelasStats();
            $statsText = [];
            foreach ($kelasStats as $kelasName => $count) {
                $statsText[] = "{$kelasName}: {$count} santri";
            }

            $message = "Berhasil mengimport {$successCount} santri!";
            if (!empty($statsText)) {
                $message .= " (" . implode(', ', $statsText) . ")";
            }
            if (!empty($warnings)) {
                $message .= "<br><br><strong>Catatan foto:</strong><br>" . implode('<br>', $warnings);
            }

            return redirect()->route('admin.santris.index')->with('success', $message);

        } catch (\Exception $e) {
            \Log::error("Error importing santri: " . $e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Gagal mengimport data: ' . $e->getMessage());
        }
    }

    /**
     * ✅ BULK UPDATE - Dengan ZIP Foto
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:2048',
            'kelas_id' => 'required|exists:kelas,id',
            'photos_zip' => 'nullable|file|mimes:zip|max:20480',
        ], [
            'file.required' => 'File Excel wajib diupload',
            'file.mimes' => 'File harus berformat Excel (.xlsx atau .xls)',
            'file.max' => 'Ukuran file maksimal 2MB',
            'kelas_id.required' => 'Kelas ID tidak ditemukan',
            'photos_zip.mimes' => 'File foto harus berformat ZIP',
            'photos_zip.max' => 'Ukuran ZIP foto maksimal 20MB',
        ]);

        $kelasId = $request->kelas_id;

        try {
            [$photos, $tmpDir] = $this->extractPhotosZip($request->file('photos_zip'));

            $import = new SantriBulkUpdateImport($kelasId, $photos);
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $failedCount = $import->getFailedCount();
            $errors = $import->getErrors();
            $warnings = $import->getWarnings();

            $this->cleanupTmp($tmpDir);

            if ($failedCount > 0) {
                $errorMessage = "Berhasil update: {$successCount} santri. Gagal: {$failedCount} santri.<br><br>";
                $errorMessage .= "<strong>Detail Error:</strong><br>";
                $errorMessage .= implode('<br>', array_merge($errors, $warnings));

                return redirect()->route('admin.santris.detail', $kelasId)->with('error', $errorMessage);
            }

            $this->logActivity('update', 'santri', "Bulk update {$successCount} data santri dari Excel di kelas ID {$kelasId}", null, null, ['total_updated' => $successCount, 'kelas_id' => $kelasId]);

            $msg = "Berhasil mengupdate {$successCount} data santri!";
            if (!empty($warnings)) {
                $msg .= "<br><br><strong>Catatan foto:</strong><br>" . implode('<br>', $warnings);
            }

            return redirect()->route('admin.santris.detail', $kelasId)->with('success', $msg);

        } catch (\Exception $e) {
            \Log::error("Error bulk updating santri: " . $e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());
            return redirect()->route('admin.santris.detail', $kelasId)->with('error', 'Gagal mengupdate data: ' . $e->getMessage());
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

            // Hapus user terkait + file fotonya
            if ($santri->user) {
                if ($santri->user->profile_photo && Storage::disk('public')->exists($santri->user->profile_photo)) {
                    Storage::disk('public')->delete($santri->user->profile_photo);
                }
                $santri->user->delete();
            }

            // Hapus santri
            $santri->delete();

            $this->logDelete('santri', $name, $oldValues);

            DB::commit();

            return redirect()->route('admin.santris.detail', $kelasId)
                ->with('success', 'Santri berhasil dihapus!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal menghapus Santri. ' . $e->getMessage());
        }
    }

    /**
     * Resize & crop foto jadi persegi (bias atas agar kepala tidak terpotong).
     */
    private function processProfilePhoto($file): string
    {
        $maxSize = 400;
        $path = $file->store('profile-photos', 'public');
        $fullPath = Storage::disk('public')->path($path);

        $info = getimagesize($fullPath);
        if (!$info) return $path;

        [$width, $height] = $info;
        $mime = $info['mime'];

        $src = match ($mime) {
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

    /**
     * Ekstrak ZIP foto jadi map [namaFileLowercase => pathAsli].
     * Return: [photosMap, tmpDir]
     */
    private function extractPhotosZip($zipFile): array
    {
        if (!$zipFile) {
            return [[], null];
        }
        if (!class_exists('ZipArchive')) {
            return [[], null];
        }

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
}