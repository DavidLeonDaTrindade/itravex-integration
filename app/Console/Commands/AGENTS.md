# AGENTS.md

## Objetivo
Este agente cubre procesos batch, imports y sincronizaciones ejecutadas por `artisan`.

## Familias de comandos
- Claim confirmations: `UpdateClaimConfirmations.php`
- GIATA: `GiataSyncProperties.php`, `GiataSyncPropertiesBasic.php`, `GiataEnrichNullBasics.php`, `ImportGiataCsv.php`, `SyncGiataProviders.php`
- Catalogos y zonas: `ImportZones.php`, `ImportZones2.php`, `ImportHotelsByZone.php`, `ImportHotelsByZone2.php`
- Utilidades puntuales: `ContarTarifasXML.php`

## Regla de lectura
- Abrir solo el comando nombrado o el mas cercano por dominio.
- Identificar rapido si la logica vive en el comando o delega a servicios/modelos.
- Si hay variantes `2`, confirmar si el cambio es exclusivo de cliente 2 o debe replicarse al flujo base.

## Handoffs recomendados
- Si el comando delega negocio: `app/Services/AGENTS.md`
- Si la complejidad esta en persistencia o conexion: `app/Models/AGENTS.md`
- Si hay cambio estructural de tabla o indice: `database/AGENTS.md`
- Si el comando tiene cobertura o necesita una nueva: `tests/AGENTS.md`

## Evitar contexto sobrante
- No abrir todos los comandos GIATA juntos. Separar import, sync providers, sync properties y enrichment.
- No mezclar imports de zonas/hoteles con GIATA salvo que la tarea pida un cruce entre catalogos.
