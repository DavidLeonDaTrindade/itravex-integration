# Testing y validacion

## Estado general
La aplicacion tiene tests utiles, pero la cobertura no es uniforme entre modulos.

Hay dos realidades:
- algunos tests heredados de Breeze siguen presentes
- los tests mas fiables y alineados con cambios recientes estan en claim confirmations

## Tests mas utiles hoy
- `tests/Unit/ClaimConfirmationSyncServiceTest.php`
- `tests/Feature/ClaimConfirmationControllerTest.php`

Cobertura actual de claim confirmations:
- renderizado de la pantalla
- sincronizacion desde SAMO simulada
- exportacion CSV de ultimos registros

## Riesgos conocidos
- parte de la suite de auth no refleja exactamente el estado actual de la app
- `App\Models\User` puede introducir sesgos por conexion fija
- no todos los modulos pesados, como disponibilidad o GIATA, tienen la misma cobertura automatizada

## Estrategia recomendada
- ejecutar primero el test mas cercano al cambio
- priorizar tests feature en pantallas operativas
- priorizar tests unit cuando la logica vive en servicios o parsing
- evitar usar la suite completa como unica señal de calidad si el cambio es muy localizado

## Ejecucion recomendada
Preferir el contenedor `app`, ya que el PHP del host puede no tener todas las extensiones necesarias.

Ejemplos:

```sh
docker compose exec app php artisan test tests/Feature/ClaimConfirmationControllerTest.php
docker compose exec app php artisan test tests/Unit/ClaimConfirmationSyncServiceTest.php
```

## Huecos razonables para ampliar
- flujos criticos de `AvailabilityController`
- validacion de exportaciones GIATA
- cambio de conexion entre `mysql` y `mysql_cli2`
- logs y filtros del visor de `itravex`
