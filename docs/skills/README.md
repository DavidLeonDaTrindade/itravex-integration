# Skills para Codex

## Objetivo
Esta carpeta documenta las skills relevantes para trabajar sobre `itravex-integration` sin inflar el contexto en cada conversacion.

## Regla base
- Las skills reales instalables viven en `~/.codex/skills/`.
- Esta carpeta no duplica skills; solo documenta estado, criterio de uso y backlog de skills futuras para este proyecto.

## Estado actual
- `skill-installer`: disponible como skill del sistema. Sirve para consultar e instalar skills oficiales o desde GitHub.
- `skill-creator`: disponible como skill del sistema. Sirve para crear skills propias cuando un flujo merece encapsularse.
- `openai-docs`: disponible como skill del sistema. Util para dudas de producto OpenAI, no para la logica diaria de este proyecto.

## Flujo recomendado
1. Consultar `requested-skills.md` para ver que skills merecen la pena en este repo.
2. Usar `skill-installer` solo para skills que existan realmente y aporten valor claro.
3. Si una skill deseada no existe en catalogo oficial pero resuelve un flujo repetitivo del repo, crearla con `skill-creator`.

## Candidatas futuras mas razonables para este repo
- Skill de despliegue y validacion Laravel con Docker Compose
- Skill de GIATA para import, sync y troubleshooting
- Skill de claim confirmations y SAMO
- Skill de conexion dinamica `mysql` / `mysql_cli2`
