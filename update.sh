#!/usr/bin/env bash
#
#  ███████╗ ██████╗ ███╗   ██╗██╗ ██████╗
#  ██╔════╝██╔═══██╗████╗  ██║██║██╔════╝   SonicStreaming Panel
#  ███████╗██║   ██║██╔██╗ ██║██║██║        Actualizador manual
#  ╚════██║██║   ██║██║╚██╗██║██║██║
#  ███████║╚██████╔╝██║ ╚██╗██║██║╚██████╗
#  ╚══════╝ ╚═════╝ ╚═╝  ╚═╝╚═╝ ╚═════╝
#
# Actualiza el panel desde GitHub mostrando en detalle QUE va a cambiar
# (commits, archivos, migraciones) antes de aplicarlo. Con backup previo.
#
# Uso:
#   sudo bash update.sh              # interactivo (pregunta antes de aplicar)
#   sudo bash update.sh --check      # solo muestra los cambios, no aplica
#   sudo bash update.sh -y           # aplica sin preguntar
#   sudo bash update.sh --no-backup  # omite el backup previo de la BD
#   sudo bash update.sh -b develop   # usa otra rama
#
set -uo pipefail

# ─────────────────────────── Estilo ───────────────────────────
if [ -t 1 ]; then
    B=$'\033[1m'; D=$'\033[2m'; R=$'\033[0;31m'; G=$'\033[0;32m'
    Y=$'\033[1;33m'; BL=$'\033[0;34m'; C=$'\033[0;36m'; M=$'\033[0;35m'; N=$'\033[0m'
else
    B=""; D=""; R=""; G=""; Y=""; BL=""; C=""; M=""; N=""
fi
ico_ok="${G}✔${N}"; ico_no="${R}✗${N}"; ico_dot="${C}•${N}"; ico_warn="${Y}▲${N}"

hr()   { printf '%s\n' "${D}────────────────────────────────────────────────────────────${N}"; }
title(){ echo; printf '%s\n' "${B}${C}$*${N}"; hr; }
say()  { printf '  %b %s\n' "$ico_dot" "$*"; }
ok()   { printf '  %b %s\n' "$ico_ok" "$*"; }
warn() { printf '  %b %s\n' "$ico_warn" "${Y}$*${N}"; }
die()  { printf '\n  %b %s\n\n' "$ico_no" "${R}$*${N}"; exit 1; }

# ─────────────────────────── Flags ───────────────────────────
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BRANCH="main"
ASSUME_YES=0; CHECK_ONLY=0; DO_BACKUP=1

while [ $# -gt 0 ]; do
    case "$1" in
        -y|--yes)     ASSUME_YES=1 ;;
        --check)      CHECK_ONLY=1 ;;
        --no-backup)  DO_BACKUP=0 ;;
        -b|--branch)  BRANCH="${2:-main}"; shift ;;
        -h|--help)
            cat <<'HELP'
SonicStreaming - Actualizador manual

Uso:
  sudo bash update.sh              Interactivo: muestra los cambios y pregunta.
  sudo bash update.sh --check      Solo muestra que cambiaria (no aplica nada).
  sudo bash update.sh -y | --yes   Aplica sin preguntar.
  sudo bash update.sh --no-backup  No hace backup previo de la base de datos.
  sudo bash update.sh -b <rama>    Actualiza desde otra rama (por defecto: main).
  sudo bash update.sh -h | --help  Muestra esta ayuda.
HELP
            exit 0 ;;
        *) die "Opcion desconocida: $1 (usa --help)" ;;
    esac
    shift
done

START_TS=$(date +%s)

