<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiataProvider extends Model
{
    protected $fillable = [
        'provider_code','provider_type','properties_href','requests'
    ];

    protected $casts = [
        'requests' => 'array',
    ];
}