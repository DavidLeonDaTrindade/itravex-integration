# Operacion y despliegue

## Entorno local
La app se trabaja con Docker Compose.

Servicios relevantes:
- `app`: contenedor web Apache o PHP
- `worker`: cola de jobs
- `mysql`: base de datos MySQL 8
- `phpmyadmin`: inspeccion manual
- `db-backup`: backup programado

Puertos habituales:
- app: `8000`
- phpMyAdmin: `8081`
- MySQL: `3306`

## Comandos frecuentes

```sh
docker compose ps
docker compose exec app php artisan test
docker compose exec app php artisan migrate
docker compose exec app php artisan <comando>
docker compose exec mysql mysql -uroot -pPortu628.
```

## Backups
El servicio `db-backup` usa `Dockerfile.backup` y `backup.sh`.

Puntos relevantes:
- hace backup de `itravex` e `itravex2`
- usa planificacion por `cron`
- guarda artefactos en `./backups`
- tiene politica de retencion basada en `RETENTION_DAYS`

## Produccion

### Servidor conocido
- host SSH: `production`
- usuario: `root`
- ruta desplegada: `/var/www/laravel`

### Flujo de despliegue habitual
1. `git push origin main`
2. `ssh production`
3. `cd /var/www/laravel`
4. `git pull --ff-only origin main`
5. `php artisan migrate --force`
6. `php artisan view:clear`
7. `php artisan cache:clear`

## Logs
El modulo de logs trabaja contra archivos en `storage/logs`.

Casos de uso principales:
- inspeccionar errores recientes del canal `itravex`
- filtrar por `locata`
- descargar el archivo seleccionado

## Permisos y caches
Historicamente hubo problemas en:
- `storage/framework/views`
- `bootstrap/cache`

Si reaparecen errores de escritura:
1. revisar propietario y permisos
2. corregir hacia el usuario correcto del servidor web
3. limpiar vistas compiladas y cache

## Alertas operativas
- en produccion se detectaron archivos no versionados en el checkout; conviene vigilar ese estado
- `docker-compose.yml` aun declara `version`, pero Docker Compose moderno la ignora
- cualquier despliegue que toque vistas o config deberia limpiar al menos `view:clear` y `cache:clear`