# ─────────────────────────── Banner ───────────────────────────
clear 2>/dev/null || true
printf '%s\n' "${M}${B}"
cat <<'BANNER'
   ____              _      ____  _
  / ___|  ___  _ __ (_) ___/ ___|| |_ _ __ ___  __ _ _ __ ___
  \___ \ / _ \| '_ \| |/ __\___ \| __| '__/ _ \/ _` | '_ ` _ \
   ___) | (_) | | | | | (__ ___) | |_| | |  __/ (_| | | | | | |
  |____/ \___/|_| |_|_|\___|____/ \__|_|  \___|\__,_|_| |_| |_|
BANNER
printf '%s' "${N}"
printf '  %s\n' "${D}Actualizador manual · rama ${B}${BRANCH}${N}${D} · $(date '+%F %H:%M:%S')${N}"

# ─────────────────────── Comprobaciones ───────────────────────
title "1) Comprobaciones"
command -v git >/dev/null 2>&1 || die "git no esta instalado."
[ -d "$APP_DIR/.git" ] || die "Esto no es un repositorio git ($APP_DIR)."

REPO_OWNER="$(stat -c '%U' "$APP_DIR/.git" 2>/dev/null || echo '')"
ME="$(id -un)"
# Ejecutar git como el dueno del repo para no romper permisos
run_git() {
    if [ "$ME" = "root" ] && [ -n "$REPO_OWNER" ] && [ "$REPO_OWNER" != "root" ]; then
        sudo -u "$REPO_OWNER" git -C "$APP_DIR" "$@"
    else
        git -C "$APP_DIR" "$@"
    fi
}
run_git config --system --add safe.directory "$APP_DIR" 2>/dev/null || \
    git config --global --add safe.directory "$APP_DIR" 2>/dev/null || true

REMOTE_URL="$(run_git remote get-url origin 2>/dev/null || echo '')"
[ -n "$REMOTE_URL" ] || die "El repositorio no tiene un remoto 'origin'."
ok "Repositorio:  ${B}$APP_DIR${N}  ${D}(dueno: ${REPO_OWNER:-?})${N}"
ok "Remoto:       $REMOTE_URL"

# ────────────────────────── Fetch ─────────────────────────────
title "2) Consultando GitHub"
say "git fetch origin $BRANCH ..."
if ! run_git fetch --prune --quiet origin "$BRANCH"; then
    die "No se pudo contactar el remoto. Si el repo es privado, configura una deploy key."
fi

LOCAL="$(run_git rev-parse HEAD)"
if ! REMOTE="$(run_git rev-parse "origin/$BRANCH" 2>/dev/null)"; then
    die "La rama 'origin/$BRANCH' no existe en el remoto."
fi

CUR_SHORT="$(run_git rev-parse --short HEAD)"
CUR_DATE="$(run_git show -s --format='%ci' HEAD)"

if [ "$LOCAL" = "$REMOTE" ]; then
    ok "${G}Ya estas en la ultima version.${N}"
    say "Version actual: ${B}$CUR_SHORT${N}  ${D}($CUR_DATE)${N}"
    echo; exit 0
fi

# ─────────────────────── Detalle de cambios ───────────────────
AHEAD="$(run_git rev-list --count "HEAD..origin/$BRANCH")"
NEW_SHORT="$(run_git rev-parse --short "origin/$BRANCH")"

title "3) Cambios disponibles  ${D}(${CUR_SHORT} → ${NEW_SHORT})${N}"
printf '  %b %s\n\n' "$ico_dot" "${B}$AHEAD${N} commit(s) nuevo(s):"
run_git -c color.ui=always log --no-merges \
    --pretty=format:"    ${Y}%h${N}  %s  ${D}— %an, %ar${N}" \
    "HEAD..origin/$BRANCH" | head -40
echo; echo

# Estadistica de archivos
FILES_CHANGED="$(run_git diff --name-only "HEAD..origin/$BRANCH" | wc -l | tr -d ' ')"
printf '  %b %s\n' "$ico_dot" "${B}$FILES_CHANGED${N} archivo(s) modificado(s):"
run_git -c color.ui=always diff --stat "HEAD..origin/$BRANCH" | sed 's/^/    /' | tail -30
echo

# Avisos inteligentes
NEW_MIGRATIONS="$(run_git diff --name-only "HEAD..origin/$BRANCH" -- database/migrations/ | wc -l | tr -d ' ')"
INSTALLER_CHANGED="$(run_git diff --name-only "HEAD..origin/$BRANCH" -- install.sh | wc -l | tr -d ' ')"
[ "$NEW_MIGRATIONS" -gt 0 ]   && warn "Hay $NEW_MIGRATIONS cambio(s) en migraciones: se aplicaran a la BD."
[ "$INSTALLER_CHANGED" -gt 0 ] && warn "install.sh cambio: quizas convenga re-ejecutarlo tras actualizar."

if [ "$CHECK_ONLY" = "1" ]; then
    echo; ok "Modo --check: no se aplico ningun cambio."; echo; exit 0
fi

# ────────────────────────── Confirmar ─────────────────────────
if [ "$ASSUME_YES" != "1" ]; then
    echo
    printf '  %b %s' "$ico_warn" "${B}¿Aplicar esta actualizacion?${N} [y/N] "
    read -r ANS || ANS=""
    case "$ANS" in
        y|Y|s|S|yes|si) : ;;
        *) echo; say "Cancelado. No se cambio nada."; echo; exit 0 ;;
    esac
