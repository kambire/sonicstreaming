-- Migration 004: Analiticas de Oyentes y Geolocalizacion GeoIP
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS listener_sessions (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    station_id       INT UNSIGNED NOT NULL,
    listener_ip      VARCHAR(45) NOT NULL,
    country          VARCHAR(100) NULL,
    country_code     VARCHAR(10) NULL,
    city             VARCHAR(100) NULL,
    latitude         DECIMAL(10, 7) NULL,
    longitude        DECIMAL(10, 7) NULL,
    user_agent       VARCHAR(255) NULL,
    device_type      ENUM('desktop', 'mobile', 'tablet', 'bot', 'unknown') NOT NULL DEFAULT 'unknown',
    player_name      VARCHAR(100) NULL,
    connected_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    disconnected_at  DATETIME NULL DEFAULT NULL,
    duration_seconds INT UNSIGNED NOT NULL DEFAULT 0,
    bytes_sent       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_ls_station (station_id),
    KEY idx_ls_connected (connected_at),
    KEY idx_ls_country (country_code),
    CONSTRAINT fk_ls_station FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
