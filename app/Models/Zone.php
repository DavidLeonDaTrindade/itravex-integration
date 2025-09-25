<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends BaseClientModel
{
    protected $fillable = [
        'code',
        'parent_code',
        'type',
        'name',
        'is_final',
    ];

    public function hotels(): HasMany
    {
        return $this->hasMany(Hotel::class, 'zone_code', 'code');
    }
}
