<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    protected $fillable = [
        'imdb_id',
        'info_hash',
        'title',
        'filename',
        'size_bytes',
        'resolution',
        'provider',
        'is_active',
        'last_kept_alive_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_kept_alive_at' => 'datetime',
    ];
}