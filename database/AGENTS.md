# AGENTS.md

## Objetivo
Este agente acota cambios estructurales de base de datos y evita leer migraciones historicas sin necesidad.

## Grupos de migraciones
- Base framework: `users`, `cache`, `jobs`
- Catalogos operativos: `zones`, `hotels`, `itravex_reservations`
- Autenticacion y usuarios: columnas de `users`
- GIATA: providers, properties, property codes, properties raw, indices asociados
- Claim confirmations: `2026_03_16_120000_create_claim_confirmations_table.php`

## Flujo recomendado
- Abrir solo la migracion o tabla relacionada.
- Si el cambio toca rendimiento, localizar primero indices existentes.
- Si el cambio nace desde un modelo, leer solo el modelo implicado ademas de la migracion concreta.
- Si afecta ambas conexiones funcionales, pensar en compatibilidad de despliegue y datos existentes.

## Evitar contexto sobrante
- No leer migraciones antiguas no relacionadas solo por orden cronologico.
- No asumir que un problema de datos requiere migracion; validar antes si era un bug de query, cast o conexion.
