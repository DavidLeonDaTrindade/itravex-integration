# AGENTS.md

## Objetivo
Este agente delimita la capa de datos, especialmente la seleccion dinamica de conexion y los modelos operativos.

## Puntos clave
- `BaseClientModel.php` define la base para usar la conexion elegida en sesion o contexto.
- Modelos operativos: `Hotel`, `Zone`, `ItravexReservation`, `ClaimConfirmation`
- Modelos GIATA: `GiataProvider`, `GiataProperty`, `GiataPropertyCode`, `GiataPropertyRaw`
- `User` tiene implicaciones especiales en tests y autenticacion.

## Flujo recomendado
- Empezar por el modelo exacto.
- Si el comportamiento depende de la conexion, revisar despues `BaseClientModel.php` y middleware relacionado.
- Si la duda es de columnas, indices o tipos, saltar a `database/AGENTS.md`.
- Si la query nace desde controlador o servicio, volver solo al llamador inmediato, no a toda la aplicacion.

## Alertas de contexto
- Confirmar siempre si la funcionalidad debe funcionar en `mysql`, `mysql_cli2` o ambas.
- No asumir que todos los modelos usan la misma conexion que `User`.
- En claim confirmations, cuidar `changestamp` como `BIGINT`.

## Evitar contexto sobrante
- No abrir todas las migraciones de GIATA para tocar un solo modelo.
- No recorrer todos los modelos si la tarea se limita a una entidad o pareja de entidades.
