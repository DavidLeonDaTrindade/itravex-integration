<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItravexReservationsTable extends Migration
{
    public function up()
    {
        Schema::create('itravex_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('locata')->unique();         // Código del localizador
            $table->string('hotel_name');               // Nombre del hotel
            $table->string('hotel_code');               // Código del hotel (codser)
            $table->string('room_type')->nullable();    // codsmo
            $table->string('board')->nullable();        // codral
            $table->date('start_date');                 // fecini
            $table->date('end_date');                   // fecfin
            $table->integer('num_guests');
            $table->decimal('total_price', 10, 2);      // impnoc
            $table->string('currency');
            $table->string('status')->default('confirmed'); // CM / P / etc.
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('itravex_reservations');
    }
}

