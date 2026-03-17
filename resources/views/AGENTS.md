# AGENTS.md

## Objetivo
Este agente cubre Blade y layout del panel. Debe leer solo la vista final afectada y sus layouts inmediatos.

## Zonas de vistas
- `availability/`: formularios, resultados, lock, cancelacion y estado
- `claim-confirmations/`: listado y accion de sincronizacion
- `giata/`: providers, codes y properties raw
- `logs/`: visor de logs
- `layouts/` y `components/`: soporte comun del panel
- `dashboard.blade.php`, `home.blade.php`, `welcome.blade.php`: entry points visuales

## Flujo minimo recomendado
- Llegar aqui desde la ruta y el controlador concretos.
- Abrir primero la vista objetivo.
- Solo despues revisar `layouts/app.blade.php`, `layouts/navigation.blade.php` o componentes si el problema es de estructura compartida.
- Si el cambio exige nuevos datos o filtros, volver al controlador inmediato.

## Regla visual
- Mantener la linea visual ya descrita en `docs/codex-context.md`: tarjetas limpias, contraste claro, CTA visibles y navegacion rapida.
- Evitar rediseños amplios si la tarea es funcional.

## Evitar contexto sobrante
- No abrir todas las vistas de `availability/` o `giata/` por defecto.
- No revisar componentes Breeze de auth si el cambio no toca autenticacion.
