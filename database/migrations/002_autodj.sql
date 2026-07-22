-- AutoDJ: biblioteca de medios, playlists y programacion.
-- Motor: MariaDB / MySQL (InnoDB, utf8mb4)

SET FOREIGN_KEY_CHECKS = 0;

-- Pistas de audio subidas por el cliente para el AutoDJ
CREATE TABLE IF NOT EXISTS media_tracks (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    station_id    INT UNSIGNED NOT NULL,
    filename      VARCHAR(255) NOT NULL,          -- nombre en disco (seguro)
    original_name VARCHAR(255) NOT NULL,
    title         VARCHAR(255) NULL,
    artist        VARCHAR(255) NULL,
    duration      INT UNSIGNED NOT NULL DEFAULT 0, -- segundos
    filesize      BIGINT UNSIGNED NOT NULL DEFAULT 0, -- bytes
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_media_station (station_id),
    CONSTRAINT fk_media_station FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Playlists por estacion
CREATE TABLE IF NOT EXISTS playlists (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    station_id  INT UNSIGNED NOT NULL,
    name        VARCHAR(150) NOT NULL,
    type        ENUM('general','jingle','scheduled') NOT NULL DEFAULT 'general',
    shuffle     TINYINT(1) NOT NULL DEFAULT 1,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    weight      INT UNSIGNED NOT NULL DEFAULT 1,  -- peso en la rotacion general
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_playlists_station (station_id),
    CONSTRAINT fk_playlists_station FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pistas dentro de una playlist (con orden)
CREATE TABLE IF NOT EXISTS playlist_items (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    playlist_id INT UNSIGNED NOT NULL,
    track_id    INT UNSIGNED NOT NULL,
    position    INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_pi_playlist (playlist_id),
    CONSTRAINT fk_pi_playlist FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
    CONSTRAINT fk_pi_track    FOREIGN KEY (track_id)    REFERENCES media_tracks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Programacion horaria del AutoDJ (que playlist suena y cuando)
CREATE TABLE IF NOT EXISTS autodj_schedules (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    station_id  INT UNSIGNED NOT NULL,
    playlist_id INT UNSIGNED NOT NULL,
    day_of_week TINYINT UNSIGNED NOT NULL DEFAULT 0, -- 0=todos, 1=Lun ... 7=Dom
    start_time  TIME NOT NULL DEFAULT '00:00:00',
    end_time    TIME NOT NULL DEFAULT '23:59:59',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sched_station (station_id),
    CONSTRAINT fk_sched_station  FOREIGN KEY (station_id)  REFERENCES stations(id)  ON DELETE CASCADE,
    CONSTRAINT fk_sched_playlist FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
