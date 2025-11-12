<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void

    {
        if (\Illuminate\Support\Facades\Schema::hasTable('giata_property_codes')) {
            return; // ya existe, no la volvemos a crear
        }
        Schema::create('giata_property_codes', function (Blueprint $table) {
            $table->id();

            // FKs
            $table->foreignId('giata_property_id')
                ->constrained('giata_properties')
                ->cascadeOnDelete();

            $table->foreignId('provider_id')
                ->constrained('giata_providers')
                ->cascadeOnDelete();

            // Código del hotel en el proveedor (respetar ceros a la izq y mayúsculas/minúsculas)
            $table->string('code_value', 255);

            // Estado del código (null = no indicado por GIATA)
            $table->enum('status', ['active', 'inactive'])->nullable();

            // addInfo (estructura arbitraria por proveedor)
            $table->json('add_info')->nullable();

            $table->timestamps();

            // Un mismo hotel puede tener varios códigos por proveedor, pero no repetidos
            $table->unique(['giata_property_id', 'provider_id', 'code_value'], 'uniq_prop_provider_code');

            // Búsqueda rápida por proveedor + código
            $table->index(['provider_id', 'code_value'], 'idx_provider_code');

            // Búsqueda rápida por propiedad
            $table->index('giata_property_id', 'idx_giata_property');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('giata_property_codes');
    }
};
