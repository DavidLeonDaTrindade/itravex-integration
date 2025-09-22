<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('zones', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();          // codzge: código único de zona
            $table->string('parent_code')->nullable(); // zgesup: código de zona padre
            $table->string('type');                    // tipzge: tipo de zona (ZON, SRG, CTY, etc.)
            $table->string('name')->nullable();        // nomzge: nombre de la zona
            $table->boolean('is_final')->default(false); // chkfin: si es una hoja final del árbol

            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};
