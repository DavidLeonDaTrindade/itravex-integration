<?php

namespace App\Models;

class ClaimConfirmation extends BaseClientModel
{
    protected $fillable = [
        'claim',
        'changestamp',
        'status',
        'flag',
        'comment',
        'cost',
    ];

    protected $casts = [
        'claim' => 'integer',
        'changestamp' => 'integer',
        'cost' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
