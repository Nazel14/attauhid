<?php

namespace App\Observers;

use App\Models\LogActivity;
use Illuminate\Database\Eloquent\Model;

/**
 * Mencatat setiap perubahan data ke tabel log_activities secara otomatis.
 *
 * Dipasang di AppServiceProvider untuk model-model yang menyimpan uang.
 * Dibuat generik supaya satu file ini cukup untuk semua model, dan model
 * baru tinggal didaftarkan tanpa menulis ulang logika pencatatan.
 */
class AuditObserver
{
    /**
     * Kolom yang tidak ikut dicatat.
     *
     * updated_at/created_at berubah di setiap penyimpanan sehingga hanya
     * jadi kebisingan. Kolom password sengaja dibuang supaya isinya tidak
     * bocor lewat log, yang justru dibaca lebih banyak orang.
     */
    private const ABAIKAN = [
        'updated_at',
        'created_at',
        'password',
        'plain_password',
        'remember_token',
    ];

    public function created(Model $model): void
    {
        $this->catat($model, 'create', null, $this->saring($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $sesudah = $this->saring($model->getChanges());

        // Penyimpanan yang hanya menggeser updated_at bukan perubahan data.
        if (empty($sesudah)) {
            return;
        }

        // getOriginal() di dalam event 'updated' masih memegang nilai
        // sebelum penyimpanan — syncOriginal() baru jalan setelahnya.
        $sebelum = array_intersect_key($model->getOriginal(), $sesudah);

        $this->catat($model, 'update', $sebelum, $sesudah);
    }

    public function deleted(Model $model): void
    {
        $this->catat($model, 'delete', $this->saring($model->getAttributes()), null);
    }

    /**
     * Tulis satu baris log.
     *
     * Kegagalan pencatatan tidak boleh menggagalkan transaksi aslinya:
     * lebih baik kehilangan satu baris log daripada kehilangan pembayaran.
     */
    private function catat(Model $model, string $jenis, ?array $sebelum, ?array $sesudah): void
    {
        try {
            $pengguna = auth()->user();

            LogActivity::create([
                'user_id'       => $pengguna?->id,
                'role'          => $pengguna?->role ?? 'sistem',
                'activity_type' => $jenis,
                'module'        => $this->modul($model),
                'description'   => $this->keterangan($model, $jenis),
                'model_type'    => $model::class,
                'model_id'      => $model->getKey(),
                'old_values'    => $sebelum,
                'new_values'    => $sesudah,
                'ip_address'    => request()->ip(),
                'user_agent'    => (string) request()->userAgent(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Buang kolom yang ada di daftar abaikan. */
    private function saring(array $nilai): array
    {
        return array_diff_key($nilai, array_flip(self::ABAIKAN));
    }

    /** Nama modul diambil dari nama kelas: Pembayaran -> pembayaran. */
    private function modul(Model $model): string
    {
        return strtolower(class_basename($model));
    }

    private function keterangan(Model $model, string $jenis): string
    {
        $kata = match ($jenis) {
            'create' => 'Menambah',
            'update' => 'Mengubah',
            'delete' => 'Menghapus',
            default  => 'Memproses',
        };

        return "{$kata} {$this->modul($model)} #{$model->getKey()}";
    }
}