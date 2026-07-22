# SonicStreaming Panel

Panel de administración de **streaming de audio** (tipo SonicPanel) escrito en **PHP 8.2 + MariaDB/MySQL**, sin frameworks pesados. Administra clientes, planes, revendedores y estaciones **Shoutcast DNAS v2**, con **AutoDJ (Liquidsoap)**, estadísticas en vivo y facturación con auto-suspensión.

> Desarrollado para correr en **XAMPP (Windows)** durante el desarrollo y desplegarse en **Linux (VPS)** en producción, mediante una capa de abstracción de control de procesos (`mock` / `windows` / `linux`).

---

## Características

- **Roles:** Super Admin · Reseller · Cliente, cada uno con su panel.
- **Estaciones Shoutcast:** crear/editar, asignación automática de puerto, iniciar/detener/reiniciar, generación de `sc_serv.conf`.
- **AutoDJ (Liquidsoap):** biblioteca de música por estación, playlists con peso y shuffle, script `.liq` generado automáticamente, harbor de DJ en vivo con *fallback* vivo→AutoDJ.
- **Planes:** bitrate máx, oyentes máx, cuota de disco para AutoDJ, precio y ciclo.
- **Estadísticas:** poller por cron, histórico y gráfica de oyentes en vivo (Chart.js), "ahora suena".
- **Facturación:** facturas con vencimiento, marca de vencidas y **auto-suspensión** de estaciones; reactivación al pagar.
- **Seguridad:** contraseñas con `password_hash`, CSRF en formularios, RBAC por middleware, PDO preparado, contraseñas admin de Shoutcast cifradas (AES-256), auditoría.

---

## Requisitos

- PHP 8.1+ con extensiones `pdo_mysql`, `openssl`, `curl` (XAMPP ya las trae).
- MariaDB / MySQL.
- Para streaming real: **Shoutcast DNAS v2** (`sc_serv`) y, para AutoDJ, **Liquidsoap**.

No requiere Composer: el autoloader y el lector de `.env` son propios. (Existe `composer.json` opcional para autoload PSR-4.)

---

## Instalación en desarrollo (XAMPP / Windows)

1. Coloca el proyecto en `C:\xampp\htdocs\sonicstreaming` (ya está).
2. Copia la configuración y ajústala si hace falta:
   ```bash
   copy .env.example .env
   ```
   Por defecto usa `root` sin contraseña y `SHOUTCAST_DRIVER=mock` (simulado, no arranca procesos reales).
3. Crea la base de datos, tablas y datos iniciales:
   ```bash
   C:\xampp\php\php.exe cron\migrate.php
   ```
4. Abre el panel: **http://localhost/sonicstreaming/public/**
5. Entra con el admin sembrado:
   - **Usuario:** `admin@sonic.local`
   - **Contraseña:** `admin123`  *(cámbiala en producción)*

### Poblar estadísticas en desarrollo
Ejecuta el poller manualmente unas cuantas veces (en `mock` inventa datos plausibles cuando la estación está "en línea"):
```bash
C:\xampp\php\php.exe cron\poll_stats.php
```

---

## Estructura

```
public/        # unica carpeta expuesta (front controller + assets)
app/
  Core/        # Router, Database(PDO), Auth, Csrf, View, Model...
  Controllers/ # Admin/ Reseller/ Client/ Api/ + Base AutoDJ
  Models/      # User, Server, Plan, Station, Invoice, Media, Playlist...
  Services/    # ShoutcastService, AutoDjService, BillingService, Crypto
  Process/     # ProcessController + MockDriver/WindowsDriver/LinuxDriver
  Views/       # vistas PHP + layouts
config/        # routes.php
database/      # migrations/*.sql
cron/          # migrate.php, poll_stats.php, billing_run.php
templates/     # sc_serv.conf.tpl
storage/       # configs (.conf/.liq), media, pids, logs
deploy/        # systemd, sudoers, cron, nginx/apache de ejemplo
```

---

## Cómo funciona el streaming

Cada **estación** es una instancia de `sc_serv` en un puerto propio (y `puerto+1` para admin). El control de procesos usa el **driver** del servidor:

| Driver    | Uso                | Qué hace |
|-----------|--------------------|----------|
| `mock`    | Desarrollo         | Simula start/stop con un archivo marcador; inventa stats. |
| `windows` | Dev con Shoutcast  | Lanza `sc_serv.exe` (ver `SHOUTCAST_BIN`). |
| `linux`   | **Producción**     | `systemctl start/stop/restart shoutcast@<id>`. |

