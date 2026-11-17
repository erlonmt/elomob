<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportMode extends Model
{
    public const WALK = 'walk';

    public $timestamps = false;

    protected $fillable = ['code', 'name', 'color'];

    public function stops(): HasMany
    {
        return $this->hasMany(Stop::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(Line::class);
    }
}
