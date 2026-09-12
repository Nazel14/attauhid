<?php

namespace App\Models\Keuangan\MasterData;

use App\Models\Kelas as KelasSantri;
use App\Models\Keuangan\Inbound\ItemTagihan;
use App\Models\Santri;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tarif extends Model
{
    use SoftDeletes;

    protected $table = 'tariffs';

    protected $fillable = [
        'billing_name',
        'nominal',
        'is_donation',
        'period',
        'is_active',
    ];

    protected $casts = [
        'nominal'     => 'decimal:2',
        'is_active'   => 'boolean',
        'is_donation' => 'boolean',
    ];

    public function itemTagihan(): HasMany
    {
        return $this->hasMany(ItemTagihan::class, 'tariff_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(TarifTarget::class, 'tariff_id');
    }

    /** Santri aktif yang kena tarif ini. */
    public function eligibleSantris(): Builder
    {
        $query = Santri::query()->where('status', 'aktif');

        foreach ($this->targets->groupBy('target_type') as $type => $rules) {
            $column = TarifTarget::DIMENSIONS[$type] ?? null;
            if (! $column) {
                continue;
            }
            $query->whereIn($column, $rules->pluck('target_value'));
        }

        return $query;
    }

    /** Ringkasan target dalam bahasa manusia. */
    public function targetSummary(): string
    {
        if ($this->is_donation) {
            return 'Donasi (tanpa target santri)';
        }

        if ($this->targets->isEmpty()) {
            return 'Semua santri aktif';
        }

        $parts = [];

        foreach ($this->targets->groupBy('target_type') as $type => $rules) {
            $values = $rules->pluck('target_value');

            if ($type === 'kelas') {
                $values = KelasSantri::whereIn('id', $values)->pluck('name');
            }

            $parts[] = (TarifTarget::LABELS[$type] ?? $type)
                     . ': ' . $values->implode(', ');
        }

        return implode(' — dan — ', $parts);
    }
}