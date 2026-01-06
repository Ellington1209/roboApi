<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RobotParameter extends Model
{
    protected $fillable = [
        'robot_id',
        'key',
        'label',
        'type',
        'value',
        'default_value',
        'required',
        'options',
        'validation_rules',
        'group',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'default_value' => 'array',
            'options' => 'array',
            'validation_rules' => 'array',
            'required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // Relacionamentos
    public function robot(): BelongsTo
    {
        return $this->belongsTo(Robot::class);
    }
}
