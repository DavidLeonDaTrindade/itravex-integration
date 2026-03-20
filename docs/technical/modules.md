# Modulos funcionales

## Vision general
La aplicacion se organiza en modulos operativos accesibles desde el panel y el navbar.

## Dashboard y navegacion
- Ruta principal autenticada: `/dashboard`
- Rol: punto de entrada a modulos operativos
- Vistas relacionadas: `resources/views/dashboard.blade.php`, `resources/views/layouts/navigation.blade.php`

## Disponibilidad y operaciones Itravex

### Alcance
Gestiona consulta de disponibilidad, bloqueo, cierre y cancelacion de reservas.

### Entradas principales
- `/availability/form`
- `/availability/select-zones`
- `/availability/search`
- `/availability/lock`
- `/availability/close`
- `/availability/cancel`
- `/itravex/status`

### Componentes clave
- Controlador principal: `App\Http\Controllers\AvailabilityController`
- Estado de reservas: `App\Http\Controllers\ItravexReservationController`
- Busquedas auxiliares: `AreaSearchController`, `HotelSearchController`
- Servicio reutilizable: `App\Services\ItravexService`
- Cliente HTTP/XML: `App\Http\Clients\ItravexClient`
- Modelo de persistencia operativa: `App\Models\ItravexReservation`

### Observaciones tecnicas
- `AvailabilityController` es el modulo mas grande y combina construccion XML, llamadas remotas, parsing y preparacion de vistas.
- El flujo depende de configuracion en `config/itravex.php`.
- El estado de reservas se consulta desde base de datos y permite borrado manual de entradas guardadas.

## Claim confirmations

### Alcance
Sincroniza confirmaciones de claims desde SAMO y las expone en una pantalla operativa con export CSV.

### Entradas principales
- `/claim-confirmations`
- `/claim-confirmations/sync`
- `/claim-confirmations/export`

### Componentes clave
- Controlador: `App\Http\Controllers\ClaimConfirmationController`
- Servicio: `App\Services\ClaimConfirmationSyncService`
- Comando: `App\Console\Commands\UpdateClaimConfirmations`
- Modelo: `App\Models\ClaimConfirmation`
- Vista: `resources/views/claim-confirmations/index.blade.php`

### Comportamiento
- lista resultados paginados ordenados por `changestamp` descendente
- permite sincronizacion manual desde interfaz
- permite exportar los ultimos registros a CSV con limite configurable
- reutiliza el ultimo `changestamp` almacenado para sincronizar de forma incremental

## GIATA providers

### Alcance
Lista, filtra y sincroniza proveedores GIATA.

### Entradas principales
- `/giata/providers`
- `/giata/providers/search`
- `POST /giata/providers`

### Componentes clave
- Controlador: `App\Http\Controllers\GiataProviderController`
- Modelo: `App\Models\GiataProvider`
- Job: `App\Jobs\SyncGiataProvidersJob`
- Comandos relacionados: `SyncGiataProviders`, `GiataSyncProperties`, `GiataSyncPropertiesBasic`

### Particularidades
- el listado soporta filtros por texto y tipo
- el endpoint de busqueda devuelve JSON
- la accion de sincronizacion puede crear el provider y encolar trabajo posterior

## GIATA codes

### Alcance
Herramientas para consultar, filtrar, exportar y cruzar codigos de propiedades GIATA.

### Entradas principales
- `/giata/codes`
- `/giata/codes/browser`
- `/giata/hotels-suggest`
- `POST /giata/codes/export`
- `POST /giata/codes/upload-giata`

### Componentes clave
- `App\Http\Controllers\GiataCodesController`
- `App\Http\Controllers\GiataCodesBrowserController`
- `App\Models\GiataPropertyCode`
- `App\Models\GiataProperty`
- `App\Models\GiataProvider`

### Particularidades
- mezcla flujos web, exportacion y carga de ficheros XLSX
- el browser de codigos puede leer un XLSX sin librerias externas, usando `ZipArchive` y `SimpleXML`

## GIATA properties raw

### Alcance
Exploracion y export de propiedades raw de GIATA.

### Entradas principales
- `/giata/properties-raw`
- `/giata/properties-raw/cities`
- `/giata/properties-raw/names`
- `/giata/properties-raw/export`

### Componentes clave
- Controlador: `App\Http\Controllers\GiataPropertyRawController`
- Modelo: `App\Models\GiataPropertyRaw`

### Particularidades
- filtros por ciudad o destino y por nombre de hotel
- sugerencias AJAX para ciudades y nombres
- export CSV por chunks para no cargar toda la consulta en memoria

## Logs Itravex

### Alcance
Visor y descarga de archivos de log del canal `itravex`.

### Entradas principales
- `/logs/itravex`
- `/logs/itravex/download`

### Componentes clave
- Controlador: `App\Http\Controllers\LogViewerController`
- Vista: `resources/views/logs/itravex.blade.php`

### Particularidades
- selector de archivo
- filtros por texto libre y `locata`
- lectura eficiente de ultimas lineas mediante tail manual

## Perfil y auth
La app conserva parte del scaffolding de Breeze.

Componentes:
- `ProfileController`
- controladores en `app/Http/Controllers/Auth`
- vistas en `resources/views/auth` y `resources/views/profile`

Observacion:
- esta zona no representa el centro funcional del proyecto y su cobertura de tests no siempre coincide con el estado real del panel.
