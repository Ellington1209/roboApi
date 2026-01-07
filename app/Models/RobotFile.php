<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RobotFile extends Model
{
    protected $fillable = [
        'robot_id',
        'name',
        'disk',
        'path',
        'url',
        'mime_type',
        'file_type',
        'size_bytes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    // Relacionamentos
    public function robot(): BelongsTo
    {
        return $this->belongsTo(Robot::class);
    }
}
