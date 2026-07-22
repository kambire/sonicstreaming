#!/usr/bin/env bash
#
# SonicStreaming Panel - Instalador para Ubuntu (20.04 / 22.04 / 24.04)
# Deja el panel funcionando en http://<IP>:7000
#
# Uso:
#   sudo bash install.sh
#
# Instala: Nginx + PHP-FPM + MariaDB + Liquidsoap (AutoDJ), configura la base
# de datos, el .env de produccion, unidades systemd para Shoutcast/Liquidsoap,
# cron y el sitio web en el puerto 7000.
#
set -euo pipefail

# ==========================================================================
# Configuracion (puedes ajustar estas variables antes de ejecutar)
# ==========================================================================
WEB_PORT="${WEB_PORT:-7000}"
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WEB_USER="www-data"

DB_NAME="${DB_NAME:-sonicstreaming}"
DB_USER="${DB_USER:-sonic}"
DB_PASS="${DB_PASS:-$(openssl rand -hex 16)}"
APP_KEY="$(openssl rand -hex 32)"
APP_TZ="${APP_TZ:-America/Bogota}"

SHOUTCAST_DIR="/opt/shoutcast"
# Auto-deploy: el cron revisa GitHub cada minuto y actualiza si hay cambios.
# Ponlo en 0 para desactivarlo:  sudo AUTO_DEPLOY=0 bash install.sh
AUTO_DEPLOY="${AUTO_DEPLOY:-1}"
# HTTPS con certificado autofirmado (no requiere puerto 80 ni dominio).
SSL_DIR="/etc/ssl/sonicstreaming"
SSL_CERT="${SSL_DIR}/cert.pem"
SSL_KEY="${SSL_DIR}/key.pem"
# Rango de puertos para las estaciones (NO debe incluir el $WEB_PORT).
PORT_RANGE_START=8000
PORT_RANGE_END=8100
# Intento de descarga automatica del DNAS (best-effort). Vacia para omitir.
SHOUTCAST_URL="${SHOUTCAST_URL:-http://download.nullsoft.com/shoutcast/tools/sc_serv2_linux_x64-latest.tar.gz}"

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'
info()  { echo -e "${GREEN}[ok]${NC} $*"; }
warn()  { echo -e "${YELLOW}[!!]${NC} $*"; }
step()  { echo -e "\n${GREEN}==>${NC} $*"; }

# ==========================================================================
# 0. Verificaciones previas
# ==========================================================================
if [ "$(id -u)" -ne 0 ]; then
    echo -e "${RED}Debes ejecutar con sudo:  sudo bash install.sh${NC}"; exit 1
fi
if ! grep -qi ubuntu /etc/os-release 2>/dev/null; then
    warn "Este script esta pensado para Ubuntu. Continuo de todas formas..."
fi
export DEBIAN_FRONTEND=noninteractive
echo "Directorio de la app: $APP_DIR"
echo "Puerto del panel:      $WEB_PORT"

# Reutilizar secretos si ya existe un .env (re-ejecuciones idempotentes).
# IMPORTANTE: regenerar APP_KEY invalidaria las contrasenas admin cifradas
# de las estaciones ya creadas, por eso se conserva.
if [ -f "$APP_DIR/.env" ]; then
    _k="$(sed -n 's/^APP_KEY=//p' "$APP_DIR/.env" | head -1)"
    _p="$(sed -n 's/^DB_PASS=//p' "$APP_DIR/.env" | head -1)"
    if [ -n "$_k" ]; then APP_KEY="$_k"; echo "Reutilizando APP_KEY existente."; fi
    if [ -n "$_p" ]; then DB_PASS="$_p"; echo "Reutilizando DB_PASS existente."; fi
fi

# ==========================================================================
# 1. Paquetes del sistema
# ==========================================================================
step "Actualizando indices de apt e instalando dependencias..."
apt-get update -y
apt-get install -y \
    nginx mariadb-server \
    php-fpm php-cli php-mysql php-curl php-mbstring php-xml php-zip \
    unzip curl openssl ca-certificates

