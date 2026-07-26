<?php

namespace App\Models\Keuangan\MasterData;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RkasTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'rkas_templates';

    protected $fillable = ['parent_id', 'kode_judul', 'nama_judul', 'level'];

    protected $casts = [
        'level' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(RkasTemplate::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(RkasTemplate::class, 'parent_id');
    }
}