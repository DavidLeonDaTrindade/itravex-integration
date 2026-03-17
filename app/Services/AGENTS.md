# AGENTS.md

## Objetivo
Este agente concentra la logica reusable y los puntos de integracion externa. Aqui suelen resolverse las reglas reales de negocio.

## Servicios actuales
- `ItravexService.php`: operaciones de disponibilidad, lock, cierre o cancelacion asociadas a Itravex.
- `ClaimConfirmationSyncService.php`: sincronizacion incremental de claim confirmations desde SAMO.

## Cuando entrar aqui
- El controlador o comando actua como orquestador ligero.
- Hay llamadas HTTP externas, parsing de respuestas, reglas de sincronizacion o transformacion de payloads.
- El cambio debe compartirse entre UI, comandos y tests.

## Flujo recomendado
- Abrir el servicio concreto.
- Leer solo el modelo o cliente externo que el servicio usa de inmediato.
- Si hay dependencia HTTP, revisar despues `app/Http/Clients/ItravexClient.php` o `config/services.php` segun corresponda.
- Si hay persistencia sensible, seguir a `app/Models/AGENTS.md`.
- Si existe comando espejo del flujo, comprobar `app/Console/Commands/AGENTS.md`.

## Evitar contexto sobrante
- No abrir todas las migraciones para entender un servicio; basta con el modelo o la tabla exacta.
- No leer ambos servicios por defecto. Elegir `Itravex` o `ClaimConfirmation` segun el caso.
