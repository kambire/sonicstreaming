-- Esquema inicial del panel SonicStreaming
-- Motor: MariaDB / MySQL (InnoDB, utf8mb4)

SET FOREIGN_KEY_CHECKS = 0;

-- Usuarios: admin | reseller | client
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(120)  NOT NULL,
    email         VARCHAR(190)  NOT NULL,
    password_hash VARCHAR(255)  NOT NULL,
    role          ENUM('admin','reseller','client') NOT NULL DEFAULT 'client',
    reseller_id   INT UNSIGNED  NULL,           -- si el usuario pertenece a un reseller
    max_accounts  INT UNSIGNED  NOT NULL DEFAULT 0,  -- cuota de cuentas (solo resellers)
    phone         VARCHAR(40)   NULL,
    status        ENUM('active','suspended') NOT NULL DEFAULT 'active',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_users_email (email),
    KEY idx_users_reseller (reseller_id),
    CONSTRAINT fk_users_reseller FOREIGN KEY (reseller_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Servidores fisicos donde corren las instancias Shoutcast
CREATE TABLE IF NOT EXISTS servers (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name             VARCHAR(120) NOT NULL,
    hostname         VARCHAR(190) NOT NULL DEFAULT '127.0.0.1',
    driver           ENUM('mock','windows','linux') NOT NULL DEFAULT 'mock',
    port_range_start INT UNSIGNED NOT NULL DEFAULT 8000,
    port_range_end   INT UNSIGNED NOT NULL DEFAULT 8100,
    max_streams      INT UNSIGNED NOT NULL DEFAULT 50,
    status           ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Planes / paquetes
CREATE TABLE IF NOT EXISTS plans (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(120) NOT NULL,
    max_bitrate   INT UNSIGNED NOT NULL DEFAULT 128,   -- kbps
    max_listeners INT UNSIGNED NOT NULL DEFAULT 100,
    disk_quota_mb INT UNSIGNED NOT NULL DEFAULT 500,   -- espacio para AutoDJ (MB)
    price         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    billing_cycle ENUM('monthly','quarterly','yearly') NOT NULL DEFAULT 'monthly',
    reseller_id   INT UNSIGNED NULL,   -- plan propio de un reseller (NULL = global)
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_plans_reseller (reseller_id),
    CONSTRAINT fk_plans_reseller FOREIGN KEY (reseller_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estaciones (radios) = instancias Shoutcast
CREATE TABLE IF NOT EXISTS stations (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED NOT NULL,     -- cliente dueno
    server_id       INT UNSIGNED NOT NULL,
    plan_id         INT UNSIGNED NULL,
    name            VARCHAR(150) NOT NULL,
    port            INT UNSIGNED NOT NULL,
    dj_port         INT UNSIGNED NULL,          -- puerto harbor Liquidsoap para DJ en vivo
    source_password VARCHAR(190) NOT NULL,
    admin_password  VARCHAR(255) NOT NULL,     -- cifrada
    max_listeners   INT UNSIGNED NOT NULL DEFAULT 100,
    max_bitrate     INT UNSIGNED NOT NULL DEFAULT 128,
    type            ENUM('live','relay') NOT NULL DEFAULT 'live',
    relay_url       VARCHAR(255) NULL,
    genre           VARCHAR(120) NULL,
    autodj_enabled  TINYINT(1) NOT NULL DEFAULT 0,
    status          ENUM('running','stopped','suspended') NOT NULL DEFAULT 'stopped',
    autodj_status   ENUM('running','stopped') NOT NULL DEFAULT 'stopped',
    autostart       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_station_server_port (server_id, port),
    KEY idx_stations_user (user_id),
    KEY idx_stations_server (server_id),
    CONSTRAINT fk_stations_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    CONSTRAINT fk_stations_server FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE RESTRICT,
    CONSTRAINT fk_stations_plan   FOREIGN KEY (plan_id)   REFERENCES plans(id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Facturas
CREATE TABLE IF NOT EXISTS invoices (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    station_id  INT UNSIGNED NULL,
    concept     VARCHAR(190) NOT NULL DEFAULT 'Servicio de streaming',
    amount      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    due_date    DATE NOT NULL,
    status      ENUM('pending','paid','overdue') NOT NULL DEFAULT 'pending',
    paid_at     DATETIME NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_invoices_user (user_id),
    KEY idx_invoices_status (status),
    CONSTRAINT fk_invoices_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    CONSTRAINT fk_invoices_station FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Snapshots de estadisticas por estacion
CREATE TABLE IF NOT EXISTS station_stats (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    station_id        INT UNSIGNED NOT NULL,
    captured_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    current_listeners INT UNSIGNED NOT NULL DEFAULT 0,
    peak_listeners    INT UNSIGNED NOT NULL DEFAULT 0,
    unique_listeners  INT UNSIGNED NOT NULL DEFAULT 0,
    song_title        VARCHAR(255) NULL,
    bitrate           INT UNSIGNED NOT NULL DEFAULT 0,
    is_up             TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_stats_station_time (station_id, captured_at),
    CONSTRAINT fk_stats_station FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajustes globales clave/valor
CREATE TABLE IF NOT EXISTS settings (
    setting_key   VARCHAR(120) NOT NULL,
    setting_value TEXT NULL,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bitacora de auditoria
CREATE TABLE IF NOT EXISTS activity_log (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NULL,
    action     VARCHAR(120) NOT NULL,
    details    VARCHAR(255) NULL,
    ip         VARCHAR(45)  NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_log_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