# Liquidsoap (AutoDJ) - best effort (esta en el repo "universe")
step "Instalando Liquidsoap (AutoDJ)..."
if apt-get install -y liquidsoap; then
    LIQUIDSOAP_BIN="$(command -v liquidsoap || echo /usr/bin/liquidsoap)"
    info "Liquidsoap instalado en $LIQUIDSOAP_BIN"
else
    LIQUIDSOAP_BIN="/usr/bin/liquidsoap"
    warn "No se pudo instalar Liquidsoap por apt. El AutoDJ no funcionara hasta instalarlo."
fi

# Detectar version de PHP y socket de PHP-FPM
PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
FPM_SOCK="/run/php/php${PHP_VER}-fpm.sock"
FPM_SERVICE="php${PHP_VER}-fpm"
info "PHP ${PHP_VER} detectado (socket: ${FPM_SOCK})"

systemctl enable --now mariadb >/dev/null 2>&1 || true
systemctl enable --now "${FPM_SERVICE}" >/dev/null 2>&1 || true

# ==========================================================================
# 2. Base de datos
# ==========================================================================
step "Creando base de datos y usuario..."
mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL
info "Base de datos '${DB_NAME}' y usuario '${DB_USER}' listos."

# ==========================================================================
# 3. Archivo .env de produccion
# ==========================================================================
step "Generando .env de produccion..."
if [ -f "$APP_DIR/.env" ]; then
    cp "$APP_DIR/.env" "$APP_DIR/.env.bak.$(date +%s)"
    warn "Se respaldo el .env anterior."
fi
cat > "$APP_DIR/.env" <<ENV
APP_NAME="SonicStreaming Panel"
APP_ENV=production
APP_DEBUG=false
APP_KEY=${APP_KEY}
APP_TIMEZONE=${APP_TZ}

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}

SHOUTCAST_DRIVER=linux
SHOUTCAST_BIN="${SHOUTCAST_DIR}/sc_serv"
SHOUTCAST_HOST=127.0.0.1

LIQUIDSOAP_BIN="${LIQUIDSOAP_BIN}"
AUTODJ_ALLOWED_EXT="mp3,aac,m4a,ogg,flac,wav"
ENV
chmod 640 "$APP_DIR/.env"
info ".env generado."

# ==========================================================================
# 4. Permisos y carpetas de trabajo
# ==========================================================================
step "Preparando permisos..."
mkdir -p "$APP_DIR"/storage/{configs,pids,logs,media,backups}
chown -R "${WEB_USER}:${WEB_USER}" "$APP_DIR"
find "$APP_DIR/storage" -type d -exec chmod 775 {} \;
chown "${WEB_USER}:${WEB_USER}" "$APP_DIR/.env"
# Git seguro para el usuario web (necesario para el auto-deploy) y scripts ejecutables
if command -v git >/dev/null 2>&1; then
    git config --system --add safe.directory "$APP_DIR" 2>/dev/null || true
fi
[ -f "$APP_DIR/deploy.sh" ] && chmod +x "$APP_DIR/deploy.sh"
info "Permisos aplicados (propietario ${WEB_USER})."

# ==========================================================================
# 5. Migraciones + seed
# ==========================================================================
step "Ejecutando migraciones y datos iniciales..."
# Instalacion nueva -> generar una contrasena de admin aleatoria (produccion).
ADMIN_COUNT="$(mysql -N -u root "${DB_NAME}" -e "SELECT COUNT(*) FROM users WHERE role='admin'" 2>/dev/null || echo '')"
if [ -z "${ADMIN_COUNT}" ] || [ "${ADMIN_COUNT}" = "0" ]; then
    FRESH_ADMIN=1
    ADMIN_PASSWORD="$(openssl rand -base64 12 | tr -dc 'A-Za-z0-9' | cut -c1-14)"
else
    FRESH_ADMIN=0
    ADMIN_PASSWORD=""
fi
sudo -u "${WEB_USER}" env ADMIN_EMAIL="admin@sonic.local" ADMIN_PASSWORD="${ADMIN_PASSWORD}" \
    php "$APP_DIR/cron/migrate.php"

