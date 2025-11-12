<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('giata_providers', function (Blueprint $table) {
            $table->id();

            // Código único del proveedor (ej: itravex, hotelbeds, tui, etc.)
            $table->string('provider_code')->unique();

            // Tipo de proveedor según Giata: GDS o Tour Operator
            $table->enum('provider_type', ['gds', 'tourOperator'])->index();

            // Enlace base a la colección de properties del proveedor (xlink:href)
            $table->string('properties_href')->nullable();

            // JSON con estructura de requests (formato, params, etc.)
            $table->json('requests')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('giata_providers');
    }
};
