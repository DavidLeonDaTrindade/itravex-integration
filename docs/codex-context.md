# Itravex Integration Context

## Proyecto

- Nombre: `itravex-integration`
- Tipo: aplicacion Laravel
- Rama principal actual: `main`
- Repositorio remoto: `git@github.com:DavidLeonDaTrindade/itravex-integration.git`
- Ruta local habitual: `/home/david/projects/itravex-integration`

## Objetivo funcional

Esta aplicacion sirve como panel interno para trabajar con operaciones de Itravex y modulos auxiliares relacionados con disponibilidad, reservas, logs, GIATA y confirmaciones de claims.

El dashboard actua como punto de entrada principal y desde ahi se accede a los modulos operativos.

## Entorno local

- Se trabaja con Docker Compose.
- Contenedor web/app: `laravel_app`
- Contenedor worker: `laravel_worker`
- Contenedor MySQL: `mysql_db`
- phpMyAdmin expuesto en puerto `8081`
- App local expuesta normalmente en `http://localhost:8000`

Comandos utiles:

- `docker compose ps`
- `docker compose exec app php artisan ...`
- `docker compose exec mysql mysql -uroot -pPortu628.`

## Bases de datos

La aplicacion maneja dos conexiones principales seleccionables por sesion:

- `mysql` -> base `itravex`
- `mysql_cli2` -> base `itravex2`

La conexion activa se guarda en sesion con la clave `db_connection`.

Middleware implicado:

- `App\Http\Middleware\AcceptDbConnParam`
- `App\Http\Middleware\EnsureClientConnection`

Modelo base para conexiones dinamicas:

- `App\Models\BaseClientModel`

Importante:

- Algunos modulos GIATA deben ocultarse o tratarse con cuidado cuando la conexion activa es `mysql_cli2`.
- El usuario `cliente2_user` tuvo que crearse manualmente en MySQL para operar sobre `itravex2`.

## Modulos principales

Secciones relevantes del proyecto:

- Dashboard: `/dashboard`
- Disponibilidad: `route('availability.form')`
- Estado Locata: `route('itravex.status')`
- Logs Itravex: `route('logs.itravex')`
- Claim Confirmations: `route('claim-confirmations.index')`
- GIATA propiedades raw: `route('giata.properties.raw.index')`
- GIATA proveedores: `route('giata.providers.index')`
- GIATA codigos: `route('giata.codes.browser')`

El navbar fue ampliado para permitir navegar directamente a estos modulos sin pasar por el dashboard.

## Claim confirmations

Se implemento un modulo nuevo para sincronizar confirmaciones desde SAMO.

Piezas clave:

- Comando Artisan: `App\Console\Commands\UpdateClaimConfirmations`
- Servicio: `App\Services\ClaimConfirmationSyncService`
- Controlador web: `App\Http\Controllers\ClaimConfirmationController`
- Modelo: `App\Models\ClaimConfirmation`
- Vista: `resources/views/claim-confirmations/index.blade.php`
- Migracion: `database/migrations/2026_03_16_120000_create_claim_confirmations_table.php`

La tabla `claim_confirmations` existe ya en:

- `itravex`
- `itravex2`

Estructura importante:

- `claim` y `changestamp` usan `unsignedBigInteger`
- `changestamp` debe ser `BIGINT` porque puede superar el rango de un `INT` con signo

La sincronizacion web y el comando hacen la peticion a SAMO usando el ultimo `changestamp` almacenado en base de datos.

Configuracion SAMO en `config/services.php`:

- `services.samo.base_url`
- `services.samo.username`
- `services.samo.password`
- `services.samo.claim_number`

## Testing

Hay tests heredados de Breeze, pero no todos reflejan el estado real actual del proyecto.

Tests utiles añadidos para la funcionalidad nueva:

- `tests/Unit/ClaimConfirmationSyncServiceTest.php`
- `tests/Feature/ClaimConfirmationControllerTest.php`

Observaciones importantes:

- La suite completa no esta completamente saneada.
- Parte de los tests viejos fallan porque:
  - `App\Models\User` fuerza la conexion `mysql`
  - algunas rutas de auth no coinciden con el Breeze original
- Para pruebas fiables del trabajo reciente, usar los tests de claim confirmations y ejecutar preferiblemente dentro del contenedor.

Ejemplos:

- `docker compose exec app php artisan test tests/Unit/ClaimConfirmationSyncServiceTest.php`
- `docker compose exec app php artisan test tests/Feature/ClaimConfirmationControllerTest.php`

## Produccion

Servidor de produccion conocido:

- Host SSH: `production`
- IP: `46.101.165.221`
- Usuario: `root`

Checkout desplegado:

- `/var/www/laravel`

Flujo usado con exito:

1. `git push origin main`
2. `ssh production`
3. `cd /var/www/laravel`
4. `git pull --ff-only origin main`
5. `php artisan migrate --force`
6. `php artisan view:clear`
7. `php artisan cache:clear`

Importante:

- En el servidor se detectaron archivos no versionados con nombres raros en `/var/www/laravel`. No bloquearon el deploy, pero conviene revisarlos.

## Permisos y caches

Hubo un problema real con permisos en:

- `storage/framework/views`
- `bootstrap/cache`

Causa:

- algunos archivos compilados de Blade estaban creados como `root`, mientras Laravel necesitaba escribir como `www-data`

Correccion aplicada:

- `chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache`
- borrado de vistas compiladas
- `php artisan view:clear`

Si reaparece un error tipo `file_put_contents(... storage/framework/views ... Permission denied)`, revisar primero propietarios y permisos.

## Estilo visual

Cambios recientes de UI:

- navbar ampliado con accesos directos a modulos
- dashboard con tarjetas mas limpias y mejor jerarquia visual
- pantalla de claim confirmations con CTA principal mas profesional

Cuando se toquen vistas, conviene mantener esta linea visual:

- tarjetas limpias
- contraste claro
- acciones principales visibles
- navegacion rapida entre modulos

## Recomendacion para futuras conversaciones

Prompt corto sugerido:

```text
Lee primero /home/david/projects/itravex-integration/docs/codex-context.md
Trabaja sobre /home/david/projects/itravex-integration
Objetivo: <tu tarea>
```

## Documentacion tecnica interna

Existe una seccion tecnica dedicada en:

- `docs/technical/README.md`

Usarla cuando haga falta una vista estructurada de:

- arquitectura general
- modulos funcionales
- capa de datos
- integraciones y comandos
- operacion y testing
