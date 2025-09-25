<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hotel extends BaseClientModel
{
    protected $fillable = [
        'codser',
        'name',
        'zone_code',
        'category',
        'type',
        'latitude',
        'longitude',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_code', 'code');
    }
}
