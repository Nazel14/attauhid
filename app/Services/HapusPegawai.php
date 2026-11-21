<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\Keuangan\Payroll\PayrollDetail;
use App\Models\Keuangan\Payroll\PayrollHeader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Penghapusan permanen seorang guru/pegawai (tabel gurus).
 *
 * Dipakai bersama oleh Management Guru (panel raport) dan Master Data
 * Pegawai (panel keuangan), supaya aturannya tidak pernah berbeda:
 *
 *  - Ikut terhapus : absensi, komponen gaji, baris gaji di periode draft,
 *                    akun login & foto.
 *  - Dilepas       : kelas, santri bimbingan, setoran, presensi — kolom
 *                    gurunya dikosongkan, datanya tetap.
 *  - Dibekukan     : gaji yang sudah diproses — nama disalin ke baris
 *                    gajinya, karena uangnya sudah tercatat di buku kas.
 *
 * Wajib dipanggil di dalam DB::transaction().
 */
class HapusPegawai
{
    /**
     * Inti penghapusan. Urutannya penting: data santri dilepas DULU, baru
     * baris guru dihapus — jangan pernah bergantung pada aturan FK saja.
     */
    public function hapus(Model $guru): void
    {
        $id = $guru->id;

        // 1. Lepaskan data milik santri. Dikerjakan eksplisit walau FK sudah
        //    SET NULL: bila SQL perubahan FK belum dijalankan di suatu
        //    database, langkah ini gagal (kolom NOT NULL) dan seluruh
        //    transaksi batal — santri tidak ikut terhapus oleh CASCADE lama.
        Kelas::where('guru_id', $id)->update(['guru_id' => null]);

        foreach (['santris', 'setorans', 'presensis'] as $tabel) {
            if (Schema::hasTable($tabel) && Schema::hasColumn($tabel, 'guru_id')) {
                DB::table($tabel)->where('guru_id', $id)->update(['guru_id' => null]);
            }
        }

        // 2. Penggajian.
        if (Schema::hasTable('payroll_details') && class_exists(PayrollDetail::class)) {
            $this->bereskanPenggajian($guru);
        }

        // 3. Absensi milik guru.
        if (Schema::hasTable('attendances')) {
            DB::table('attendances')->where('employee_id', $id)->delete();
        }

        // 4. Akun login + foto.
        if ($guru->user) {
            if ($guru->user->profile_photo && Storage::disk('public')->exists($guru->user->profile_photo)) {
                Storage::disk('public')->delete($guru->user->profile_photo);
            }
            $guru->user->delete();
        }

        // 5. Baris guru. Komponen gaji (employee_allowance/deduction) ikut
        //    terhapus lewat FK CASCADE — memang data milik guru sendiri.
        //    forceDelete: model Guru/Pegawai pakai SoftDeletes, tapi tombol
        //    Hapus artinya permanen.
        method_exists($guru, 'forceDelete') ? $guru->forceDelete() : $guru->delete();
    }

    /**
     * Gaji di periode draft dibuang (belum ada uang keluar) dan total
     * periodenya dihitung ulang. Gaji yang sudah diproses dibekukan: nama
     * guru disalin, lalu tautannya dilepas — slip lama tetap bernama dan
     * total periodenya tetap sama dengan yang tercatat di buku kas.
     */
    private function bereskanPenggajian(Model $guru): void
    {
        $detail = DB::table('payroll_details as d')
            ->join('payroll_headers as h', 'h.id', '=', 'd.payroll_header_id')
            ->where('d.employee_id', $guru->id)
            ->when(Schema::hasColumn('payroll_details', 'deleted_at'), fn ($q) => $q->whereNull('d.deleted_at'))
            ->get(['d.id', 'd.payroll_header_id', 'h.status_approval']);

        $draft = $detail->where('status_approval', 'draft');
        $beku  = $detail->where('status_approval', '!=', 'draft');

        if ($draft->isNotEmpty()) {
            // Lewat model, sama seperti PayrollController::destroyDetail():
            // relasi snapshot tunjangan/potongan ikut dibersihkan dan
            // SoftDeletes (bila dipakai) dihormati saat menjumlah ulang.
            PayrollDetail::whereIn('id', $draft->pluck('id')->all())->get()
                ->each(function ($d) {
                    $d->allowances()->delete();
                    $d->deductions()->delete();
                    $d->delete();
                });

            foreach ($draft->pluck('payroll_header_id')->unique() as $headerId) {
                $sisa = PayrollDetail::where('payroll_header_id', $headerId)->get();

                PayrollHeader::whereKey($headerId)->update([
                    'total_gaji_pokok' => $sisa->sum('gaji_pokok'),
                    'total_tunjangan'  => $sisa->sum('total_tunjangan'),
                    'total_potongan'   => $sisa->sum('total_potongan'),
                    'total_tambahan'   => $sisa->sum('nominal_tambahan'),
                    'grand_total'      => $sisa->sum('take_home_pay'),
                ]);
            }
        }

        if ($beku->isNotEmpty()) {
            DB::table('payroll_details')->whereIn('id', $beku->pluck('id')->all())->update([
                'nama_pegawai_snapshot' => $guru->name,
                'employee_id'           => null,
            ]);
        }
    }

