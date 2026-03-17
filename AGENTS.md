# AGENTS.md

## Objetivo
Este archivo actua como orquestador de `itravex-integration`. Su funcion es ayudar a cargar solo el contexto minimo necesario, elegir la zona correcta del proyecto y evitar lecturas amplias que disparen la compactacion del contexto.

## Primer contexto recomendado
- Leer primero `docs/codex-context.md` si la tarea toca entorno, despliegue, Docker, conexiones activas o modulos funcionales.
- Para cambios pequenos, empezar directamente por el archivo mas cercano al problema y volver a este `AGENTS.md` solo para reencaminar.

## Mapa rapido
- `app/Http/Controllers/`: entrada web del panel, modulos operativos y endpoints auxiliares.
- `app/Services/`: logica reutilizable para integraciones y sincronizacion.
- `app/Console/Commands/`: procesos batch y sincronizaciones por `artisan`.
- `app/Models/`: acceso a datos, conexion dinamica y entidades de negocio.
- `resources/views/`: Blade del panel interno.
- `database/`: migraciones y fabrica base.
- `tests/`: pruebas focalizadas, con especial relevancia en claim confirmations.
- `config/`: conexiones, servicios externos y opciones de runtime.

## Regla principal de contexto
- No recorrer `vendor/`, `node_modules/`, `storage/`, `bootstrap/cache/`, `public/build/` ni `.git/` salvo que el problema sea de dependencias, assets compilados o infraestructura.
- No abrir `.env` completo ni exponer secretos. Buscar solo claves concretas si hacen falta.
- No leer todos los controladores ni todas las vistas por defecto. Identificar antes el modulo: disponibilidad, GIATA, claim confirmations, logs, auth o dashboard.
- Si la tarea es de una pantalla concreta, abrir primero su ruta en `routes/web.php`, luego el controlador, luego la vista.
- Si la tarea es de sync o importacion, abrir primero el comando o servicio implicado y solo despues modelos o migraciones relacionadas.

## Selector de agentes
- `root-orchestrator`: punto de entrada. Decide si el trabajo cae en web, servicios, consola, datos, config o tests.
- `web-panel`: pantallas Blade, navegacion, dashboard, formularios, filtros y flujo controlador-vista.
- `giata-sync`: modulos GIATA, providers, property codes, properties raw, imports CSV y enriquecimiento.
- `availability-ops`: disponibilidad, lock, cancelacion, status de Itravex y reservas operativas.
- `claim-sync`: claim confirmations desde SAMO, sincronizacion web/comando, vistas y tests asociados.
- `data-shape`: modelos, relaciones, migraciones, indices y efectos de la conexion dinamica entre `mysql` y `mysql_cli2`.
- `platform-config`: Docker, `config/*.php`, middleware de conexion, jobs y comportamiento de entorno.
- `test-keeper`: pruebas focalizadas, huecos de cobertura y validacion del cambio minimo fiable.

## Cuando elegir cada uno
- Si la tarea menciona GIATA, providers, property codes, properties raw, importacion o enrichment: usar `giata-sync`.
- Si la tarea menciona availability, lock, close, cancel, reservas o status Itravex: usar `availability-ops`.
- Si la tarea menciona claim confirmations, changestamp, SAMO o sincronizacion incremental: usar `claim-sync`.
- Si la tarea menciona una vista, navbar, dashboard, formulario o paginacion: usar `web-panel`.
- Si la tarea afecta tablas, indices, conexiones o modelos compartidos: usar `data-shape`.
- Si la tarea afecta `config/`, Docker, middleware, jobs o variables concretas de entorno: usar `platform-config`.
- Si la prioridad es validar o ampliar pruebas: usar `test-keeper`.

## Handoffs recomendados
- De `root-orchestrator` a `app/AGENTS.md` para cualquier cambio de aplicacion.
- De `web-panel` a `app/Http/Controllers/AGENTS.md` para entrada HTTP.
- De `web-panel` a `resources/views/AGENTS.md` si ya esta claro que el cambio es de Blade o layout.
- De `claim-sync`, `availability-ops` o `giata-sync` a `app/Services/AGENTS.md` cuando la logica no cabe en el controlador.
- De `claim-sync`, `giata-sync` o `platform-config` a `app/Console/Commands/AGENTS.md` si el flujo nace en `artisan`.
- De cualquier agente funcional a `app/Models/AGENTS.md` si hay que entender tablas, scopes o conexion base.
- De `data-shape` a `database/AGENTS.md` si el cambio exige migracion o revision de indices.
- De cualquier agente a `tests/AGENTS.md` cuando llegue el momento de validar.

## Atajos de lectura
- Entrada principal web: `routes/web.php`
- Conexion dinamica: `app/Models/BaseClientModel.php`, `app/Http/Middleware/AcceptDbConnParam.php`, `app/Http/Middleware/EnsureClientConnection.php`
- Disponibilidad y reservas: `app/Http/Controllers/AvailabilityController.php`, `app/Http/Controllers/ItravexReservationController.php`, `app/Services/ItravexService.php`
- Claim confirmations: `app/Http/Controllers/ClaimConfirmationController.php`, `app/Services/ClaimConfirmationSyncService.php`, `app/Console/Commands/UpdateClaimConfirmations.php`
- GIATA: `app/Http/Controllers/Giata*`, `app/Console/Commands/Giata*`, `app/Console/Commands/ImportGiataCsv.php`, modelos `Giata*`
- Config externa: `config/database.php`, `config/services.php`, `config/itravex.php`

## Criterio de profundidad
- Si la tarea se entiende con ruta + controlador + vista, no abrir mas.
- Si la tarea se entiende con comando + servicio + modelo, no abrir todos los controladores relacionados.
- Si aparecen dos conexiones o comportamientos distintos por cliente, confirmar pronto si el cambio debe respetar `mysql` y `mysql_cli2`.
- Leer `tests/` solo los casos conectados con la funcionalidad tocada.
