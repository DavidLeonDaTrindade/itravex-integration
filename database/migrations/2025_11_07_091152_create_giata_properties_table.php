<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('giata_properties')) {
            return; // ya existe (venía del backup)
        }

        Schema::create('giata_properties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('giata_id')->unique();
            $table->string('name')->nullable();
            $table->string('country', 2)->nullable();
            $table->timestamp('last_update')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('giata_properties');
    }
};
