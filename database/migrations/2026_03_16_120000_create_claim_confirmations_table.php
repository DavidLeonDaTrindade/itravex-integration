<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_confirmations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('claim')->unique();
            $table->unsignedBigInteger('changestamp')->default(0)->index();
            $table->string('status', 50);
            $table->string('flag', 20)->nullable();
            $table->string('comment', 255)->nullable();
            $table->decimal('cost', 18, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_confirmations');
    }
};
