# AGENTS.md

## Objetivo
Este agente ayuda a validar cambios con la menor superficie posible y a no cargar una suite completa cuando no hace falta.

## Estado de la suite
- Las pruebas mas fiables del trabajo reciente son `tests/Unit/ClaimConfirmationSyncServiceTest.php` y `tests/Feature/ClaimConfirmationControllerTest.php`.
- Hay tests heredados de Breeze que pueden no reflejar el estado real del proyecto.
- `User` y algunas rutas de auth pueden introducir ruido en pruebas antiguas.

## Regla de validacion
- Ejecutar primero el test mas cercano al cambio.
- Si se toca claim confirmations, priorizar los tests ya existentes de esa funcionalidad.
- Si se toca UI o controlador sin cobertura, valorar test feature pequeno antes que ampliar suites ajenas.
- Si se toca modelo o servicio sin HTTP, preferir unit test focalizado.

## Handoffs recomendados
- Desde web: volver a `app/Http/Controllers/AGENTS.md`
- Desde sync o integraciones: volver a `app/Services/AGENTS.md` o `app/Console/Commands/AGENTS.md`
- Desde cambios de esquema: revisar `database/AGENTS.md`

## Evitar contexto sobrante
- No correr toda la suite por defecto.
- No usar tests heredados de auth como señal principal salvo que la tarea sea precisamente de auth.
