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
| **Auto-deploy** | Revisa GitHub cada minuto y **actualiza el panel solo** al detectar cambios en `main` (desactivable con `AUTO_DEPLOY=0`). |
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

### Automático (auto-deploy, activado por defecto)

El instalador deja funcionando el **auto-deploy**: el servidor revisa GitHub **cada minuto** y, si hay un commit nuevo en `main`, se actualiza solo (sincroniza el código con el remoto y aplica migraciones). Tú solo haces `git push` desde tu PC:

```bash
git add -A && git commit -m "mis cambios" && git push
```

En ~1 minuto los cambios están en el servidor. Para ver el registro:

```bash
tail -f /var/www/sonicstreaming/storage/logs/deploy.log
```

Forzar un despliegue inmediato (sin esperar al minuto):

```bash
sudo -u www-data bash /var/www/sonicstreaming/deploy.sh
```

Para **desactivarlo**: reinstala con `sudo AUTO_DEPLOY=0 bash install.sh` o borra la línea de `deploy` en `/etc/cron.d/sonicstreaming`.

> **Repositorio privado:** el auto-pull necesita acceso de lectura sin interacción. Si tu repo es privado, agrega una *deploy key* de solo lectura:
> ```bash
> sudo -u www-data ssh-keygen -t ed25519 -f /var/www/.ssh/id_ed25519 -N ""
> sudo -u www-data cat /var/www/.ssh/id_ed25519.pub   # pégala en GitHub → repo → Settings → Deploy keys
> cd /var/www/sonicstreaming && sudo -u www-data git remote set-url origin git@github.com:kambire/sonicstreaming.git
> ```

### Manual con detalle de cambios — `update.sh`

Si prefieres actualizar tú mismo **viendo exactamente qué va a cambiar** antes de aplicarlo:

```bash
cd /var/www/sonicstreaming && sudo bash update.sh
```

Al ejecutarlo **sin argumentos** muestra un **menú interactivo** con estas opciones seleccionables:

```
¿Qué deseas hacer?
    1)  Ver cambios disponibles      (no aplica nada)
    2)  Actualizar el panel          (con backup y confirmación)
    3)  Actualizar sin confirmar     (rápido, con backup)
    4)  Actualizar sin backup        (pide confirmación)
    5)  Actualizar desde otra rama
    0)  Salir
```

Además muestra los **commits nuevos**, los **archivos modificados** y avisa si hay **migraciones** o si cambió `install.sh`, con **backup previo** de la base de datos.

También puedes saltarte el menú con flags (útil para scripts): `--check`, `-y`/`--yes`, `--no-backup`, `-b <rama>`, `-h`/`--help`.

Alternativa mínima: `sudo -u www-data git pull && sudo bash install.sh` (reinstalar es idempotente y no pierde secretos ni datos).

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
deploy.sh      # auto-deploy (lo llama el cron)
update.sh      # actualizador manual con menu interactivo
```

---
---

# 🛠️ Guía de desarrollo (referencia técnica)

> **Esta sección es la referencia para continuar el desarrollo en futuras sesiones/conversaciones.**
> Resume la arquitectura interna, convenciones y "recetas" para extender el panel sin tener que releer todo el código.

## Filosofía y stack

- **PHP 8 vanilla con MVC ligero propio, sin framework.** El objetivo es que se despliegue en cualquier hosting con PHP + MySQL sin dependencias.
- **Sin Composer obligatorio:** autoloader PSR‑4 propio y lector de `.env` propio (`app/Core/Env.php`). `composer.json` existe solo para autoload opcional.
- **UI:** Bootstrap 5 + Bootstrap Icons + Chart.js (por CDN). Tema oscuro en `public/assets/css/app.css`.
- **Base de datos:** PDO + MariaDB/MySQL, siempre con sentencias preparadas.

## Flujo de una petición

```
public/index.php  (inicia sesión + bootstrap)
  └─ bootstrap.php  (autoloader App\ → app/, carga .env, helpers)
      └─ config/routes.php  (Router)
          └─ middlewares (Auth / Role / Csrf)
              └─ App\Controllers\...@metodo
                  ├─ Models (PDO)  /  Services
                  └─ View::render(vista, datos, layout) → HTML
