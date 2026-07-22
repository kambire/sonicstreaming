# SonicStreaming Panel

Panel de administración de **streaming de audio** (tipo SonicPanel) en **PHP 8 + MariaDB**, sin frameworks pesados. Administra clientes, planes, revendedores y estaciones **Shoutcast DNAS v2**, con **AutoDJ (Liquidsoap)**, estadísticas en vivo y facturación con auto‑suspensión.

> ⚠️ **Diseñado para producción sobre Ubuntu Server** (20.04 / 22.04 / 24.04 LTS).
> Trae un instalador desatendido (`install.sh`) que deja todo funcionando por **HTTPS en el puerto 7000**.
> *(Opcionalmente puede desarrollarse en XAMPP/Windows en modo simulado — ver el final.)*

---

## Índice

1. [Requisitos](#requisitos)
2. [Instalación en Ubuntu Server (automática)](#instalación-en-ubuntu-server-automática)
3. [Qué hace el instalador](#qué-hace-el-instalador)
4. [Primer acceso](#primer-acceso)
5. [Después de instalar (operación)](#después-de-instalar-operación)
6. [Actualizar el panel](#actualizar-el-panel)
7. [Instalación manual (opcional)](#instalación-manual-opcional)
8. [Cómo funciona el streaming](#cómo-funciona-el-streaming)
9. [Seguridad](#seguridad)
10. [Desarrollo local (XAMPP/Windows)](#desarrollo-local-xamppwindows)

---

## Requisitos

- **Ubuntu Server 20.04, 22.04 o 24.04 LTS** (instalación limpia recomendada).
- Acceso **root / sudo**.
- Conexión a internet (para instalar paquetes).
- **Shoutcast DNAS v2** (`sc_serv`) — binario propietario y gratuito; el instalador lo intenta descargar y, si no puede, te indica cómo colocarlo.

El instalador se encarga del resto: **Nginx, PHP‑FPM, MariaDB y Liquidsoap**. No necesitas Composer (el autoloader y el lector de `.env` son propios).

---

## Instalación en Ubuntu Server (automática)

Conéctate por SSH a tu servidor y ejecuta:

```bash
sudo apt update && sudo apt install -y git
```

```bash
sudo git clone https://github.com/kambire/sonicstreaming.git /var/www/sonicstreaming
```

```bash
cd /var/www/sonicstreaming && sudo bash install.sh
```

Eso es todo. Al terminar verás un resumen como este:

```
============================================================
 Instalacion completada
============================================================
 Panel:        https://TU_IP:7000/
   (certificado autofirmado: el navegador mostrara un aviso la 1a vez)
 Usuario:      admin@sonic.local
 Contrasena:   XXXXXXXXXXXX   (guardala; cambiala en el panel)

 Base de datos: sonicstreaming
 DB usuario:    sonic
 DB password:   ....... (guardada en /var/www/sonicstreaming/.env)
============================================================
```

> **Puerto personalizado:** por defecto usa el **7000**. Para otro puerto:
> ```bash
> sudo WEB_PORT=8443 bash install.sh
> ```

### Shoutcast DNAS

`sc_serv` es propietario. El instalador intenta descargarlo automáticamente; si no lo logra, verás un aviso. En ese caso descárgalo (Linux x64) de <https://www.shoutcast.com/broadcast-tools> y colócalo así:

```bash
sudo mkdir -p /opt/shoutcast
sudo cp sc_serv /opt/shoutcast/sc_serv
sudo chmod +x /opt/shoutcast/sc_serv
sudo chown -R www-data:www-data /opt/shoutcast
```

El panel, el AutoDJ, las estadísticas y la facturación funcionan sin él; solo el **arranque de estaciones** lo necesita.

---

## Qué hace el instalador

| Componente | Detalle |
|---|---|
| **Nginx** | Sitio en **HTTPS puerto 7000** con certificado **autofirmado**; redirige `http→https`; cabeceras de seguridad. |
| **PHP‑FPM** | Detecta la versión (8.1/8.3), configura el socket, sube límites de subida a **200 MB** y endurece `display_errors=Off`, `expose_php=Off`. |
| **MariaDB** | Crea la base `sonicstreaming` y el usuario `sonic` con **contraseña aleatoria**. |
| **Liquidsoap** | Instalado por `apt` para el AutoDJ. |
| **.env de producción** | `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` y `DB_PASS` **aleatorios**, `SHOUTCAST_DRIVER=linux`. |
| **Base de datos** | Migraciones + datos iniciales (admin con contraseña aleatoria, servidor local, planes demo). |
| **systemd** | Unidades plantilla `shoutcast@<id>` y `liquidsoap@<id>` (una por estación). |
| **sudoers** | Permite a `www-data` iniciar/detener/reiniciar esas unidades sin contraseña. |
| **Cron** | Estadísticas (cada minuto), facturación (diaria), **backup de BD** (diario) y **restauración tras reinicio**. |
| **logrotate** | Rotación semanal de los logs del panel. |
| **Firewall (ufw)** | Abre el 7000 y el rango de estaciones 8000–8100 (si `ufw` está activo). |

Es **idempotente**: si lo vuelves a ejecutar, **conserva** tu `APP_KEY` y `DB_PASS` (no rompe las contraseñas cifradas de las estaciones) y **no** resetea la contraseña del admin.

---

## Primer acceso

1. Abre **`https://TU_IP:7000/`** en el navegador.
2. Como el certificado es autofirmado, el navegador avisa "conexión no privada" la primera vez → **Avanzado → Continuar**. A partir de ahí el tráfico va cifrado.
3. Inicia sesión con `admin@sonic.local` y la **contraseña que imprimió el instalador**.
4. Ve a **Mi perfil → Cambiar contraseña** y define la tuya.

---

## Después de instalar (operación)

Comandos útiles en el servidor:

```bash
# Estado de una estación (id 12) y su AutoDJ
sudo systemctl status shoutcast@12 liquidsoap@12
```

```bash
# Ver logs del panel
tail -f /var/www/sonicstreaming/storage/logs/*.log
```

```bash
# Logs de una instancia concreta
journalctl -u shoutcast@12 -f
journalctl -u liquidsoap@12 -f
```

- **Estadísticas:** se recogen solas cada minuto (`cron/poll_stats.php`).
- **Facturación:** corre a diario a las 03:00 (marca vencidas y suspende estaciones impagas).
- **Backups:** `mysqldump` diario a las 04:00 en `storage/backups/` (conserva 7 días).
- **Reinicios:** tras reiniciar el servidor, las estaciones que estaban "en línea" se vuelven a levantar solas (`cron/boot_restore.php`).

---

## Actualizar el panel

Desde tu PC subes cambios a GitHub (`git push`) y en el servidor:

```bash
cd /var/www/sonicstreaming && sudo git pull && sudo bash install.sh
```

Volver a correr `install.sh` es seguro (idempotente): re‑aplica configuración y migraciones sin perder secretos ni datos.

---

## Instalación manual (opcional)

Si prefieres no usar `install.sh`, los archivos de ejemplo están en `deploy/`:

1. Sube el proyecto a `/var/www/sonicstreaming` y crea `.env` (basado en `.env.example`) con `SHOUTCAST_DRIVER=linux` y credenciales de BD.
2. Web server con DocumentRoot en `public/`: `deploy/nginx.conf.example` o `deploy/apache-vhost.conf.example`.
3. Base de datos: `php cron/migrate.php`.
4. Instala Shoutcast (`/opt/shoutcast/sc_serv`) y Liquidsoap (`apt install liquidsoap`).
5. Unidades systemd y sudoers: `deploy/shoutcast@.service`, `deploy/liquidsoap@.service`, `deploy/sonicstreaming.sudoers`.
6. Cron: `deploy/crontab.example`.
7. Permisos: `sudo chown -R www-data:www-data storage`.

---

## Cómo funciona el streaming

Cada **estación** es una instancia de `sc_serv` en un puerto propio (y `puerto+1` para admin). El control de procesos usa el **driver** del servidor:

| Driver    | Uso                | Qué hace |
|-----------|--------------------|----------|
| `linux`   | **Producción**     | `systemctl start/stop/restart shoutcast@<id>` / `liquidsoap@<id>`. |
| `windows` | Dev con Shoutcast  | Lanza `sc_serv.exe` / `liquidsoap.exe` (`proc_open`). |
| `mock`    | Desarrollo         | Simula start/stop con un archivo marcador e inventa estadísticas. |

Las estadísticas se leen del DNAS por `http://host:puerto/statistics?json=1`.

### AutoDJ (Liquidsoap)

Con AutoDJ habilitado, cada estación genera un script `.liq` que:
- reproduce las playlists activas (rotación aleatoria ponderada),
- expone un **harbor** para DJ en vivo en `dj_port` (= puerto + 10000),
- hace `fallback(track_sensitive=false, [live, autodj])` (el DJ en vivo tiene prioridad; al terminar vuelve el AutoDJ),
- envía el audio a `sc_serv` como fuente (`output.shoutcast`).

---

## Seguridad

- Solo `public/` se expone por web; el `.env`, `storage/` y los `.conf`/`.liq` quedan bloqueados.
- Contraseñas con `password_hash` (bcrypt); las contraseñas admin de Shoutcast se guardan **cifradas** (AES‑256) con `APP_KEY`.
- **CSRF** en todos los formularios, **RBAC** por middleware, **PDO** con sentencias preparadas, cookie de sesión `secure`+`httponly` bajo HTTPS.
- Cambia la contraseña del admin en el primer acceso.

---

## Desarrollo local (XAMPP / Windows)

Solo para desarrollar; **no es el entorno de producción**.

1. Proyecto en `C:\xampp\htdocs\sonicstreaming`, copia `.env.example` a `.env` (deja `SHOUTCAST_DRIVER=mock`).
2. Crea la BD y datos: `C:\xampp\php\php.exe cron\migrate.php`.
3. Abre **http://localhost/sonicstreaming/public/** — admin `admin@sonic.local` / `admin123`.
4. Puebla estadísticas de prueba: `C:\xampp\php\php.exe cron\poll_stats.php` (en `mock` inventa datos).

---

## Estructura

```
public/        # unica carpeta expuesta (front controller + assets)
app/
  Core/        # Router, Database(PDO), Auth, Csrf, View, Model...
  Controllers/ # Admin/ Reseller/ Client/ Api/ + Base AutoDJ + Profile
  Models/      # User, Server, Plan, Station, Invoice, Media, Playlist...
  Services/    # ShoutcastService, AutoDjService, BillingService, Crypto
  Process/     # ProcessController + Mock/Windows/Linux drivers
  Views/       # vistas PHP + layouts
config/        # routes.php
database/      # migrations/*.sql
cron/          # migrate, poll_stats, billing_run, boot_restore, db_backup
templates/     # sc_serv.conf.tpl
storage/       # configs (.conf/.liq), media, pids, logs, backups
deploy/        # systemd, sudoers, cron, nginx/apache de ejemplo
install.sh     # instalador para Ubuntu Server
```
