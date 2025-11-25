<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiataPropertyRaw extends Model
{
    protected $table = 'giata_properties_raw';

    protected $primaryKey = 'id';

    protected $fillable = [
        'giata_id',
        'name',
        'rating',
        'city',
        'destination',
        'country_code',
        'address_lines',
        'zipcode',
        'phone',
        'fax',
        'email',
        'website',
        'latitude',
        'longitude',
        'accuracy',
        'last_change',
        'alternative_name',
        'chain',
        'airport',
    ];

    protected $casts = [
        'giata_id'   => 'integer',
        'latitude'   => 'float',
        'longitude'  => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
