<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('giata_properties', function (Blueprint $table) {
            if (!Schema::hasColumn('giata_properties', 'giata_id')) {
                $table->unsignedBigInteger('giata_id')->unique()->after('id');
            }
            if (!Schema::hasColumn('giata_properties', 'name')) {
                $table->string('name')->nullable()->after('giata_id');
            }
            if (!Schema::hasColumn('giata_properties', 'country')) {
                $table->string('country', 2)->nullable()->after('name');
            }
            if (!Schema::hasColumn('giata_properties', 'last_update')) {
                $table->timestamp('last_update')->nullable()->after('country');
            }
            // índices útiles (por si no existen)
            $table->index('country');
        });
    }

    public function down(): void
    {
        Schema::table('giata_properties', function (Blueprint $table) {
            if (Schema::hasColumn('giata_properties', 'last_update')) $table->dropColumn('last_update');
            if (Schema::hasColumn('giata_properties', 'country'))     $table->dropColumn('country');
            if (Schema::hasColumn('giata_properties', 'name'))        $table->dropColumn('name');
            if (Schema::hasColumn('giata_properties', 'giata_id'))    $table->dropColumn('giata_id');
        });
    }
};
