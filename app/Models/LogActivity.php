<?php
// app/Models/LogActivity.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;

class LogActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'role',
        'activity_type',
        'module',
        'description',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Polymorphic relation ke model yang di-log
     */
    public function model()
    {
        return $this->morphTo();
    }

    /**
     * Helper static method untuk membuat log
     */
    public static function log(
        string $activityType,
        string $module,
        string $description,
        $model = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ) {
        $user = Auth::user();
        
        return self::create([
            'user_id' => $user?->id,
            'role' => $user?->role ?? 'guest',
            'activity_type' => $activityType,
            'module' => $module,
            'description' => $description,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id ?? null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /* ========================================
     * QUERY SCOPES
     * ======================================== */

    /**
     * Filter by role
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Filter by module
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Filter by activity type
     */
    public function scopeByActivityType($query, string $type)
    {
        return $query->where('activity_type', $type);
    }

    /**
     * Filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get recent logs (default 30 days)
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get today's logs
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Search in description
     */
    public function scopeSearch($query, string $keyword)
    {
        return $query->where('description', 'like', "%{$keyword}%");
    }

    /* ========================================
     * ACCESSOR & MUTATOR
     * ======================================== */

    /**
     * Format activity type untuk display
     */
    public function getFormattedActivityTypeAttribute(): string
    {
        return match($this->activity_type) {
            'login' => 'Login',
            'logout' => 'Logout',
            'create' => 'Tambah Data',
            'update' => 'Edit Data',
            'delete' => 'Hapus Data',
            'download' => 'Download',
            'view' => 'Lihat Data',
            'import' => 'Import Data',
            'submit' => 'Submit Data',
            'approve' => 'Approve',
            'reject' => 'Reject',
            default => ucfirst($this->activity_type),
        };
    }

    /**
     * Format module untuk display
     */
    public function getFormattedModuleAttribute(): string
    {
        return match($this->module) {
            'auth' => 'Autentikasi',
            'santri' => 'Santri',
            'guru' => 'Guru',
            'kelas' => 'Kelas',
            'presensi' => 'Presensi',
            'setoran' => 'Setoran',
            'rapot' => 'Rapot',
            'sesi-presensi' => 'Sesi Presensi',
            'monitoring' => 'Monitoring',
            'log-activity' => 'Log Activity',
            default => ucfirst($this->module),
        };
    }

    /**
     * Get badge color based on activity type
     */
    public function getBadgeColorAttribute(): string
    {
        return match($this->activity_type) {
            'login' => 'success',
            'logout' => 'secondary',
            'create' => 'primary',
            'update' => 'info',
            'delete' => 'danger',
            'download' => 'warning',
            'view' => 'secondary',
            'submit' => 'violet',
            'approve' => 'lime',
            'reject' => 'rose',
            default => 'secondary',
        };
    }
}