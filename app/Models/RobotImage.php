<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class RobotImage extends Model
{
    protected $fillable = [
        'robot_id',
        'title',
        'caption',
        'disk',
        'path',
        'url',
        'thumbnail_path',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // Relacionamentos
    public function robot(): BelongsTo
    {
        return $this->belongsTo(Robot::class);
    }

    // Accessor para sempre retornar URL completa
    public function getUrlAttribute($value)
    {
        // Se já é uma URL completa (começa com http:// ou https://), retorna como está
        if ($value && (str_starts_with($value, 'http://') || str_starts_with($value, 'https://'))) {
            return $value;
        }

        // Se é um caminho relativo, converte para URL completa
        if ($value && str_starts_with($value, '/')) {
            return url($value);
        }

        // Se não tem URL salva, gera a partir do path
        if ($this->path) {
            return url(Storage::url($this->path));
        }

        return $value;
    }
}