    /**
     * Jumlah data terdampak per guru, untuk dialog konfirmasi hapus.
     * Satu query per tabel (bukan per guru) supaya daftar tetap cepat.
     */
    public function dampak(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $hitung = function (string $tabel, string $kolom) use ($ids) {
            if (! Schema::hasTable($tabel)) {
                return collect();
            }
            return DB::table($tabel)->whereIn($kolom, $ids)
                ->selectRaw("$kolom as gid, COUNT(*) as n")->groupBy($kolom)
                ->pluck('n', 'gid');
        };

        $absensi  = $hitung('attendances', 'employee_id');
        $santri   = $hitung('santris', 'guru_id');
        $kelas    = $hitung('kelas', 'guru_id');
        $setoran  = $hitung('setorans', 'guru_id');
        $presensi = $hitung('presensis', 'guru_id');

        $gajiDraft = collect();
        $gajiBeku  = collect();
        if (Schema::hasTable('payroll_details')) {
            $gaji = DB::table('payroll_details as d')
                ->join('payroll_headers as h', 'h.id', '=', 'd.payroll_header_id')
                ->whereIn('d.employee_id', $ids)
                ->when(Schema::hasColumn('payroll_details', 'deleted_at'), fn ($q) => $q->whereNull('d.deleted_at'))
                ->selectRaw("d.employee_id as gid, SUM(h.status_approval = 'draft') as draft, SUM(h.status_approval <> 'draft') as beku")
                ->groupBy('d.employee_id')
                ->get()->keyBy('gid');
            $gajiDraft = $gaji->map(fn ($r) => (int) $r->draft);
            $gajiBeku  = $gaji->map(fn ($r) => (int) $r->beku);
        }

        $hasil = [];
        foreach ($ids as $id) {
            $hasil[$id] = [
                'absensi'    => (int) ($absensi[$id] ?? 0),
                'gaji_draft' => (int) ($gajiDraft[$id] ?? 0),
                'gaji_beku'  => (int) ($gajiBeku[$id] ?? 0),
                'santri'     => (int) ($santri[$id] ?? 0),
                'kelas'      => (int) ($kelas[$id] ?? 0),
                'setoran'    => (int) ($setoran[$id] ?? 0),
                'presensi'   => (int) ($presensi[$id] ?? 0),
            ];
        }

        return $hasil;
    }

    public function pesanGagal(QueryException $e): string
    {
        \Log::error('Gagal hapus guru: ' . $e->getMessage());

        // Kolom guru_id di setoran/presensi masih NOT NULL, atau kolom
        // nama_pegawai_snapshot belum ada: SQL perubahan FK belum dijalankan.
        if (str_contains($e->getMessage(), 'cannot be null') || str_contains($e->getMessage(), 'nama_pegawai_snapshot')) {
            return 'Gagal menghapus: struktur database belum diperbarui. Jalankan SQL perubahan sambungan tabel guru '
                . '(santris/setorans/presensis/payroll_details) terlebih dahulu. Tidak ada data yang terhapus.';
        }

        return 'Gagal menghapus guru. Tidak ada data yang terhapus. (' . $e->getMessage() . ')';
    }
}