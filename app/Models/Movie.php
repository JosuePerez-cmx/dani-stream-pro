<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    protected $fillable = [
        'title',
        'release_title', 
        'imdb_id',
        'magnet_link',
        'size_gb',
        'is_active',
        'last_kept_alive_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_kept_alive_at' => 'datetime',
    ];
}