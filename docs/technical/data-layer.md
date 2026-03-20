# Capa de datos

## Conexiones disponibles
La configuracion de base de datos se define en `config/database.php`.

Conexiones funcionales principales:
- `mysql`: base principal `itravex`
- `mysql_cli2`: base alternativa `itravex2`

La app puede cambiar la conexion activa en runtime segun la sesion del usuario.

## Seleccion de conexion

### Middleware
- `AcceptDbConnParam`: acepta `db_connection` desde request y la normaliza a `mysql` o `mysql_cli2`
- `EnsureClientConnection`: cambia `database.default` y fuerza reconexion

### Modelo base
`BaseClientModel` aplica esta prioridad:
1. `session('db_connection')`
2. `session('db_conn')` por compatibilidad
3. `Auth::user()->db_connection`
4. `config('database.default')`

## Modelos operativos

### `Zone`
- tabla: `zones`
- relacion: una zona tiene muchos hoteles

### `Hotel`
- tabla: `hotels`
- relacion: pertenece a una zona
- uso principal: busquedas de hoteles y soporte a disponibilidad

### `ItravexReservation`
- tabla: `itravex_reservations`
- uso principal: historial o estado de reservas generadas desde la operativa

### `ClaimConfirmation`
- tabla: `claim_confirmations`
- campos relevantes: `claim`, `changestamp`, `status`, `flag`, `comment`, `cost`
- nota importante: `changestamp` debe mantenerse como `BIGINT`

## Modelos GIATA

### `GiataProvider`
- representa proveedores GIATA
- relaciones con `GiataPropertyCode` y propiedades asociadas

### `GiataProperty`
- almacena propiedades normalizadas
- relacion con codigos y proveedores

### `GiataPropertyCode`
- tabla puente entre propiedades y proveedores
- scopes especificos para codigos activos y filtrado por provider

### `GiataPropertyRaw`
- dataset raw para exploracion y filtrado previo a normalizacion o cruces

## Migraciones relevantes

### Catalogos y operativa
- `2025_07_17_102617_create_zones_table.php`
- `2025_07_21_091201_create_hotels_table.php`
- `2025_07_29_105015_create_itravex_reservations_table.php`
- `2025_08_09_114254_add_indexes_to_hotels_table.php`

### Usuarios y auth
- `0001_01_01_000000_create_users_table.php`
- `2025_09_15_095926_add_admin_and_active_to_users.php`
- `2025_09_25_115128_add_db_connection_to_users_table.php`

### GIATA
- `2025_10_23_000000_create_giata_providers_table.php`
- `2025_11_07_091152_create_giata_properties_table.php`
- `2025_11_07_091157_create_giata_property_codes_table.php`
- `2025_11_07_104602_update_giata_properties_add_giata_fields.php`
- `2025_11_24_084936_create_giata_properties_raw_table.php`
- `2026_03_13_000000_add_missing_indexes_to_giata_property_codes_table.php`

### Claim confirmations
- `2026_03_16_120000_create_claim_confirmations_table.php`

## Recomendaciones de mantenimiento
- cualquier cambio en modelos compartidos debe validarse con ambas conexiones
- antes de crear una migracion, confirmar si el problema es realmente estructural o solo de query o cast
- evitar asumir que la conexion efectiva coincide siempre con `config('database.default')` en tiempo de bootstrap
