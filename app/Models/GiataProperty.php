<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GiataProperty extends Model
{
    use HasFactory;

    protected $table = 'giata_properties';

    protected $fillable = [
        'giata_id',
        'name',
        'country',
        'last_update',
    ];

    protected $casts = [
        'last_update' => 'datetime',
    ];

    // ── Relaciones ────────────────────────────────────────────────
    public function codes()
    {
        return $this->hasMany(GiataPropertyCode::class, 'giata_property_id');
    }

    // muchos-a-muchos a través de la tabla de códigos (pivot enriquecido)
    public function providers()
    {
        return $this->belongsToMany(GiataProvider::class, 'giata_property_codes', 'giata_property_id', 'provider_id')
            ->withPivot(['code_value', 'status', 'add_info'])
            ->withTimestamps();
    }

    // ── Scopes útiles ─────────────────────────────────────────────
    public function scopeGiata($query, int $giataId)
    {
        return $query->where('giata_id', $giataId);
    }

    public function scopeCountry($query, string $iso2)
    {
        return $query->where('country', strtoupper($iso2));
    }
}