# Asegurar que el servidor sembrado use el rango de puertos correcto
mysql -u root "${DB_NAME}" <<SQL || true
UPDATE servers SET driver='linux', hostname='127.0.0.1',
       port_range_start=${PORT_RANGE_START}, port_range_end=${PORT_RANGE_END}
 WHERE id=1;
SQL

# ==========================================================================
# 6. Ajustes de PHP (subida de audio para el AutoDJ)
# ==========================================================================
step "Ajustando limites de subida de PHP..."
PHP_INI_DIR="/etc/php/${PHP_VER}/fpm/conf.d"
if [ -d "$PHP_INI_DIR" ]; then
cat > "${PHP_INI_DIR}/99-sonicstreaming.ini" <<INI
upload_max_filesize = 200M
post_max_size = 210M
max_execution_time = 120
memory_limit = 256M
; Endurecimiento produccion
display_errors = Off
log_errors = On
expose_php = Off
session.cookie_httponly = 1
session.cookie_samesite = "Lax"
INI
    info "Limites y hardening de PHP configurados (200M por archivo)."
fi

# ==========================================================================
# 7. Shoutcast DNAS (sc_serv)
# ==========================================================================
step "Preparando Shoutcast DNAS..."
mkdir -p "$SHOUTCAST_DIR"
if [ ! -x "${SHOUTCAST_DIR}/sc_serv" ] && [ -n "$SHOUTCAST_URL" ]; then
    warn "Intentando descargar Shoutcast DNAS (best-effort)..."
    if curl -fsSL --connect-timeout 20 -o /tmp/sc_serv.tar.gz "$SHOUTCAST_URL" 2>/dev/null; then
        tar -xzf /tmp/sc_serv.tar.gz -C "$SHOUTCAST_DIR" 2>/dev/null || true
        rm -f /tmp/sc_serv.tar.gz
        [ -x "${SHOUTCAST_DIR}/sc_serv" ] && chmod +x "${SHOUTCAST_DIR}/sc_serv"
    fi
fi
if [ -x "${SHOUTCAST_DIR}/sc_serv" ]; then
    chown -R "${WEB_USER}:${WEB_USER}" "$SHOUTCAST_DIR"
    info "sc_serv disponible en ${SHOUTCAST_DIR}/sc_serv"
else
    warn "No se instalo sc_serv automaticamente."
    warn "Descarga Shoutcast DNAS 2 (Linux x64) desde https://www.shoutcast.com/broadcast-tools"
    warn "y coloca el binario en ${SHOUTCAST_DIR}/sc_serv (chmod +x). Luego las estaciones podran iniciarse."
fi

# ==========================================================================
# 8. Unidades systemd (una por estacion) + sudoers
# ==========================================================================
step "Instalando unidades systemd para Shoutcast y Liquidsoap..."
cat > /etc/systemd/system/shoutcast@.service <<UNIT
[Unit]
Description=Shoutcast DNAS - estacion %i
After=network.target

[Service]
Type=simple
User=${WEB_USER}
Group=${WEB_USER}
WorkingDirectory=${SHOUTCAST_DIR}
ExecStart=${SHOUTCAST_DIR}/sc_serv ${APP_DIR}/storage/configs/station_%i.conf
Restart=on-failure
RestartSec=3

[Install]
WantedBy=multi-user.target
UNIT

cat > /etc/systemd/system/liquidsoap@.service <<UNIT
[Unit]
Description=Liquidsoap AutoDJ - estacion %i
After=network.target shoutcast@%i.service
Wants=shoutcast@%i.service

[Service]
Type=simple
User=${WEB_USER}
Group=${WEB_USER}
ExecStart=${LIQUIDSOAP_BIN} ${APP_DIR}/storage/configs/station_%i.liq
Restart=on-failure
RestartSec=3

[Install]
WantedBy=multi-user.target
UNIT

