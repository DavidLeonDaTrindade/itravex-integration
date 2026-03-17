# AGENTS.md

## Objetivo
Este agente cubre configuracion sensible y comportamiento de entorno sin necesidad de inspeccionar todo el proyecto.

## Ficheros clave
- `database.php`: conexiones `mysql` y `mysql_cli2`
- `services.php`: credenciales e integraciones externas, incluido SAMO
- `itravex.php`: opciones propias de la integracion
- `queue.php`, `logging.php`, `app.php`: comportamiento transversal solo si la tarea lo pide

## Cuando entrar aqui
- La tarea menciona variables de entorno, timeouts, credenciales, endpoints o nombres de conexion.
- Un servicio o comando falla por configuracion, no por logica.
- Hay que entender por que una integracion usa una URL, usuario o conexion distinta.

## Regla de seguridad
- Nunca volcar secretos completos.
- Buscar solo la clave necesaria en `.env` si de verdad hace falta confirmar un valor o una presencia.
- Preferir leer el `config/*.php` correspondiente antes que inspeccionar entorno bruto.

## Handoffs recomendados
- Si el cambio impacta middleware o seleccion de base de datos, volver a `app/Models/AGENTS.md` y `app/Http/Controllers/AGENTS.md`.
- Si el cambio afecta un cliente externo o sincronizacion, volver a `app/Services/AGENTS.md`.
