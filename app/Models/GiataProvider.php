<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiataProvider extends Model
{
    protected $table = 'giata_providers';

    // Alias para mantener la vista tal como la tienes
    protected $appends = ['name', 'type', 'code'];

    public function getNameAttribute()
    {
        // No tenemos nombre real; usamos el code como “name” visible
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