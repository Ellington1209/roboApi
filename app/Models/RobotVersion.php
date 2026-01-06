<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RobotVersion extends Model
{
    protected $fillable = [
        'robot_id',
        'version',
        'code',
        'changelog',
        'is_current',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'is_current' => 'boolean',
        ];
    }

    // Relacionamentos
    public function robot(): BelongsTo
    {
        return $this->belongsTo(Robot::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
