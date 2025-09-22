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
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('codser')->unique();           // Código del hotel (clave del servicio)
            $table->string('name')->nullable();           // Nombre del hotel (nomser)
            $table->string('zone_code')->nullable();      // Código de zona (relación con zones.code)
            $table->string('category')->nullable();       // Categoría (codsca)
            $table->string('type')->nullable();           // Tipo de servicio (codtse)
            $table->decimal('latitude', 10, 7)->nullable();  // Coordenadas opcionales
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();

            // Relación con la tabla zones
            $table->foreign('zone_code')->references('code')->on('zones')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
