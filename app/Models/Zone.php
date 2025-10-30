<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends BaseClientModel
{
    protected $table = 'zones';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code', 'parent_code', 'type', 'name', 'is_final'];

    protected $casts = [
        'is_final' => 'boolean',
    ];

    public function hotels(): HasMany
    {
        return $this->hasMany(Hotel::class, 'zone_code', 'code');
    }
}
