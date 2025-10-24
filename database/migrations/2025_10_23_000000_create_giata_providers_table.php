<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('giata_providers', function (Blueprint $table) {
            $table->id();
            $table->string('provider_code')->unique();         // ej: gta, 24x7rooms
            $table->enum('provider_type', ['gds','tourOperator']);
            $table->string('properties_href');                 // xlink:href de <properties>
            $table->json('requests')->nullable();              // [{format:"...", params:[{name,typeHint}]}]
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('giata_providers');
    }
};