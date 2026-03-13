<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Algunos entornos ya pueden tener estos índices (p.ej. si se aplicaron manualmente).
        // Aquí los creamos solo si no existen para no romper migraciones repetidas.
        $connection = Schema::getConnection();
        $hasIdxProviderProperty = (bool) $connection->selectOne(
            "SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$connection->getDatabaseName(), 'giata_property_codes', 'idx_provider_property']
        );
        $hasIdxProviderStatusProperty = (bool) $connection->selectOne(
            "SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$connection->getDatabaseName(), 'giata_property_codes', 'idx_provider_status_property']
        );

        Schema::table('giata_property_codes', function (Blueprint $table) use ($hasIdxProviderProperty, $hasIdxProviderStatusProperty) {
            if (! $hasIdxProviderProperty) {
                $table->index(['provider_id', 'giata_property_id'], 'idx_provider_property');
            }
            if (! $hasIdxProviderStatusProperty) {
                $table->index(['provider_id', 'status', 'giata_property_id'], 'idx_provider_status_property');
            }
        });
    }

    public function down(): void
    {
        Schema::table('giata_property_codes', function (Blueprint $table) {
            $table->dropIndex('idx_provider_property');
            $table->dropIndex('idx_provider_status_property');
        });
    }
};