# Permitir al usuario web controlar las unidades sin contrasena
cat > /etc/sudoers.d/sonicstreaming <<SUDO
${WEB_USER} ALL=(root) NOPASSWD: /usr/bin/systemctl start shoutcast@*, /usr/bin/systemctl stop shoutcast@*, /usr/bin/systemctl restart shoutcast@*
${WEB_USER} ALL=(root) NOPASSWD: /usr/bin/systemctl start liquidsoap@*, /usr/bin/systemctl stop liquidsoap@*, /usr/bin/systemctl restart liquidsoap@*
SUDO
chmod 440 /etc/sudoers.d/sonicstreaming
systemctl daemon-reload
info "Unidades systemd y sudoers instalados."

# ==========================================================================
# 9. Certificado autofirmado + Nginx (HTTPS) en el puerto ${WEB_PORT}
# ==========================================================================
step "Generando certificado autofirmado (HTTPS sin puerto 80)..."
SERVER_IP="$(hostname -I 2>/dev/null | awk '{print $1}')"
HOSTN="$(hostname -f 2>/dev/null || hostname)"
mkdir -p "$SSL_DIR"
if [ ! -f "$SSL_CERT" ] || [ ! -f "$SSL_KEY" ]; then
    SAN="DNS:${HOSTN}"
    [ -n "${SERVER_IP:-}" ] && SAN="${SAN},IP:${SERVER_IP}"
    openssl req -x509 -nodes -newkey rsa:2048 -days 3650 \
        -keyout "$SSL_KEY" -out "$SSL_CERT" \
        -subj "/CN=${HOSTN}" -addext "subjectAltName=${SAN}" >/dev/null 2>&1
    chmod 600 "$SSL_KEY"
    info "Certificado autofirmado creado en ${SSL_CERT}"
else
    info "Certificado ya existente, se reutiliza."
fi

step "Configurando Nginx (HTTPS) en el puerto ${WEB_PORT}..."
cat > /etc/nginx/sites-available/sonicstreaming <<NGINX
server {
    listen ${WEB_PORT} ssl;
    listen [::]:${WEB_PORT} ssl;
    server_name _;

    ssl_certificate     ${SSL_CERT};
    ssl_certificate_key ${SSL_KEY};
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    server_tokens off;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header Referrer-Policy "same-origin" always;

    # Si alguien entra por http://IP:${WEB_PORT} lo redirige a https
    error_page 497 =301 https://\$host:${WEB_PORT}\$request_uri;

    root ${APP_DIR}/public;
    index index.php;

    client_max_body_size 200M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:${FPM_SOCK};
        fastcgi_param HTTPS on;
    }

    location ~ /\.(?!well-known).* { deny all; }
    location ~* \.(env|conf|liq|sql|log|tpl)\$ { deny all; }
}
NGINX
ln -sf /etc/nginx/sites-available/sonicstreaming /etc/nginx/sites-enabled/sonicstreaming
nginx -t
systemctl reload nginx
info "Nginx sirviendo HTTPS en el puerto ${WEB_PORT}."

# ==========================================================================
# 10. Cron (estadisticas + facturacion)
# ==========================================================================
step "Instalando script de backup, rotacion de logs y cron..."

# Script de backup de la base de datos (mysqldump como root por socket).
cat > "${APP_DIR}/cron/db_backup.sh" <<BACKUP
#!/usr/bin/env bash
set -e
DIR="${APP_DIR}/storage/backups"
mkdir -p "\$DIR"
mysqldump ${DB_NAME} | gzip > "\$DIR/db_\$(date +%Y%m%d_%H%M).sql.gz"
# Conservar solo 7 dias
find "\$DIR" -name 'db_*.sql.gz' -mtime +7 -delete
BACKUP
chmod +x "${APP_DIR}/cron/db_backup.sh"

