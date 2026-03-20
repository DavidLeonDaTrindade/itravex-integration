# Arquitectura

## Resumen
`itravex-integration` es una aplicacion Laravel que funciona como panel interno para operaciones de Itravex, herramientas GIATA, consulta de logs y sincronizacion de claim confirmations desde SAMO.

La aplicacion mezcla dos patrones principales:
- flujos web tradicionales Blade para operativa diaria
- procesos batch y sincronizaciones por `artisan` para carga, enriquecimiento e importacion de datos

## Capas principales

### 1. Entrada web
La entrada principal esta en `routes/web.php`. Casi toda la operativa queda bajo `auth`, con rutas especificas para:
- disponibilidad y reservas
- estado de reservas
- claim confirmations
- GIATA providers, codes y properties raw
- visor de logs
- perfil y auth heredada de Breeze

### 2. Controladores
Los controladores se concentran en `app/Http/Controllers/`.

Patrones observables:
- algunos controladores son ligeros y delegan en servicios, como claim confirmations
- otros concentran bastante logica de negocio y transformacion, especialmente `AvailabilityController`
- varios modulos GIATA combinan listados web, endpoints JSON y exportaciones CSV

### 3. Servicios
Los servicios reutilizables viven en `app/Services/`.

Actualmente hay dos ejes:
- `ItravexService`: apertura de sesion y envio XML contra Itravex
- `ClaimConfirmationSyncService`: sincronizacion incremental contra SAMO

## Conexion dinamica por cliente
Uno de los rasgos arquitectonicos mas importantes es la seleccion dinamica de base de datos.

Piezas implicadas:
- `app/Http/Middleware/AcceptDbConnParam.php`
- `app/Http/Middleware/EnsureClientConnection.php`
- `app/Models/BaseClientModel.php`
- `config/database.php`

Funcionamiento:
1. la sesion guarda `db_connection`
2. el middleware valida que sea `mysql` o `mysql_cli2`
3. `EnsureClientConnection` cambia la conexion por defecto en runtime
4. los modelos que heredan de `BaseClientModel` usan esa conexion activa

Esto permite reutilizar gran parte del panel para dos bases de datos funcionales distintas.

## Persistencia
La app usa Eloquent para las entidades de negocio y consultas manuales o `upsert` para sincronizaciones.

Bloques principales de datos:
- catalogos operativos: `zones`, `hotels`, `itravex_reservations`
- usuarios y auth
- tablas GIATA: providers, properties, property codes, properties raw
- `claim_confirmations`

## UI
La interfaz usa Blade con layouts compartidos y navegacion central.

Principios visuales ya consolidados:
- tarjetas limpias
- contraste claro
- acciones principales visibles
- navegacion directa a modulos operativos

## Riesgos tecnicos relevantes
- `AvailabilityController` concentra mucha logica y es un punto natural de deuda tecnica.
- Hay mezcla de logica de dominio entre controladores, comandos y servicios segun el modulo.
- La suite de tests no cubre toda la app con la misma fiabilidad.
- La conexion dinamica es potente, pero cualquier cambio en middleware, modelos o auth puede romper la coherencia entre `mysql` y `mysql_cli2`.