```

`base_url()` se calcula desde `SCRIPT_NAME`, por eso funciona igual en subcarpeta (XAMPP `/sonicstreaming/public`) que en la raíz (producción con docroot en `public/`).

## Núcleo — `app/Core/`

| Clase | Responsabilidad / métodos clave |
|-------|--------------------------------|
| `Env` | `Env::load($path)`, `Env::get($k,$def)`. Parser `.env` propio. |
| `helpers.php` | Funciones globales: `env()`, `e()`, `url()`, `asset()`, `base_url()`, `redirect()`, `auth()`, `flash()/set_flash()`, `old()`, `random_password()`, `money()`, `human_size()`, `status_badge()`, `nav_active()`. |
| `Database` | `Database::connection()` → PDO singleton (utf8mb4, `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, sin emular prepares). |
| `Model` | Base tipo ActiveRecord. Cada modelo define `static $table`. Métodos: `all()`, `find()`, `findBy()`, `where()` (trata `null` como `IS NULL`), `create()`→id, `update()`, `delete()`, `count()`. |
| `Controller` | `view()`, `json()`, `flashOld()`, `clearOld()`. |
| `Request` | `method()` (soporta `_method`), `path()` (quita el base), `str()`, `int()`, `input()`, `all()`. |
| `Router` | `get/post/put/delete()`, `group([mw], fn)`. Handler string `'Sub\\Controller@metodo'` (prefijo `App\Controllers\`) o closure. Params `{id}` → regex. |
| `View` | `render(vista, datos, layout)` y `renderPartial()`. Vistas = PHP plano en `app/Views/`. |
| `Auth` | `attempt()`, `check()`, `user()` (cacheada), `id()`, `role()`, `hasRole()`, `logout()`. Sesión `auth_user_id`; `session_regenerate_id` al entrar. |
| `Csrf` | `token()`, `verify()`, `field()` (input oculto). |

**Middlewares** (`app/Middleware/`): `AuthMiddleware` (exige sesión), `RoleMiddleware('admin','reseller',…)` (RBAC), `CsrfMiddleware` (valida token en POST/PUT/DELETE). Se aplican por ruta o por grupo en `config/routes.php`.

## Modelos — `app/Models/`

| Modelo | Tabla | Métodos propios |
|--------|-------|-----------------|
| `User` | `users` | `clients()`, `resellers()`, `clientsOfReseller()`, `emailExists()` |
| `Server` | `servers` | `nextFreePort()` (avanza de 2 en 2), `activeCount()` |
| `Plan` | `plans` | `global()` |
| `Station` | `stations` | `allWithOwner()`, `forUser()`, `forReseller()`, `findWithServer()`, `activeWithServer()` |
| `Invoice` | `invoices` | `allWithUser()`, `forUser()`, `overdueUnpaid()` |
| `StationStat` | `station_stats` | `latest()`, `history()` |
| `MediaTrack` | `media_tracks` | `forStation()`, `diskUsage()` |
| `Playlist` | `playlists` | `forStation()`, `items()` |
| `PlaylistItem` | `playlist_items` | `nextPosition()` |
| `ActivityLog` | `activity_log` | `record($action,$details)` |

## Servicios — `app/Services/`

- **`ShoutcastService`** — genera `storage/configs/station_<id>.conf` desde `templates/sc_serv.conf.tpl`; `start/stop/restart()`; `fetchStats()` (lee `/statistics?json=1`; en `mock` inventa datos); elige driver con `driverFor()`.
- **`AutoDjService`** — genera el `.liq` y los `.m3u` por playlist; `start/stop()` de Liquidsoap (mock/systemd/proc_open); `mediaDir()`, `liqPath()`.
- **`BillingService`** — `runOverdue()` (marca vencidas + suspende estaciones), `reactivateUser()` (reactiva al pagar).
- **`Crypto`** — AES‑256‑CBC con clave `sha256(APP_KEY)`. Cifra la contraseña admin de cada estación en BD.

## Control de procesos — `app/Process/`

Interfaz `ProcessController { start, stop, restart, isRunning }` con 3 drivers:

| Driver | Shoutcast | AutoDJ (Liquidsoap) |
|--------|-----------|---------------------|
| `MockDriver` | marcador `storage/pids/station_<id>.running` | marcador `.autodj.running` |
| `WindowsDriver` | `proc_open` `sc_serv.exe` + PID | `proc_open` `liquidsoap.exe` |
| `LinuxDriver` | `sudo systemctl … shoutcast@<id>` | `sudo systemctl … liquidsoap@<id>` |

Se elige por el campo `servers.driver` (o `SHOUTCAST_DRIVER` del `.env`).

## Controladores y rutas

- Organizados por rol: `App\Controllers\{Admin,Client,Reseller}\…`, más `Auth`, `Home`, `Profile`, `Api\StatsController`.
- **AutoDJ** comparte lógica en `BaseAutoDjController` (abstracto); cada rol extiende y define `$base` y `authorizeStation()` (scoping de acceso).
- En `config/routes.php` las rutas se agrupan con `RoleMiddleware`. Handler = `'Sub\\Controller@metodo'`.

## Base de datos y migraciones

- Migraciones en `database/migrations/NNN_nombre.sql` (`001_initial_schema.sql`, `002_autodj.sql`).
- `cron/migrate.php` crea la BD (tolerante si el usuario no tiene privilegio `CREATE`), aplica **todas** las migraciones en orden y **siembra** admin, servidor local y planes demo. La contraseña del admin se toma de `ADMIN_PASSWORD`/`ADMIN_EMAIL` (env) o cae a `admin123` en dev.
- Tablas núcleo: `users`, `servers`, `plans`, `stations`, `invoices`, `station_stats`, `settings`, `activity_log`; AutoDJ: `media_tracks`, `playlists`, `playlist_items`, `autodj_schedules`.

## Puertos y streaming

- `stations.port` sale del rango del servidor (`port_range_start..end`, **paso 2** porque Shoutcast usa `port` y `port+1`).
- `stations.dj_port = port + 10000` → harbor de Liquidsoap para DJ en vivo.
- El listener escucha en `http://host:port/stream`; el source (software DJ) apunta a `host:port` mount `/stream` con `source_password`.

## Convenciones de código

- `declare(strict_types=1);` en todos los archivos PHP.
- **Siempre** PDO preparado (usar los helpers de `Model`, o `?` con arrays).
- En vistas: `e()` para escapar, `Csrf::field()` en cada `<form method="post">`, `<input type="hidden" name="_method" value="PUT|DELETE">` para esos verbos.
- Mensajes: `set_flash($tipo,$msg)` + partial `flash`; repoblar formularios con `old()` / `flashOld()`.
- **Texto de UI en español con tildes** en las vistas; en **código, logs y `.sh` evitar tildes/ñ** para no depender de la codificación.
- Nunca pasar entrada de usuario a shell; operar por ID validado y `escapeshellarg`.

## Recetas rápidas

**Nueva pantalla CRUD:** añadir rutas en `config/routes.php` → crear `App\Controllers\…Controller` (extiende `Controller`) → crear vistas en `app/Views/…` (usan `layouts/app`).

**Nueva migración:** crear `database/migrations/003_xxx.sql` y correr `php cron/migrate.php` (idempotente).

**Nuevo campo en estación:** añadir columna vía nueva migración → incluirlo en el form (`admin/stations/form.php`) y en `StationController::store/update`.

**Nuevo driver de proceso:** implementar `App\Process\ProcessController` y mapearlo en `ShoutcastService::driverFor()` / `AutoDjService`.

## Verificación local (sin Shoutcast, driver `mock`)

```bash
# Lint de todo el PHP
for f in $(find app cron config public -name '*.php'); do php -l "$f"; done
# Crear/actualizar BD + seed
php cron/migrate.php
# Poblar estadísticas de prueba
php cron/poll_stats.php
```

Prueba de humo por HTTP: `GET /login` para obtener cookie + `csrf_token`, `POST /login`, luego navegar el panel (todas las páginas deben dar 200 sin errores PHP).

## Scripts operativos

| Script | Rol |
|--------|-----|
| `install.sh` | Instalación completa en Ubuntu Server (idempotente). |
| `deploy.sh` | Auto‑deploy: lo ejecuta el cron cada minuto; `git reset --hard` al remoto + migraciones si hay cambios. |
| `update.sh` | Actualización manual con **menú interactivo** y detalle de cambios. |
| `cron/migrate.php` | Migraciones + seed. |
| `cron/poll_stats.php` | Snapshot de oyentes (cada minuto). |
| `cron/billing_run.php` | Vencimientos + auto‑suspensión (diario). |
| `cron/boot_restore.php` | Re‑levanta estaciones "en línea" tras reinicio. |
| `cron/db_backup.sh` | Backup diario de la BD (lo genera `install.sh`). |

## Credenciales de desarrollo

`admin@sonic.local` / `admin123` (solo en el seed de desarrollo; en producción `install.sh` genera una aleatoria).

## Limitaciones conocidas y roadmap

- **Liquidsoap `%mp3`** requiere soporte LAME según la versión/paquete; en algunas builds hay que instalar el plugin de codificación.
- **Rate‑limit de login** es básico (contador por sesión).
- `update.sh` aún **no tiene `--rollback`** (volver al commit anterior + restaurar backup) — mejora propuesta.
- **HTTPS** es autofirmado; para certificado de confianza sin puerto 80, usar Let's Encrypt **DNS‑01** (documentado en *Actualizar → repo privado / HTTPS*).
- Ideas futuras: soporte **Icecast**, **pasarela de pago**, **reproductor web** embebible por estación, **multi‑servidor** remoto por SSH, **GitHub Actions** para deploy, rama `production` separada de `main`.

## Historial de decisiones (contexto)

- Proyecto tipo **SonicPanel**; alcance elegido: multi‑tenant (admin/reseller/cliente) + planes + facturación + **AutoDJ full**.
- Desarrollo en **XAMPP/Windows** (driver `mock`), producción en **Ubuntu Server** (driver `linux`) en el **puerto 7000** por HTTPS.
- Repo: <https://github.com/kambire/sonicstreaming> · auto‑deploy por sondeo activado por defecto.
