<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('giata_properties_raw', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('giata_id')->index();
            $table->string('name')->nullable();
            $table->string('rating')->nullable();
            $table->string('city')->nullable();
            $table->string('destination')->nullable();
            $table->string('country_code', 4)->nullable();

            // Campos potencialmente largos → TEXT
            $table->text('address_lines')->nullable();
            $table->string('zipcode')->nullable();
            $table->text('phone')->nullable();
            $table->text('fax')->nullable();
            $table->text('email')->nullable();
            $table->text('website')->nullable();

            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->string('accuracy')->nullable();
            $table->string('last_change')->nullable();

            $table->text('alternative_name')->nullable();
            $table->string('chain')->nullable();
            $table->string('airport')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('giata_properties_raw');
    }
};
