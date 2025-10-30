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

    // 👇 hace que zone_name salga en el JSON automáticamente
    protected $appends = ['zone_name'];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_code', 'code');
    }

    // 👇 accessor para el nombre de la zona
    public function getZoneNameAttribute(): ?string
    {
        return $this->zone?->name;
    }
}
