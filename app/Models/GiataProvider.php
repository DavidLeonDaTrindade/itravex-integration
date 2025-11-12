<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GiataProvider extends Model
{
    use HasFactory;

    protected $table = 'giata_providers';

    protected $fillable = [
        'provider_code',
        'provider_type',
        'properties_href',   // ← nuevo
        'requests',
    ];
    protected $casts = [
        'requests' => 'array',
    ];

    // Alias para mantener la vista tal como la tienes
    protected $appends = ['name', 'type', 'code'];

    // ── Relaciones ────────────────────────────────────────────────
    public function codes()
    {
        return $this->hasMany(GiataPropertyCode::class, 'provider_id');
    }

    public function properties()
    {
        // muchos-a-muchos vía tabla de códigos
        return $this->belongsToMany(GiataProperty::class, 'giata_property_codes', 'provider_id', 'giata_property_id')
            ->withPivot(['code_value', 'status', 'add_info'])
            ->withTimestamps();
    }

    // ── Accessors legacy ─────────────────────────────────────────
    public function getNameAttribute()
    {
        return $this->provider_code;
    }

    public function getTypeAttribute()
    {
        return $this->provider_type;
    }

    public function getCodeAttribute()
    {
        return $this->provider_code;
    }
}
