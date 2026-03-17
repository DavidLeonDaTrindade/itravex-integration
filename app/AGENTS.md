# AGENTS.md

## Objetivo
Este nivel organiza la aplicacion Laravel por capas para evitar lecturas horizontales innecesarias.

## Selector rapido
- `app/Http/Controllers/AGENTS.md`: flujos web, pantallas y endpoints.
- `app/Services/AGENTS.md`: logica reusable de integracion y sincronizacion.
- `app/Console/Commands/AGENTS.md`: procesos `artisan`, imports y tareas batch.
- `app/Models/AGENTS.md`: entidades, consultas y conexion dinamica.

## Regla de entrada
- Empezar por la capa donde nace la tarea.
- Si el cambio entra por una URL o una accion del panel, ir a controladores.
- Si entra por un comando o cron, ir a consola.
- Si el problema es de consulta, tabla, fillable, casts o conexion, ir a modelos.
- Si el controlador o comando solo orquesta y deriva trabajo, saltar pronto a servicios.

## Evitar contexto sobrante
- No abrir `Providers/`, `View/Components/`, `Jobs/` o `Http/Middleware/` salvo que el flujo lo requiera.
- No leer todos los ficheros de una carpeta por similitud de nombre; elegir el caso exacto.
- Si la tarea es GIATA, no mezclar con disponibilidad ni claim confirmations salvo evidencia clara.

## Handoffs utiles
- Controladores con logica excesiva -> `app/Services/AGENTS.md`
- Cambio de persistencia o consulta -> `app/Models/AGENTS.md`
- Cambio que tambien existe por comando -> `app/Console/Commands/AGENTS.md`
- Cambio de middleware o conexion -> volver a raiz y considerar `platform-config`
