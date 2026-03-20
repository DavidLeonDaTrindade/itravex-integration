# Integraciones y procesos batch

## Integracion Itravex

### Configuracion
La integracion principal se apoya en `config/itravex.php`.

Parametros clave:
- `endpoint`
- `codsys`
- `codage`
- `user`
- `pass`
- `codtou`
- `codnac`

### Componentes
- `App\Http\Clients\ItravexClient`
- `App\Services\ItravexService`
- `App\Http\Controllers\AvailabilityController`

### Flujo base
1. se construye XML segun la operacion
2. se envia al endpoint remoto
3. se parsea la respuesta XML
4. se persiste o renderiza el resultado segun el caso

## Integracion SAMO

### Configuracion
Se define en `config/services.php` bajo la clave `samo`.

Parametros:
- `base_url`
- `username`
- `password`
- `claim_number`

### Componente principal
`App\Services\ClaimConfirmationSyncService`

### Flujo base
1. leer ultimo `changestamp` guardado
2. pedir a SAMO cambios posteriores
3. parsear el payload XML
4. extraer bloques `{CM|...}`
5. hacer `upsert` por `claim`
6. repetir mientras llegue un `new_changestamp` superior

### Manejo de errores
- log de errores HTTP
- log de XML invalido
- excepciones de dominio mediante `RuntimeException`

## Integracion GIATA

### Configuracion
La integracion GIATA vive en `config/services.php` bajo la clave `giata`.

Parametros:
- `base_url`
- `user`
- `pass`

### Areas tecnicas
- sincronizacion de providers
- sincronizacion de properties
- enriquecimiento de registros
- importaciones CSV o XLSX
- exploracion de properties raw

## Jobs

### `SyncGiataProvidersJob`
Se utiliza para encolar sincronizaciones ligadas a providers concretos. Es especialmente relevante cuando la UI solicita lanzar sync de un provider.

### Worker
El contenedor `worker` en `docker-compose.yml` ejecuta:

```sh
php artisan queue:work --queue=default --sleep=1 --tries=3
```

## Comandos Artisan

### Claim confirmations
- `UpdateClaimConfirmations`

### GIATA
- `SyncGiataProviders`
- `GiataSyncProperties`
- `GiataSyncPropertiesBasic`
- `GiataEnrichNullBasics`
- `ImportGiataCsv`

### Catalogos operativos
- `ImportZones`
- `ImportZones2`
- `ImportHotelsByZone`
- `ImportHotelsByZone2`

### Utilidades puntuales
- `ContarTarifasXML`

## Criterios de uso
- usar UI cuando la accion sea interactiva o de soporte puntual
- usar comando cuando el flujo sea pesado, repetible o apto para cron manual o batch
- usar job cuando haga falta desacoplar tiempo de respuesta web de una sincronizacion