Las estadísticas se leen del DNAS por `http://host:puerto/statistics?json=1`.

### AutoDJ (Liquidsoap)

Con AutoDJ habilitado, cada estación genera un script `.liq` que:
- reproduce las playlists activas (rotación aleatoria ponderada),
- expone un **harbor** para DJ en vivo en `dj_port` (= puerto + 10000),
- hace `fallback(track_sensitive=false, [live, autodj])`,
- envía el audio a `sc_serv` como fuente (`output.shoutcast`).

Liquidsoap se controla igual que Shoutcast con el driver (`mock`/`windows`/`linux` → `liquidsoap@<id>`).

---

## Despliegue en producción (Ubuntu) — instalador automático

En un servidor **Ubuntu** limpio, sube el proyecto y ejecuta:

```bash
sudo bash install.sh
```

El script instala y configura **todo lo necesario** y deja el panel sirviendo en el **puerto 7000**:

- Nginx (escuchando en `:7000`) + PHP-FPM + MariaDB + Liquidsoap (AutoDJ).
- Base de datos, usuario dedicado y `.env` de producción (con `APP_KEY` y contraseña de BD aleatorias, `SHOUTCAST_DRIVER=linux`).
- Migraciones + datos iniciales (admin, servidor local, planes).
- Unidades systemd `shoutcast@` y `liquidsoap@`, reglas `sudoers`, límites de subida de PHP y tareas `cron`.
- Firewall (`ufw`) abriendo el puerto 7000 y el rango de estaciones 8000–8100.

Al terminar imprime la URL (`http://<IP>:7000/`) y las credenciales. Puedes cambiar el puerto con `sudo WEB_PORT=8080 bash install.sh`.

> **Shoutcast DNAS** es un binario propietario: el script intenta descargarlo, pero si falla, coloca `sc_serv` en `/opt/shoutcast/sc_serv` (chmod +x) — descárgalo de <https://www.shoutcast.com/broadcast-tools>. El resto del panel funciona sin él; solo el arranque de estaciones lo requiere.

### Despliegue manual (paso a paso)

1. **Sube el proyecto** a `/var/www/sonicstreaming` y crea `.env` con:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=<clave-aleatoria-larga>
   DB_...=<credenciales>
   SHOUTCAST_DRIVER=linux
   SHOUTCAST_HOST=127.0.0.1
   ```
2. **Web server** apuntando el DocumentRoot a `public/`:
   - Nginx: `deploy/nginx.conf.example`
   - Apache: `deploy/apache-vhost.conf.example` (activa `mod_rewrite`).
3. **Base de datos:**
   ```bash
   php cron/migrate.php
   ```
4. **Shoutcast:** instala `sc_serv` (p. ej. en `/opt/shoutcast`) y copia la unidad plantilla:
   ```bash
   sudo cp deploy/shoutcast@.service   /etc/systemd/system/
   sudo cp deploy/liquidsoap@.service  /etc/systemd/system/
   sudo systemctl daemon-reload
   ```
5. **AutoDJ:** instala Liquidsoap (`apt install liquidsoap`).
6. **Permisos systemd sin contraseña** para el usuario web:
   ```bash
   sudo cp deploy/sonicstreaming.sudoers /etc/sudoers.d/sonicstreaming
   sudo chmod 440 /etc/sudoers.d/sonicstreaming
   ```
7. **Cron** (estadísticas + facturación): ver `deploy/crontab.example`.
8. **Permisos de escritura** para el usuario web en `storage/`:
   ```bash
   sudo chown -R www-data:www-data storage
   ```

---

## Seguridad

- Solo `public/` debe ser accesible por web. En producción, DocumentRoot = `public/`.
- `.env`, `storage/`, `.conf`, `.liq`, etc. quedan bloqueados por `.htaccess`/nginx.
- Cambia `admin@sonic.local` / `admin123` y define un `APP_KEY` fuerte.
- Sube el límite de subida de PHP para el AutoDJ (`upload_max_filesize`, `post_max_size`) y de Nginx (`client_max_body_size`).

---

## Cuentas de prueba (tras el seed)

| Rol   | Correo               | Contraseña |
|-------|----------------------|------------|
| Admin | `admin@sonic.local`  | `admin123` |

Crea clientes y resellers desde el panel de administración.
