<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItravexReservation extends Model
{
    protected $fillable = [
        'locata',
        'hotel_name',
        'hotel_code',
        'room_type',
        'board',
        'start_date',
        'end_date',
        'num_guests',
        'total_price',
        'currency',
        'status',
    ];
}
