<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Robot extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'language',
        'tags',
        'code',
        'is_active',
        'version',
        'last_executed_at',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_active' => 'boolean',
            'version' => 'integer',
            'last_executed_at' => 'datetime',
        ];
    }

    // Relacionamentos
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(RobotParameter::class)->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(RobotImage::class)->orderBy('sort_order');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(RobotVersion::class)->orderBy('version', 'desc');
    }

    public function currentVersion(): HasMany
    {
        return $this->hasMany(RobotVersion::class)->where('is_current', true);
    }
}
