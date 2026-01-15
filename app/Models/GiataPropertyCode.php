<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GiataPropertyCode extends Model
{
    use HasFactory;

    protected $table = 'giata_property_codes';

    protected $fillable = [
        'giata_property_id',
        'provider_id',
        'code_value',
        'status',    // 'active' | 'inactive'
        'add_info',  // JSON
    ];

    protected $casts = [
        'add_info' => 'array',
    ];

    // ── Relaciones ────────────────────────────────────────────────
    public function property()
    {
        return $this->belongsTo(GiataProperty::class, 'giata_property_id');
    }

    public function provider()
    {
        return $this->belongsTo(GiataProvider::class, 'provider_id');
    }

    // ── Scopes ────────────────────────────────────────────────────

    /**
     * SOLO códigos activos reales
     */
    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    /**
     * Filtrar por provider_code
     */
    public function scopeForProvider($q, string $providerCode)
    {
        return $q->whereHas('provider', function ($p) use ($providerCode) {
            $p->where('provider_code', $providerCode);
        });
    }
}
