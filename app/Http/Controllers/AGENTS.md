# AGENTS.md

## Objetivo
Este agente cubre la capa HTTP del panel interno. Debe limitar la lectura al modulo web que realmente participa en la peticion.

## Modulos web
- Disponibilidad y operaciones Itravex: `AvailabilityController`, `ItravexReservationController`, `AreaSearchController`, `HotelSearchController`
- Claim confirmations: `ClaimConfirmationController`
- GIATA: `GiataProviderController`, `GiataCodesController`, `GiataCodesBrowserController`, `GiataPropertyRawController`
- Soporte y panel: `LogViewerController`, `ProfileController`, `ZoneController`
- Auth: `Auth/*` solo si el problema es de login, password o verificacion

## Flujo minimo recomendado
- Empezar en `routes/web.php` para ubicar la ruta exacta y middlewares.
- Abrir solo el controlador implicado.
- Si devuelve Blade, seguir a `resources/views/AGENTS.md`.
- Si delega integracion o sincronizacion, seguir a `app/Services/AGENTS.md`.
- Si necesita consultas complejas o comportamiento por conexion, seguir a `app/Models/AGENTS.md`.

## Heuristicas por tarea
- Pantalla rota o CTA incorrecto: ruta -> controlador -> vista.
- Filtro que no funciona: ruta -> controlador -> request params -> modelo o query.
- Error de sync lanzado desde interfaz: controlador -> servicio -> comando relacionado si existe.
- Cambio en GIATA browser o export: revisar primero `GiataCodesController` y `GiataCodesBrowserController`, no toda la familia GIATA.

## Evitar contexto sobrante
- No abrir todos los controladores `Auth/` salvo que el bug sea de autenticacion.
- No leer vistas compartidas completas si el problema esta en una sola pantalla.
- No abrir comandos batch desde aqui salvo que el controlador los dispare o replique su logica.