# Rotacion de logs del panel
cat > /etc/logrotate.d/sonicstreaming <<ROT
${APP_DIR}/storage/logs/*.log {
    weekly
    rotate 8
    compress
    missingok
    notifempty
    copytruncate
}
ROT

cat > /etc/cron.d/sonicstreaming <<CRON
# Poller de estadisticas: cada minuto
* * * * * ${WEB_USER} php ${APP_DIR}/cron/poll_stats.php >> ${APP_DIR}/storage/logs/poll.log 2>&1
# Facturacion diaria a las 03:00
0 3 * * * ${WEB_USER} php ${APP_DIR}/cron/billing_run.php >> ${APP_DIR}/storage/logs/billing.log 2>&1
# Backup de la base de datos a las 04:00
0 4 * * * root ${APP_DIR}/cron/db_backup.sh >> ${APP_DIR}/storage/logs/backup.log 2>&1
# Restaurar estaciones "en linea" tras un reinicio del servidor
@reboot ${WEB_USER} sleep 20 && php ${APP_DIR}/cron/boot_restore.php >> ${APP_DIR}/storage/logs/boot.log 2>&1
CRON
chmod 644 /etc/cron.d/sonicstreaming
info "Backup, logrotate y cron instalados."

# Auto-deploy desde GitHub (sondeo cada minuto)
if [ "${AUTO_DEPLOY}" = "1" ] && [ -f "${APP_DIR}/deploy.sh" ]; then
    cat >> /etc/cron.d/sonicstreaming <<CRON2
# Auto-deploy: revisa GitHub cada minuto y actualiza si hay cambios
* * * * * ${WEB_USER} /usr/bin/flock -n /tmp/ss_deploy.lock ${APP_DIR}/deploy.sh >> ${APP_DIR}/storage/logs/deploy.log 2>&1
CRON2
    info "Auto-deploy ACTIVADO (revisa GitHub cada minuto)."
fi

# ==========================================================================
# 11. Firewall (si ufw esta activo)
# ==========================================================================
if command -v ufw >/dev/null 2>&1 && ufw status | grep -qi active; then
    step "Abriendo puertos en ufw..."
    ufw allow "${WEB_PORT}/tcp" >/dev/null 2>&1 || true
    ufw allow "${PORT_RANGE_START}:${PORT_RANGE_END}/tcp" >/dev/null 2>&1 || true
    info "Puertos ${WEB_PORT} y ${PORT_RANGE_START}-${PORT_RANGE_END} permitidos."
fi

# ==========================================================================
# Resumen
# ==========================================================================
SERVER_IP="${SERVER_IP:-$(hostname -I 2>/dev/null | awk '{print $1}')}"
echo -e "\n${GREEN}============================================================${NC}"
echo -e "${GREEN} Instalacion completada${NC}"
echo -e "${GREEN}============================================================${NC}"
echo -e " Panel:        ${YELLOW}https://${SERVER_IP:-<IP-DEL-SERVIDOR>}:${WEB_PORT}/${NC}"
echo -e "   ${YELLOW}(certificado autofirmado: el navegador mostrara un aviso la 1a vez,${NC}"
echo -e "   ${YELLOW} acepta 'Avanzado -> Continuar' y quedara cifrado)${NC}"
echo -e " Usuario:      admin@sonic.local"
if [ "${FRESH_ADMIN:-0}" = "1" ] && [ -n "${ADMIN_PASSWORD:-}" ]; then
    echo -e " Contrasena:   ${YELLOW}${ADMIN_PASSWORD}${NC}   ${RED}(guardala; cambiala en el panel)${NC}"
else
    echo -e " Contrasena:   (sin cambios; usa la que ya definiste)"
fi
echo ""
echo -e " Base de datos: ${DB_NAME}"
echo -e " DB usuario:    ${DB_USER}"
echo -e " DB password:   ${DB_PASS}"
echo -e "   (guardada tambien en ${APP_DIR}/.env)"
echo ""
if [ "${AUTO_DEPLOY}" = "1" ]; then
    echo -e " Auto-deploy:  ${GREEN}ACTIVADO${NC} (revisa GitHub y actualiza cada minuto)"
fi
if [ ! -x "${SHOUTCAST_DIR}/sc_serv" ]; then
    echo -e " ${YELLOW}Pendiente:${NC} coloca el binario Shoutcast en ${SHOUTCAST_DIR}/sc_serv"
    echo -e "            para poder iniciar estaciones."
fi
echo -e "${GREEN}============================================================${NC}"
