<?php

namespace App\Models;

class ItravexReservation extends BaseClientModel
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