fi

# ───────────────────── Backup previo de la BD ─────────────────
if [ "$DO_BACKUP" = "1" ]; then
    title "4) Backup de seguridad de la base de datos"
    env_get() { sed -n "s/^$1=//p" "$APP_DIR/.env" 2>/dev/null | head -1 | sed 's/^"//;s/"$//'; }
    DB_NAME="$(env_get DB_NAME)"; DB_USER="$(env_get DB_USER)"
    DB_PASS="$(env_get DB_PASS)"; DB_HOST="$(env_get DB_HOST)"; DB_PORT="$(env_get DB_PORT)"
    if [ -n "$DB_NAME" ] && command -v mysqldump >/dev/null 2>&1; then
        mkdir -p "$APP_DIR/storage/backups"
        BK="$APP_DIR/storage/backups/pre_update_$(date +%Y%m%d_%H%M%S).sql.gz"
        args=( -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" -u "${DB_USER:-root}" )
        [ -n "$DB_PASS" ] && args+=( "-p$DB_PASS" )
        if mysqldump "${args[@]}" "$DB_NAME" 2>/dev/null | gzip > "$BK"; then
            ok "Backup creado: ${B}$BK${N}"
        else
            warn "No se pudo generar el backup (continuo de todas formas)."
            rm -f "$BK"
        fi
    else
        warn "mysqldump/.env no disponibles: se omite el backup."
    fi
fi

# ─────────────────────────── Aplicar ──────────────────────────
title "5) Aplicando actualizacion"
say "Sincronizando codigo con origin/$BRANCH ..."
if run_git reset --hard "origin/$BRANCH" >/dev/null 2>&1; then
    ok "Codigo actualizado a ${B}$NEW_SHORT${N}."
else
    die "Fallo al aplicar los cambios (git reset)."
fi

say "Aplicando migraciones de base de datos ..."
if php "$APP_DIR/cron/migrate.php" >/tmp/ss_migrate.log 2>&1; then
    ok "Migraciones al dia."
else
    warn "El migrador reporto avisos (revisa /tmp/ss_migrate.log)."
fi

# Permisos de storage (si somos root)
if [ "$ME" = "root" ] && [ -n "$REPO_OWNER" ]; then
    chown -R "$REPO_OWNER":"$REPO_OWNER" "$APP_DIR/storage" 2>/dev/null || true
fi

# Refrescar opcache recargando PHP-FPM (best-effort, solo root)
if [ "$ME" = "root" ] && command -v systemctl >/dev/null 2>&1; then
    FPM_UNIT="$(systemctl list-units --type=service --no-legend 'php*-fpm.service' 2>/dev/null | awk '{print $1}' | head -1)"
    if [ -n "$FPM_UNIT" ]; then
        systemctl reload "$FPM_UNIT" 2>/dev/null && ok "PHP-FPM recargado ($FPM_UNIT)."
    fi
fi

# ─────────────────────────── Resumen ──────────────────────────
ELAPSED=$(( $(date +%s) - START_TS ))
title "✓ Actualizacion completada"
printf '  %b %s\n' "$ico_ok" "Version:   ${D}$CUR_SHORT${N} → ${B}${G}$NEW_SHORT${N}"
printf '  %b %s\n' "$ico_ok" "Commits:   ${B}$AHEAD${N} aplicados · ${B}$FILES_CHANGED${N} archivo(s)"
printf '  %b %s\n' "$ico_ok" "Duracion:  ${B}${ELAPSED}s${N}"
[ "$INSTALLER_CHANGED" -gt 0 ] && printf '  %b %s\n' "$ico_warn" "${Y}Recuerda: install.sh cambio → 'sudo bash install.sh' si aplica.${N}"
echo
