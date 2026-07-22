#!/usr/bin/env bash
#
# Auto-despliegue de SonicStreaming Panel.
# Revisa el repositorio de GitHub y, si hay cambios nuevos, actualiza el panel.
# Lo ejecuta el cron cada minuto (lo configura install.sh). Tambien a mano:
#
#   sudo -u www-data bash /var/www/sonicstreaming/deploy.sh
#
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BRANCH="${DEPLOY_BRANCH:-main}"
export HOME="${HOME:-$APP_DIR}"

cd "$APP_DIR"

# Traer las referencias del remoto (sin modificar nada aun)
if ! git fetch --quiet origin "$BRANCH" 2>/dev/null; then
    echo "[$(date '+%F %T')] ERROR: no se pudo hacer git fetch (¿repo privado sin llave de deploy?)"
    exit 1
fi

LOCAL="$(git rev-parse HEAD)"
REMOTE="$(git rev-parse "origin/${BRANCH}")"

# Sin cambios -> salir en silencio (no ensucia el log)
if [ "$LOCAL" = "$REMOTE" ]; then
    exit 0
fi

echo "[$(date '+%F %T')] Cambios detectados ${LOCAL:0:7} -> ${REMOTE:0:7}. Desplegando..."

# El servidor es solo destino: sincronizamos el arbol con el remoto.
# (No toca archivos ignorados como .env ni storage/.)
git reset --hard "origin/${BRANCH}"

# Aplicar migraciones nuevas (idempotente; no borra datos)
php "${APP_DIR}/cron/migrate.php" 2>&1 || echo "[$(date '+%F %T')] aviso: migrate devolvio error"

echo "[$(date '+%F %T')] Despliegue OK -> $(git rev-parse --short HEAD)"
